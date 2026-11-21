<?php

namespace App\Jobs;

use App\Mail\ArticleCreated;
use App\Models\Article;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class VeryLongJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Article $article) {}

    public function handle(): void
    {
        User::query()
            ->whereHas('role', fn ($query) => $query->where('name', Role::MODERATOR))
            ->each(function (User $moderator): void {
                Mail::to($moderator->email)->send(new ArticleCreated($this->article));
            });
    }
}
