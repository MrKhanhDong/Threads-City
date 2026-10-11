@extends('layouts.blog')
@section('title', 'Đọc sau')
@section('content')
    <header class="page-heading"><span class="overline">ĐỂ DÀNH CHO MỘT KHOẢNG RẢNH</span><h1>Đọc sau<span class="heading-dot">.</span></h1><p>Bài viết được lưu vào tài khoản của bạn, đồng bộ khi đăng nhập ở thiết bị khác.</p></header>
    <div class="post-list">
        @forelse($posts as $post)<x-post-card :post="$post" />@empty
            <div class="empty-state"><x-icon name="bookmark" size="36" /><h2>Một kệ sách nhỏ đang chờ bạn</h2><p>Nhấn biểu tượng lưu bên cạnh bài viết để thêm vào đây.</p><a class="button button--black" href="{{ route('posts.index') }}">Khám phá bài viết <x-icon name="arrow" size="17" /></a></div>
        @endforelse
    </div>
    <x-pagination :paginator="$posts" />
@endsection
