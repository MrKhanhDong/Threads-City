@extends('layouts.blog')
@section('title', 'Đặt lại mật khẩu')
@section('content')
<header class="page-heading"><h1>Mật khẩu mới<span class="heading-dot">.</span></h1><p>Liên kết chỉ dùng một lần và có thời hạn.</p></header>
<form class="form-stack auth-form" method="post" action="{{ route('password.update') }}">@csrf
<input type="hidden" name="token" value="{{ $token }}">
<label for="email">Email tài khoản</label><input id="email" name="email" type="email" value="{{ old('email', $email) }}" maxlength="255" autocomplete="email" required>
<label for="password">Mật khẩu mới</label><input id="password" name="password" type="password" minlength="10" maxlength="255" autocomplete="new-password" required><span class="field-hint">Ít nhất 10 ký tự, có chữ và số.</span>
<label for="password_confirmation">Nhập lại mật khẩu</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
<button class="button button--black" type="submit">Đặt lại mật khẩu</button>
</form>
@endsection
