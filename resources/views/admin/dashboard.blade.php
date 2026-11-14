@extends('layouts.admin')
@php($pageTitle = 'Tổng quan cộng đồng')
@section('content')
<div class="admin-page-heading"><div><p class="eyebrow">QUẢN TRỊ CỘNG ĐỒNG</p><h1>Chào {{ auth()->user()->name }}.</h1><p class="muted">Theo dõi hoạt động và các nội dung cần xử lý.</p></div><a class="btn btn-forest" href="{{ route('admin.moderation.index') }}">Xử lý báo cáo</a></div>
<form method="get" class="filter-bar admin-range-filter"><label for="days">Khoảng thống kê tài khoản mới</label><select id="days" name="days" class="form-select">@foreach([7,30,90] as $range)<option value="{{ $range }}" @selected($days === $range)>{{ $range }} ngày</option>@endforeach</select><button class="btn btn-outline-forest">Cập nhật</button></form>
<div class="stats-grid">@foreach([
['Người dùng',$counts['users'],'admin.users.index',[],'people'],
['Tài khoản mới trong '.$days.' ngày',$counts['new_users'],'admin.users.index',[],'person'],
['Tài khoản đang khóa',$counts['locked_users'],'admin.users.index',['status'=>'locked'],'archive'],
['Bài đăng',$counts['threads'],'admin.community.index',['kind'=>'post'],'book'],
['Trả lời',$counts['replies'],'admin.community.index',['kind'=>'reply'],'message'],
['Bình luận',$counts['comments'],'admin.comments.index',[],'message'],
['Báo cáo nội dung chờ xử lý',$counts['content_reports'],'admin.moderation.index',[],'info'],
['Bài đăng / trả lời bị ẩn',$counts['hidden'],'admin.community.index',['status'=>'hidden'],'archive'],
] as [$label,$number,$route,$params,$icon])<a class="stat-card" href="{{ route($route,$params) }}"><span class="stat-card-heading"><span>{{ $label }}</span><x-journal-icon :name="$icon" :size="19"/></span><strong>{{ number_format($number) }}</strong><small>Xem chi tiết <x-journal-icon name="arrow" :size="14"/></small></a>@endforeach</div>
@if($counts['pending_comments'] > 0)<p><a class="text-link" href="{{ route('admin.comments.index', ['status'=>'pending']) }}">{{ $counts['pending_comments'] }} bình luận cũ đang chờ duyệt</a>. Bình luận mới hiển thị ngay, không cần duyệt trước.</p>@endif
<div class="admin-overview-grid">
<section class="panel"><div class="section-heading"><h2>Nội dung cập nhật gần đây</h2><a class="text-link" href="{{ route('admin.community.index') }}">Xem tất cả ↗</a></div>@forelse($latestPosts as $post)<a class="dashboard-list-item" href="{{ route('admin.community.index', ['id'=>$post->id,'status'=>'all']) }}#post-{{ $post->id }}"><span class="admin-post-summary"><small>{{ $post->parent_id ? 'Trả lời' : 'Bài đăng' }} · #{{ $post->id }}</small><span>{{ \Illuminate\Support\Str::limit(trim(strip_tags($post->content)), 100) ?: 'Nội dung đính kèm · Xem chi tiết' }}</span></span><small>{{ $post->updated_at->format('d/m H:i') }}</small></a>@empty<div class="empty-state"><x-journal-icon name="book" :size="28"/><p>Chưa có bài đăng để hiển thị.</p></div>@endforelse</section>
<section class="panel admin-quick-links"><div class="section-heading"><h2>Truy cập nhanh</h2><x-journal-icon name="settings" :size="19"/></div><a href="{{ route('admin.moderation.index') }}"><x-journal-icon name="info"/><span><strong>Báo cáo cần xử lý</strong><small>{{ $counts['content_reports'] }} báo cáo đang chờ</small></span><x-journal-icon name="chevron" :size="16"/></a><a href="{{ route('admin.users.index') }}"><x-journal-icon name="people"/><span><strong>Thành viên cộng đồng</strong><small>Trạng thái tài khoản và xác minh</small></span><x-journal-icon name="chevron" :size="16"/></a><a href="{{ route('admin.history.index') }}"><x-journal-icon name="repost"/><span><strong>Lịch sử xử lý</strong><small>Xem quyết định và lý do kiểm duyệt</small></span><x-journal-icon name="chevron" :size="16"/></a></section>
</div>
@endsection
