@extends('layouts.public')
@php $pageTitle='Chọn tên hiển thị'; @endphp
@section('content')
<section class="auth-shell">
    <div class="auth-intro">
        <p class="eyebrow">CHÀO MỪNG BẠN</p>
        <h1>Một bước nhỏ.<br><em>Một góc đọc riêng.</em></h1>
        <p>Tài khoản đã được tạo bằng Google và email đã được xác minh. Chỉ cần xác nhận tên hiển thị để bắt đầu.</p>
    </div>
    <div class="auth-card">
        <h2>Bạn muốn được gọi là gì?</h2>
        <p>Tên từ Google đã được điền sẵn. Nhấp vào ô bên dưới nếu bạn muốn đổi tên.</p>
        <form method="post" action="{{ route('google.profile.store') }}">
            @csrf
            <x-field name="name" label="Tên hiển thị" :value="auth()->user()->name" maxlength="100" autocomplete="name" required/>
            <p class="muted small">Bạn có thể đổi tên hoặc thêm mật khẩu riêng cho website trong Hồ sơ của tôi bất cứ lúc nào.</p>
            <p class="muted small">Khi tiếp tục, bạn đồng ý với <a href="{{ route('terms') }}">điều khoản</a> và <a href="{{ route('privacy') }}">chính sách quyền riêng tư</a>.</p>
            <button class="btn btn-forest w-100">Hoàn tất và bắt đầu đọc ↗</button>
        </form>
        <form method="post" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button class="btn btn-outline-forest w-100">Đăng xuất</button>
        </form>
    </div>
</section>
@endsection
