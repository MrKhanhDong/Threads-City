@extends('layouts.blog')
@section('title', 'Quản lý chuyên mục')
@section('robots', 'noindex,nofollow')
@section('content')
<header class="page-heading"><h1>Chuyên mục<span class="heading-dot">.</span></h1><p>Ẩn chuyên mục sẽ ẩn mọi bài viết thuộc chuyên mục đó khỏi blog công khai.</p></header><x-admin-nav />
<details class="management-box" open><summary>Thêm chuyên mục</summary><form class="form-stack" method="post" action="{{ route('admin.categories.store') }}">@csrf
<div class="form-grid"><label>Tên<input name="name" value="{{ old('name') }}" maxlength="100" required></label><label>Slug<input name="slug" value="{{ old('slug') }}" maxlength="120" placeholder="Tự tạo nếu để trống"></label></div>
<label>Mô tả<textarea name="description" maxlength="500" rows="2">{{ old('description') }}</textarea></label>
<div class="form-grid"><label>Biểu tượng<select name="icon">@foreach(['book'=>'Sách','code'=>'Lập trình','layers'=>'Thiết kế','coffee'=>'Cuộc sống'] as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label><label>Hiển thị<select name="is_active"><option value="1">Đang hoạt động</option><option value="0">Ẩn khỏi blog</option></select></label></div><button class="button button--black" type="submit">Thêm chuyên mục</button></form></details>
@foreach($items as $item)<details class="management-box"><summary>{{ $item->name }} <span class="muted">· {{ $item->posts_count }} bài · {{ $item->is_active ? 'Đang hoạt động' : 'Đang ẩn' }}</span></summary>
<form class="form-stack" method="post" action="{{ route('admin.categories.update', $item) }}">@csrf @method('PATCH')
<div class="form-grid"><label>Tên<input name="name" value="{{ $item->name }}" maxlength="100" required></label><label>Slug<input name="slug" value="{{ $item->slug }}" maxlength="120" required></label></div><label>Mô tả<textarea name="description" rows="2" maxlength="500">{{ $item->description }}</textarea></label>
<div class="form-grid"><label>Biểu tượng<select name="icon">@foreach(['book'=>'Sách','code'=>'Lập trình','layers'=>'Thiết kế','coffee'=>'Cuộc sống'] as $key=>$label)<option value="{{ $key }}" @selected($item->icon === $key)>{{ $label }}</option>@endforeach</select></label><label>Hiển thị<select name="is_active"><option value="1" @selected($item->is_active)>Đang hoạt động</option><option value="0" @selected(!$item->is_active)>Ẩn khỏi blog</option></select></label></div><button class="button" type="submit">Lưu thay đổi</button></form>
<form method="post" action="{{ route('admin.categories.destroy', $item) }}" data-confirm="Xóa chuyên mục trống này?">@csrf @method('DELETE')<button class="text-button" type="submit">Xóa chuyên mục (chỉ khi không còn bài)</button></form>
</details>@endforeach
@endsection
