<?php

namespace Tests\Feature\Jobs;

use App\Jobs\VeryLongJob;
use App\Mail\ArticleCreated;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class VeryLongJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_worker_sends_the_new_article_mail_to_each_moderator(): void
    {
        $moderator = User::factory()->moderator()->create();
        User::factory()->create();
        $article = Article::factory()->create([
            'title' => 'Новая статья',
            'content' => 'Содержимое новой статьи.',
        ]);
        Mail::fake();
        config(['queue.default' => 'database']);

        dispatch(new VeryLongJob($article));

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--once' => true,
        ])->assertExitCode(0);

        Mail::assertSent(ArticleCreated::class, function (ArticleCreated $mail) use ($moderator, $article): bool {
            $mail->assertHasSubject('Добавлена новая статья');
            $mail->assertSeeInHtml('Новая статья');
            $mail->assertSeeInHtml('Содержимое новой статьи.');

            return $mail->hasTo($moderator->email) && $mail->article->is($article);
        });
        Mail::assertSentCount(1);
    }
}
