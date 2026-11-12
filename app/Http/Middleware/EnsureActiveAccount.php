<?php

namespace App\Http\Middleware;

use App\Models\Post;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_locked) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            if ($request->expectsJson()) {
                abort(403, 'Tài khoản đang bị khóa.');
            }

            return redirect()->route('login')->withErrors(['email' => 'Tài khoản đang bị khóa.']);
        }

        if ($request->user()?->google_profile_pending && ! $request->routeIs('google.profile', 'google.profile.store', 'logout', 'terms', 'privacy')) {
            return redirect()->route('google.profile');
        }

        $thread = $request->route('thread');
        if ($thread instanceof Post && ! $request->routeIs('threads.destroy', 'threads.unarchive', 'admin.*')) {
            abort_unless(Post::published()->visibleTo($request->user())->whereKey($thread->id)->exists(), 404);
        }

        return $next($request);
    }
}
