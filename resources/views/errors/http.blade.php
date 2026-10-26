<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lỗi {{ $status }} — Threads City</title>
</head>
<body style="font:18px/1.7 system-ui;background:#000;color:#f3f5f7;max-width:760px;margin:80px auto;padding:24px">
    <main>
        <p>LỖI {{ $status }}</p>
        <h1>Chưa thực hiện được thao tác</h1>
        <p role="alert">{{ $message }}</p>
        <p><a href="{{ route('home') }}">Về trang chủ</a> · <a href="{{ route('login') }}">Đăng nhập</a></p>
    </main>
</body>
</html>
