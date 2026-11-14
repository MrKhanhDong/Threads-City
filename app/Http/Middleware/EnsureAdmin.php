<?php

namespace App\Http\Middleware;

use App\Services\ModerationSchema;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if (! app(ModerationSchema::class)->isReady()) {
            $message = 'Cần cập nhật cơ sở dữ liệu trước khi sử dụng chức năng quản trị mới.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 503)->header('Cache-Control', 'no-store')
                : response()->view('errors.moderation-setup', ['message' => $message], 503)->header('Cache-Control', 'no-store');
        }

        return $next($request);
    }
}
