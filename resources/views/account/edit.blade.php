@extends('layouts.public')
@php $pageTitle='Hồ sơ của tôi'; $user=auth()->user(); @endphp
@section('content')
<section class="container page-section">
    <p class="eyebrow">GÓC ĐỌC CỦA BẠN</p>
    <h1>Hồ sơ của tôi</h1>
    <div class="profile-grid">
        <div class="profile-card">
            <x-avatar :user="$user" />
            <h2>{{ $user->name }}</h2>
            <p>{{ $user->email }}</p>
            <p class="status">{{ $user->hasVerifiedEmail() ? 'Email đã xác minh' : 'Chưa xác minh email' }}</p>
            <a class="text-link" href="{{ route('saved') }}">Bài viết đã lưu ↗</a>
            @if(!$user->hasVerifiedEmail())
                <a class="text-link d-block mt-3" href="{{ route('verification.notice') }}">Xác minh email ↗</a>
            @endif
        </div>
        <div>
            <div class="panel">
                <h2>Thông tin cá nhân</h2>
                <form action="{{ route('account.update') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('patch')
                    <x-field name="name" label="Tên hiển thị" :value="$user->name" required maxlength="100"/>
                    <x-field name="bio" label="Một chút về bạn" type="textarea" :value="$user->bio" maxlength="1000"/>
                    <x-field name="avatar" type="file" label="Ảnh đại diện" accept="image/jpeg,image/png,image/webp"/>
                    <p class="muted small">JPEG, PNG, WebP; tối đa 2 MB.</p>
                    <button class="btn btn-forest">Lưu hồ sơ</button>
                </form>
            </div>
            <div class="panel mt-4">
                @if($user->google_id)
                    <h2>Thiết lập mật khẩu qua email</h2>
                    <p>Bạn đã liên kết tài khoản Google. Nếu chưa đặt mật khẩu riêng cho website, hãy nhận liên kết qua email để thiết lập mà không cần mật khẩu hiện tại.</p>
                    <p class="muted">Thư được gửi tới <strong>{{ $user->email }}</strong>. Không dùng mật khẩu Gmail; sau khi thiết lập, bạn có thể đăng nhập bằng Google hoặc email và mật khẩu trên cùng một tài khoản.</p>
                    <x-local-mail-preview />
                    @if(in_array(config('mail.default'), ['array', 'log']))
                        <p class="alert alert-warning">SMTP chưa được cấu hình. Hiện tại website chưa gửi được liên kết thiết lập mật khẩu.</p>
                    @endif
                    <form action="{{ route('password.email') }}" method="post">
                        @csrf
                        <button class="btn btn-forest">Gửi liên kết thiết lập mật khẩu</button>
                    </form>
                    <p class="muted small mt-3">Nếu đã có mật khẩu riêng, bạn cũng có thể dùng liên kết này để đặt lại mật khẩu.</p>
                    <details class="mt-4" @if($errors->hasAny(['current_password', 'password', 'password_confirmation'])) open @endif>
                        <summary class="text-link">Đã có mật khẩu? Đổi mật khẩu hiện tại</summary>
                @else
                    <h2>Đổi mật khẩu</h2>
                @endif
                <form action="{{ route('account.password') }}" method="post" @class(['mt-3' => (bool) $user->google_id])>
                    @csrf
                    @method('put')
                    <x-field name="current_password" label="Mật khẩu hiện tại" type="password" required autocomplete="current-password"/>
                    <x-field name="password" label="Mật khẩu mới" type="password" required minlength="8" autocomplete="new-password"/>
                    <x-field name="password_confirmation" label="Nhập lại mật khẩu" type="password" required autocomplete="new-password"/>
                    <button class="btn btn-outline-forest">Đổi mật khẩu</button>
                </form>
                @if($user->google_id)
                    </details>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
