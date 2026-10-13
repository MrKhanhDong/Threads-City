<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ViewRecorder
{
    public function record(Request $request, Post $post): void
    {
        if ($request->user()?->isAdmin()) {
            return;
        }
        $day = now()->toDateString();
        $key = 'daily_views.'.$post->id.'.'.$day;
        if ($request->session()->has($key)) {
            return;
        }
        DB::transaction(function () use ($post, $day): void {
            DB::table('post_daily_views')->insertOrIgnore(['post_id' => $post->id, 'view_date' => $day, 'views' => 0, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('post_daily_views')->where('post_id', $post->id)->where('view_date', $day)->increment('views', 1, ['updated_at' => now()]);
            $post->increment('views_count');
        });
        $request->session()->put($key, true);
    }
}
