@extends('layouts.blog')
@section('title', 'Đăng nhập')
@section('content')
<header class="page-heading"><span class="overline">CHÀO MỪNG TRỞ LẠI</span><h1>Đăng nhập<span class="heading-dot">.</span></h1><p>Lưu bài muốn đọc và để lại lời nhắn cho tác giả.</p></header>
<form class="form-stack auth-form" method="post" action="{{ route('login.store') }}">@csrf
<label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required autofocus>
<label for="password">Mật khẩu</label><input id="password" name="password" type="password" autocomplete="current-password" required>
<label class="check-label"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Ghi nhớ đăng nhập</label>
<button class="button button--black" type="submit">Đăng nhập <x-icon name="arrow" size="16" /></button>
<div class="form-links"><a href="{{ route('password.request') }}">Quên mật khẩu?</a><a href="{{ route('register') }}">Tạo tài khoản độc giả</a></div>
</form>
@endsection
