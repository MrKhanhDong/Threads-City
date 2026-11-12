<?php

namespace App\Services;

use App\Models\ContentReport;
use App\Models\User;
use App\Notifications\CommentReplied;
use App\Notifications\Mentioned;
use App\Notifications\ReportHandled;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class UserNotifications
{
    public function forUser(User $user): MorphMany
    {
        return $user->notifications()->where(function (Builder $notifications) use ($user): void {
            $notifications->where(function (Builder $replies) use ($user): void {
                $replies->where('type', CommentReplied::class)
                    ->whereIn('data->comment_id', app(CommentThreads::class)->visible($user->id)->select('id'));
            })->orWhere(function (Builder $results) use ($user): void {
                $results->where('type', ReportHandled::class)
                    ->whereIn('data->report_id', ContentReport::where('reporter_id', $user->id)->whereIn('status', ['resolved', 'dismissed'])->select('id'));
            })->orWhere(function (Builder $mentions) use ($user): void {
                $mentions->where('type', Mentioned::class)->whereIn('data->mention_id', app(Mentions::class)->visibleTo($user)->select('id'));
            });
        });
    }
}
