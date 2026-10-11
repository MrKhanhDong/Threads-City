<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostViewRecorder
{
    public function record(Request $request, Post $post): void
    {
        // Admin reading/preview is not readership. We store no IP/user tracking.
        if ($request->user()?->isAdmin()) return;
        $now = now();
        $day = $now->toDateString();
        $key = 'viewed_post_daily.'.$post->id.'.'.$day;
        if ($request->session()->has($key)) return;

        DB::transaction(function () use ($post, $now, $day) {
            // Unique (post_id, view_date) + atomic SQL increments prevent lost
            // updates when independent reader sessions arrive concurrently.
            DB::table('post_daily_views')->insertOrIgnore([
                'post_id' => $post->id, 'view_date' => $day, 'views' => 0,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('post_daily_views')->where('post_id', $post->id)->where('view_date', $day)
                ->increment('views', 1, ['updated_at' => $now]);
            $post->increment('views_count');
        });

        // Only mark a session after both counters were committed successfully.
        $request->session()->put($key, true);
    }
}
