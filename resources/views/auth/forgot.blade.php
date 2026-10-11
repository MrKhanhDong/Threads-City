@extends('layouts.blog')
@section('title', 'Quên mật khẩu')
@section('content')
<header class="page-heading"><h1>Quên mật khẩu<span class="heading-dot">?</span></h1><p>Nhập email để nhận liên kết đặt lại mật khẩu.</p></header>
<form class="form-stack auth-form" method="post" action="{{ route('password.email') }}">@csrf
<label for="email">Email tài khoản</label><input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
<button class="button button--black" type="submit">Gửi liên kết đặt lại</button><a href="{{ route('login') }}" class="text-link">Quay lại đăng nhập</a>
</form>
@endsection
