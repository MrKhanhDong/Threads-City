@extends('layouts.blog')
@section('title', 'Quản lý độc giả')
@section('robots', 'noindex,nofollow')
@section('content')
<header class="page-heading"><h1>Độc giả<span class="heading-dot">.</span></h1><p>Tài khoản độc giả chỉ được đọc, lưu bài và bình luận; không có quyền đăng bài.</p></header><x-admin-nav />
<div class="table-wrap"><table class="admin-table"><thead><tr><th>Tài khoản</th><th>Vai trò</th><th>Bình luận</th><th>Thao tác</th></tr></thead><tbody>
@foreach($users as $item)<tr><td><strong>{{ $item->name }}</strong><small>{{ $item->email }}</small></td><td><span class="status-chip">{{ $item->role === 'admin' ? 'Chủ blog' : ($item->is_locked ? 'Độc giả bị khóa' : 'Độc giả') }}</span></td><td>{{ $item->comments_count }}</td><td>
@if($item->role !== 'admin')<form method="post" action="{{ route('admin.users.update', $item) }}" data-confirm="{{ $item->is_locked ? 'Mở khóa tài khoản này?' : 'Khóa tài khoản và chặn các phiên đăng nhập của độc giả này?' }}">@csrf @method('PATCH')<input type="hidden" name="is_locked" value="{{ $item->is_locked ? 0 : 1 }}"><button class="text-button" type="submit">{{ $item->is_locked ? 'Mở khóa' : 'Khóa tài khoản' }}</button></form>@else<span class="muted">Không thể khóa</span>@endif
</td></tr>@endforeach
</tbody></table></div><x-pagination :items="$users" />
@endsection
