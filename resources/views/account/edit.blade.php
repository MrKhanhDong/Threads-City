@extends('layouts.blog')
@section('title', 'Tài khoản của tôi')
@section('content')
<header class="page-heading"><span class="overline">{{ $user->isAdmin() ? 'CHỦ BLOG' : 'ĐỘC GIẢ' }}</span><h1>Tài khoản của tôi<span class="heading-dot">.</span></h1><p>Cập nhật tên, email và mật khẩu đăng nhập.</p></header>
<form class="form-stack auth-form" method="post" action="{{ route('account.update') }}">@csrf @method('PATCH')
<label for="name">Tên hiển thị</label><input id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="80" required autocomplete="name">
<label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="255" required autocomplete="email">
<hr><h2>Đổi mật khẩu</h2><span class="field-hint">Để trống ba ô bên dưới nếu chỉ đổi tên hoặc email.</span>
<label for="current_password">Mật khẩu hiện tại</label><input id="current_password" name="current_password" type="password" autocomplete="current-password">
<label for="password">Mật khẩu mới</label><input id="password" name="password" type="password" minlength="10" maxlength="255" autocomplete="new-password">
<label for="password_confirmation">Nhập lại mật khẩu mới</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
<button class="button button--black" type="submit">Lưu thay đổi</button>
</form>
@endsection
