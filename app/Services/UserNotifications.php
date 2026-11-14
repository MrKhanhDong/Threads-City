<?php

namespace App\Services;

use App\Models\ContentReport;
use App\Models\Post;
use App\Models\User;
use App\Notifications\CommentReplied;
use App\Notifications\Mentioned;
use App\Notifications\ModerationDecision;
use App\Notifications\ReportHandled;
use App\Notifications\ThreadInteractionNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class UserNotifications
{
    public function forUser(User $user): MorphMany
    {
        return $user->notifications()->where(function (Builder $notifications) use ($user): void {
            $notifications->where('type', ModerationDecision::class)->orWhere(function (Builder $legacy) use ($user): void {
                $legacy->where(function (Builder $replies) use ($user): void {
                    $replies->where('type', CommentReplied::class)
                        ->whereIn('data->comment_id', app(CommentThreads::class)->visible($user->id)->select('id'));
                })->orWhere(function (Builder $results) use ($user): void {
                    $results->where('type', ReportHandled::class)
                        ->whereIn('data->report_id', ContentReport::where('reporter_id', $user->id)->whereIn('status', ['resolved', 'dismissed'])->select('id'));
                })->orWhere(function (Builder $mentions) use ($user): void {
                    $mentions->where('type', Mentioned::class)->whereIn('data->mention_id', app(Mentions::class)->visibleTo($user)->select('id'));
                })->orWhere(function (Builder $social) use ($user): void {
                    $social->where('type', ThreadInteractionNotification::class)
                        ->whereIn('data->actor_id', User::discoverableTo($user)->notLocked()->select('users.id'))
                        ->where(function (Builder $targets) use ($user): void {
                            $targets->where('data->action', 'follow')->orWhereIn('data->thread_id', Post::published()->visibleTo($user)->select('posts.id'));
                        });
                });
            });
        });
    }
}
