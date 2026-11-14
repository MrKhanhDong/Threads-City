@extends('layouts.admin')
@php($pageTitle = 'Cập nhật cơ sở dữ liệu')
@section('content')
<section class="panel">
    <h1>Trang quản trị đang chờ cập nhật</h1>
    <p class="muted">{{ $message }}</p>
    <p>Người triển khai cần chạy lệnh sau với cấu hình kết nối database production:</p>
    <pre>php artisan migrate --force --no-interaction</pre>
    <p class="muted">Migration bổ sung cột khóa tài khoản, trạng thái kiểm duyệt và bảng lịch sử xử lý. Không cần chạy lại dữ liệu mẫu.</p>
    <a class="btn btn-forest" href="{{ route('home') }}">Về trang chính</a>
</section>
@endsection
