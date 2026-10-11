@extends('layouts.blog')
@section('title', 'Thùng rác bài viết')
@section('robots', 'noindex,nofollow')
@section('content')
<header class="admin-heading"><div><span class="overline">CHƯA MẤT ĐI, CHỈ TẠM CẤT LẠI</span><h1>Thùng rác<span class="heading-dot">.</span></h1></div><a class="button" href="{{ route('admin.posts.index') }}">Về thư viện bài</a></header>
<x-admin-nav />
<p class="flash">Bài trong thùng rác không xuất hiện ở blog, đọc sau hay thống kê. Ảnh, thẻ và bình luận được giữ nguyên. Khôi phục đưa bài về <strong>bản nháp</strong>; xóa vĩnh viễn mới xóa dữ liệu liên quan. Không tự động dọn thùng rác.</p>
<form method="get" class="filter-form"><label class="sr-only" for="trash-q">Tìm bài trong thùng rác</label><input id="trash-q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="200" placeholder="Tìm bài đã xóa…"><button class="button" type="submit">Tìm</button><a class="text-link" href="{{ route('admin.posts.trash') }}">Bỏ lọc</a></form>
<p class="muted">{{ $posts->total() }} bài trong kết quả</p>
<div class="table-wrap"><table class="admin-table"><thead><tr><th>Bài viết</th><th>Ngày đưa vào thùng rác</th><th>Dữ liệu đang giữ</th><th>Thao tác</th></tr></thead><tbody>
@forelse($posts as $item)
<tr><td><strong>{{ $item->title }}</strong><small>{{ $item->category->name }}</small></td><td>{{ $item->deleted_at->format('d/m/Y H:i') }}</td><td>{{ $item->views_count }} lượt xem<br>{{ $item->comments_count }} bình luận</td><td>
<form method="post" action="{{ route('admin.posts.restore', $item->id) }}">@csrf @method('PATCH')<button class="button" type="submit">Khôi phục về nháp</button></form>
<details class="permanent-delete"><summary>Xóa vĩnh viễn…</summary>
<form method="post" action="{{ route('admin.posts.force-destroy', $item->id) }}" data-confirm="Xóa vĩnh viễn bài này cùng ảnh, bình luận, lưu bài và thống kê? Không thể hoàn tác.">@csrf @method('DELETE')
<label class="check-label"><input type="checkbox" name="confirm_delete" value="1" required> Tôi hiểu không thể khôi phục</label><button class="button button--black" type="submit">Xóa vĩnh viễn</button>
</form></details>
</td></tr>
@empty<tr><td colspan="4">{{ empty($filters['q']) ? 'Thùng rác đang trống.' : 'Không tìm thấy bài phù hợp.' }}</td></tr>@endforelse
</tbody></table></div>
<x-pagination :items="$posts" />
@endsection
