@extends('layouts.blog')
@section('title', 'Quản lý bài viết')
@section('robots', 'noindex,nofollow')
@section('content')
<header class="admin-heading"><h1>Bài viết<span class="heading-dot">.</span></h1><a class="button button--black" href="{{ route('admin.editor') }}">Viết bài mới</a></header><x-admin-nav />
<div class="library-note"><span class="muted">Xóa bài sẽ chuyển vào thùng rác, chưa xóa dữ liệu.</span><a class="button" href="{{ route('admin.posts.trash') }}">Thùng rác · {{ $trashCount }}</a></div>
<form method="get" class="filter-form"><label class="sr-only" for="q">Tìm bài viết</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="200" placeholder="Tìm theo tiêu đề…"><label class="sr-only" for="status">Trạng thái</label><select id="status" name="status"><option value="">Mọi trạng thái</option>@foreach(['draft'=>'Bản nháp','published'=>'Xuất bản','archived'=>'Lưu trữ'] as $value=>$label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select><button class="button" type="submit">Lọc</button><a class="text-link" href="{{ route('admin.posts.index') }}">Bỏ lọc</a></form>
<div class="table-wrap"><table class="admin-table"><thead><tr><th>Bài viết</th><th>Trạng thái</th><th>Lượt xem / lời nhắn</th><th>Thao tác</th></tr></thead><tbody>
@forelse($posts as $item)<tr><td><strong>{{ $item->title }}</strong><small>{{ $item->category->name }} · {{ $item->updated_at->format('d/m/Y H:i') }}</small></td><td><span class="status-chip">{{ ['draft'=>'Bản nháp','published'=>'Xuất bản','archived'=>'Lưu trữ'][$item->status] }}</span></td><td>{{ $item->views_count }} / {{ $item->comments_count }}</td><td><div class="row-actions"><a href="{{ route('admin.posts.edit', $item) }}">Sửa</a><a href="{{ route('admin.posts.preview', $item) }}">Xem</a><form action="{{ route('admin.posts.destroy', $item) }}" method="post" data-confirm="Chuyển bài vào thùng rác? Bài sẽ ẩn khỏi blog, nhưng có thể khôi phục.">@csrf @method('DELETE')<button class="text-button" type="submit">Vào thùng rác</button></form></div></td></tr>
@empty<tr><td colspan="4">Không có bài viết phù hợp.</td></tr>@endforelse
</tbody></table></div><x-pagination :items="$posts" />
@endsection
