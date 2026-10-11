@extends('layouts.blog')
@section('title', 'Đăng ký độc giả')
@section('content')
<header class="page-heading"><span class="overline">MỘT GÓC ĐỌC NHỎ</span><h1>Tham gia cùng mình<span class="heading-dot">.</span></h1><p>Tài khoản dành cho độc giả, không phải tài khoản đăng bài.</p></header>
<form class="form-stack auth-form" method="post" action="{{ route('register.store') }}">@csrf
<label for="name">Tên hiển thị</label><input id="name" name="name" value="{{ old('name') }}" maxlength="80" autocomplete="name" required>
<label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
<label for="password">Mật khẩu</label><input id="password" name="password" type="password" minlength="10" maxlength="255" autocomplete="new-password" required><span class="field-hint">Ít nhất 10 ký tự, có chữ và số.</span>
<label for="password_confirmation">Nhập lại mật khẩu</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
<button class="button button--black" type="submit">Tạo tài khoản</button><a class="text-link" href="{{ route('login') }}">Đã có tài khoản? Đăng nhập</a>
</form>
@endsection
