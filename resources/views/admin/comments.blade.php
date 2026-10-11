@extends('layouts.blog')
@section('title', 'Duyệt bình luận')
@section('robots', 'noindex,nofollow')
@section('content')
<header class="page-heading"><h1>Lời nhắn của độc giả<span class="heading-dot">.</span></h1><p>Duyệt trước khi hiển thị để giữ góc blog lịch sự, đúng chủ đề.</p></header><x-admin-nav />
<div class="filter-pills"><a class="pill" href="{{ route('admin.comments.index') }}">Tất cả</a>@foreach(['pending'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Từ chối'] as $key=>$label)<a @class(['pill','is-active'=>request('status') === $key]) href="{{ route('admin.comments.index', ['status'=>$key]) }}">{{ $label }}</a>@endforeach</div>
@forelse($comments as $item)<article class="management-box" id="comment-{{ $item->id }}"><div class="comment-top"><strong>{{ $item->user->name }}</strong><small>{{ $item->created_at->format('d/m/Y H:i') }}</small></div><a class="text-link" href="{{ route('admin.posts.preview', $item->post) }}">{{ $item->post->title }}</a><p class="preserve-lines">{{ $item->body }}</p>
<div class="row-actions"><form class="inline-form" method="post" action="{{ route('admin.comments.update', $item) }}">@csrf @method('PATCH')<label class="sr-only" for="status-{{ $item->id }}">Trạng thái bình luận</label><select id="status-{{ $item->id }}" name="status">@foreach(['pending'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Từ chối'] as $key=>$label)<option value="{{ $key }}" @selected($item->status === $key)>{{ $label }}</option>@endforeach</select><button class="button" type="submit">Cập nhật</button></form>
<form method="post" action="{{ route('admin.comments.destroy', $item) }}" data-confirm="Xóa vĩnh viễn bình luận này?">@csrf @method('DELETE')<button class="text-button" type="submit">Xóa</button></form></div></article>
@empty<p class="empty-card">Không có bình luận phù hợp.</p>@endforelse
<x-pagination :items="$comments" />
@endsection
