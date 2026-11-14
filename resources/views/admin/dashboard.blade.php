@extends('layouts.admin')
@php($pageTitle = 'Tổng quan cộng đồng')
@section('content')
<div class="admin-page-heading"><div><p class="eyebrow">QUẢN TRỊ CỘNG ĐỒNG</p><h1>Chào {{ auth()->user()->name }}.</h1><p class="muted">Theo dõi hoạt động và các nội dung cần xử lý.</p></div><a class="btn btn-forest" href="{{ route('admin.moderation.index') }}">Xử lý báo cáo</a></div>
<form method="get" class="filter-bar admin-range-filter"><label for="days">Khoảng thống kê tài khoản mới</label><select id="days" name="days" class="form-select">@foreach([7,30,90] as $range)<option value="{{ $range }}" @selected($days === $range)>{{ $range }} ngày</option>@endforeach</select><button class="btn btn-outline-forest">Cập nhật</button></form>
<div class="stats-grid">@foreach([
['Người dùng',$counts['users'],'admin.users.index',[]],
['Tài khoản mới trong '.$days.' ngày',$counts['new_users'],'admin.users.index',[]],
['Tài khoản đang khóa',$counts['locked_users'],'admin.users.index',['status'=>'locked']],
['Bài đăng',$counts['threads'],'admin.community.index',['kind'=>'post']],
['Trả lời',$counts['replies'],'admin.community.index',['kind'=>'reply']],
['Bình luận',$counts['comments'],'admin.comments.index',[]],
['Báo cáo nội dung chờ xử lý',$counts['content_reports'],'admin.moderation.index',[]],
['Bài đăng / trả lời bị ẩn',$counts['hidden'],'admin.community.index',['status'=>'hidden']],
] as [$label,$number,$route,$params])<a class="stat-card" href="{{ route($route,$params) }}"><span>{{ $label }}</span><strong>{{ number_format($number) }}</strong><small>Xem chi tiết ↗</small></a>@endforeach</div>
@if($counts['pending_comments'] > 0)<p><a class="text-link" href="{{ route('admin.comments.index', ['status'=>'pending']) }}">{{ $counts['pending_comments'] }} bình luận cũ đang chờ duyệt</a>. Bình luận mới hiển thị ngay, không cần duyệt trước.</p>@endif
<section class="panel"><div class="section-heading"><h2>Nội dung cập nhật gần đây</h2><a class="text-link" href="{{ route('admin.community.index') }}">Xem tất cả ↗</a></div>@forelse($latestPosts as $post)<a class="dashboard-list-item" href="{{ route('admin.community.index', ['id'=>$post->id,'status'=>'all']) }}#post-{{ $post->id }}"><span>{{ \Illuminate\Support\Str::limit(strip_tags($post->content), 120) }}</span><small>{{ $post->updated_at->format('d/m/Y H:i') }}</small></a>@empty<p class="muted">Chưa có bài đăng.</p>@endforelse</section>
@endsection