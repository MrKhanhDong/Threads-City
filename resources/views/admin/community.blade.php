@extends('layouts.admin')
@php($pageTitle = 'Kiểm duyệt bài đăng')
@section('content')
<div class="admin-page-heading"><div><p class="eyebrow">AN TOÀN CỘNG ĐỒNG</p><h1>Bài đăng & trả lời</h1><p class="muted">Xem ngữ cảnh, ẩn nội dung vi phạm hoặc khôi phục khi xử lý nhầm. Nội dung của tác giả được giữ nguyên.</p></div></div>
<section class="panel"><form method="get" class="filter-bar">
<x-field name="q" label="Nội dung hoặc tác giả" :value="request('q')" maxlength="150"/>
<div class="field"><label for="status">Trạng thái</label><select name="status" id="status" class="form-select">@foreach(['active'=>'Chưa bị xóa','hidden'=>'Admin đã ẩn','deleted'=>'Tác giả đã xóa','all'=>'Tất cả'] as $value=>$label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select></div>
<div class="field"><label for="kind">Loại</label><select name="kind" id="kind" class="form-select"><option value="">Tất cả</option><option value="post" @selected(request('kind') === 'post')>Bài đăng</option><option value="reply" @selected(request('kind') === 'reply')>Trả lời</option></select></div>
@if(request('author'))<input type="hidden" name="author" value="{{ request('author') }}">@endif
@if(request('id'))<input type="hidden" name="id" value="{{ request('id') }}">@endif
<x-field name="from" type="date" label="Từ ngày" :value="request('from')"/><x-field name="to" type="date" label="Đến ngày" :value="request('to')"/><button class="btn btn-forest">Lọc nội dung</button></form>
@forelse($posts as $post)<article class="moderation-item" id="post-{{ $post->id }}">
<div class="comment-heading"><strong>{{ $post->author?->name ?? 'Tài khoản đã xóa' }}</strong><span>#{{ $post->id }} · {{ $post->parent_id ? 'Trả lời' : 'Bài đăng' }}</span><span class="status">{{ $post->trashed() ? ($post->moderation_hidden_at ? 'Admin đã ẩn' : 'Tác giả đã xóa') : ($post->archived_at ? 'Tác giả đã lưu trữ' : $post->status) }}</span><small>{{ $post->created_at->format('d/m/Y H:i') }}</small></div>
@if($post->parent)<details><summary>Ngữ cảnh: trả lời bài #{{ $post->parent_id }}</summary><blockquote class="preserve-lines">{{ strip_tags($post->parent->content) }}</blockquote></details>@endif
@if($post->repostOf)<details><summary>Bài gốc #{{ $post->repost_of_id }}</summary><blockquote class="preserve-lines">{{ strip_tags($post->repostOf->content) }}</blockquote></details>@endif
<p class="preserve-lines">{{ strip_tags($post->content) }}</p>
@if($post->cover)<a href="{{ route('media.show', $post->cover) }}" target="_blank" rel="noopener">Xem ảnh bìa ↗</a>@endif
@foreach($post->media as $media)<p><a href="{{ route('media.show', $media) }}" target="_blank" rel="noopener">Xem tệp đính kèm: {{ $media->original_name }} ({{ $media->mime_type }}) ↗</a></p>@endforeach
<div class="moderation-actions"><a class="text-link" href="{{ route('admin.history.index', ['target_type'=>'post','target_id'=>$post->id]) }}">Lịch sử xử lý</a>@unless($post->trashed())<a class="text-link" href="{{ route('threads.show', $post->id) }}" target="_blank" rel="noopener">Xem trên website ↗</a>@endunless</div>
@if(!$post->trashed() || $post->moderation_hidden_at)<form method="post" action="{{ route('admin.community.update', $post->id) }}" class="moderation-actions" data-confirm="Xác nhận quyết định kiểm duyệt?">@csrf @method('patch')<input type="hidden" name="action" value="{{ $post->trashed() ? 'restore' : 'hide' }}"><div class="field"><label for="reason-{{ $post->id }}">Lý do xử lý (gửi cho tác giả)</label><input name="reason" id="reason-{{ $post->id }}" class="form-control" required maxlength="1000"></div><button class="btn btn-outline-forest">{{ $post->trashed() ? 'Khôi phục' : 'Ẩn nội dung' }}</button></form>@endif
</article>@empty<p class="muted">Không có nội dung phù hợp.</p>@endforelse{{ $posts->links() }}</section>
@endsection
