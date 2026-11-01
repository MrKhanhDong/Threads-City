<?php

use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\LivePageUpdates;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [EnsureActiveAccount::class, 'auth.session', LivePageUpdates::class]);
        $middleware->alias(['admin' => EnsureAdmin::class]);
        $middleware->redirectGuestsTo(fn (): string => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => $request->is('api/*') || $request->expectsJson());
        $exceptions->render(function (QueryException $error, Request $request) {
            $message = match ((int) ($error->errorInfo[1] ?? 0)) {
                2002 => 'Không kết nối được MySQL. Hãy khởi động MySQL trong Laragon và kiểm tra máy chủ, cổng kết nối.',
                1049 => 'Database được cấu hình chưa tồn tại. Hãy tạo database và chạy migrations trước khi sử dụng.',
                1045 => 'MySQL từ chối tài khoản kết nối. Hãy kiểm tra tên đăng nhập và mật khẩu database trong cấu hình riêng.',
                default => null,
            };
            if ($message !== null) {
                return $request->expectsJson()
                    ? response()->json(['message' => $message], 503)
                    : response()->view('errors.database', ['message' => $message], 503);
            }

            return null;
        });
        $exceptions->respond(function (Response $response, Throwable $error, Request $request): Response {
            $status = $response->getStatusCode();
            if ($error instanceof ValidationException || ($error instanceof QueryException && in_array((int) ($error->errorInfo[1] ?? 0), [2002, 1049, 1045], true))) {
                return $response;
            }
            $message = match ($status) {
                400 => 'Yêu cầu không hợp lệ. Hãy tải lại trang và kiểm tra thông tin trước khi gửi.',
                401 => 'Bạn cần đăng nhập để thực hiện thao tác này.',
                403 => 'Bạn không có quyền truy cập hoặc thực hiện thao tác này.',
                404 => 'Không tìm thấy nội dung được yêu cầu. Nội dung có thể đã bị xóa hoặc chưa được công khai.',
                405 => 'Cách gửi yêu cầu không được hỗ trợ. Hãy quay lại trang và dùng đúng nút thao tác.',
                408, 504 => 'Yêu cầu xử lý quá lâu. Hãy kiểm tra kết nối và thử lại sau.',
                409 => 'Dữ liệu đã thay đổi từ một thao tác khác. Hãy tải lại trang trước khi tiếp tục.',
                413 => 'Dữ liệu tải lên vượt quá giới hạn máy chủ. Hãy giảm dung lượng ảnh rồi thử lại.',
                419 => 'Phiên làm việc đã hết hạn hoặc mã bảo vệ không hợp lệ. Hãy tải lại trang, đăng nhập lại nếu cần rồi gửi lại.',
                422 => 'Thông tin đã nhập chưa hợp lệ. Hãy kiểm tra các trường rồi gửi lại.',
                429 => 'Bạn đã gửi quá nhiều yêu cầu trong thời gian ngắn.',
                500 => 'Máy chủ gặp lỗi khi xử lý yêu cầu. Hãy thử lại sau hoặc báo cho quản trị viên kèm thao tác vừa thực hiện.',
                502, 503 => 'Dịch vụ hiện chưa sẵn sàng. Hãy thử lại sau.',
                default => null,
            };
            if ($message === null) {
                return $response;
            }
            if ($error instanceof InvalidSignatureException) {
                $message = 'Liên kết xác minh không hợp lệ hoặc đã hết hạn. Hãy yêu cầu gửi lại thư xác minh.';
            } elseif ($status === 403 && $request->user()?->is_locked) {
                $message = 'Tài khoản đang bị khóa. Hãy liên hệ quản trị viên để được hỗ trợ.';
            } elseif ($status === 403 && $request->isMethod('post') && $request->routeIs('comments.store', 'likes.store', 'bookmarks.store') && ! $request->user()?->hasVerifiedEmail()) {
                $message = 'Bạn cần xác minh email trước khi bình luận, thích hoặc lưu bài viết.';
            }
            if ($status === 429) {
                $seconds = max(1, (int) $response->headers->get('Retry-After', 60));
                $message .= ' Vui lòng chờ '.$seconds.' giây rồi thử lại.';
            }
            $headers = $response->headers->all();
            unset($headers['content-type'], $headers['content-length']);
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], $status, $headers);
            }
            if ($status === 404) {
                return $response;
            }

            return response()->view('errors.http', ['status' => $status, 'message' => $message], $status, $headers);
        });
    })->create();

if (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL']) || env('VERCEL')) {
    $app->useStoragePath('/tmp/storage');
}

return $app;
