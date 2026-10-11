@extends('layouts.blog')
@section('title', 'Quản lý thẻ')
@section('robots', 'noindex,nofollow')
@section('content')
<header class="page-heading"><h1>Thẻ bài viết<span class="heading-dot">.</span></h1><p>Những từ khóa nhỏ để độc giả tìm các bài liên quan.</p></header><x-admin-nav />
<form class="form-stack management-box" method="post" action="{{ route('admin.tags.store') }}">@csrf
<div class="form-grid"><label>Tên thẻ<input name="name" value="{{ old('name') }}" maxlength="50" required></label><label>Slug<input name="slug" value="{{ old('slug') }}" maxlength="80" placeholder="Tự tạo nếu để trống"></label></div><button class="button button--black" type="submit">Thêm thẻ</button></form>
@foreach($items as $item)<details class="management-box"><summary>{{ $item->name }} <span class="muted">· {{ $item->posts_count }} bài</span></summary>
<form class="form-stack" method="post" action="{{ route('admin.tags.update', $item) }}">@csrf @method('PATCH')<div class="form-grid"><label>Tên thẻ<input name="name" value="{{ $item->name }}" maxlength="50" required></label><label>Slug<input name="slug" value="{{ $item->slug }}" maxlength="80" required></label></div><button class="button" type="submit">Lưu thay đổi</button></form>
<form method="post" action="{{ route('admin.tags.destroy', $item) }}" data-confirm="Xóa thẻ này khỏi tất cả bài viết?">@csrf @method('DELETE')<button class="text-button" type="submit">Xóa thẻ</button></form></details>@endforeach
@endsection
