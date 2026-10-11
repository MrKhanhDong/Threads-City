@extends('layouts.blog')
@section('title', 'Cài đặt blog')
@section('robots', 'noindex,nofollow')
@section('content')
<header class="page-heading"><h1>Góc của mình<span class="heading-dot">.</span></h1><p>Thông tin hiển thị ở trang chủ, giới thiệu và thanh bên.</p></header><x-admin-nav />
<form class="form-stack" method="post" enctype="multipart/form-data" action="{{ route('admin.settings.update') }}">@csrf @method('PATCH')
<label for="blog_title">Tên blog</label><input id="blog_title" name="blog_title" value="{{ old('blog_title', auth()->user()->blog_title) }}" maxlength="100" required>
<label for="headline">Dòng giới thiệu ngắn</label><input id="headline" name="headline" value="{{ old('headline', auth()->user()->headline) }}" maxlength="120">
<label for="bio">Mô tả ngắn</label><textarea id="bio" name="bio" rows="3" maxlength="500">{{ old('bio', auth()->user()->bio) }}</textarea>
<label for="about">Giới thiệu đầy đủ</label><textarea id="about" name="about" rows="9" maxlength="10000">{{ old('about', auth()->user()->about) }}</textarea><span class="field-hint">Văn bản thuần; dòng trống để tách đoạn.</span>
<label for="website">Website liên hệ (không bắt buộc)</label><input id="website" name="website" type="url" value="{{ old('website', auth()->user()->website) }}" maxlength="255" placeholder="https://...">
<label for="avatar">Ảnh đại diện JPG, PNG hoặc WebP (tối đa 2 MB)</label><input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp">
@if(auth()->user()->avatar_path)<img class="avatar settings-avatar" src="{{ route('media', ['path'=>auth()->user()->avatar_path]) }}" alt="Ảnh đại diện hiện tại"><label class="check-label"><input type="checkbox" name="remove_avatar" value="1"> Xóa ảnh hiện tại</label>@endif
<button class="button button--black" type="submit">Lưu cài đặt</button><a href="{{ route('account.edit') }}" class="text-link">Đổi tên, email hoặc mật khẩu tài khoản</a>
</form>
@endsection
