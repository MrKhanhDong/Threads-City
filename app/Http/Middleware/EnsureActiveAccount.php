<?php

namespace App\Http\Middleware;

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

        return $next($request);
    }
}
