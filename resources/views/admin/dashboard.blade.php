@extends('layouts.admin')
@php($pageTitle = 'Tổng quan cộng đồng')
@section('content')
<div class="admin-page-heading admin-dashboard-heading">
    <div><p class="eyebrow">KHÔNG GIAN LÀM VIỆC</p><h1>Tổng quan cộng đồng</h1><p class="muted">Chào {{ auth()->user()->name }}. Theo dõi hoạt động và bắt đầu công việc tại đây.</p></div>
    <span class="admin-date-chip"><x-journal-icon name="chart" :size="17"/>{{ now()->format('d/m/Y') }}</span>
</div>

<section class="admin-metrics" aria-label="Quy mô cộng đồng">
    @foreach([
        ['Thành viên',$counts['users'],'admin.users.index','people','Tài khoản trong cộng đồng','violet'],
        ['Bài đăng',$counts['threads'],'admin.community.index','book','Bài gốc, đăng lại và trích dẫn','blue'],
        ['Trả lời',$counts['replies'],'admin.replies.index','message','Phản hồi dưới các bài đăng','teal'],
    ] as [$label,$number,$route,$icon,$description,$color])
        <a class="admin-metric is-{{ $color }}" href="{{ route($route) }}">
            <span class="admin-metric-top"><span class="admin-metric-icon"><x-journal-icon :name="$icon" :size="21"/></span><span>{{ $label }}</span><x-journal-icon name="arrow" :size="17"/></span>
            <strong>{{ number_format($number) }}</strong><small>{{ $description }}</small>
        </a>
    @endforeach
</section>

<div class="admin-dashboard-work">
    <section class="panel admin-priority-panel">
        <div class="section-heading"><h2>Cần xem xét</h2><span class="admin-section-caption">Kiểm duyệt cộng đồng</span></div>
        <a href="{{ route('admin.moderation.index') }}" @class(['admin-report-priority', 'has-reports' => $counts['content_reports'] > 0])>
            <span class="admin-priority-icon"><x-journal-icon name="info" :size="25"/></span>
            <span><strong>{{ number_format($counts['content_reports']) }} báo cáo chờ xử lý</strong><small>{{ $counts['content_reports'] > 0 ? 'Xem nội dung được báo cáo và phản hồi thành viên.' : 'Không có báo cáo đang chờ. Tiếp tục theo dõi cộng đồng.' }}</small></span>
            <span class="admin-priority-link">Xem báo cáo<x-journal-icon name="arrow" :size="17"/></span>
        </a>
        <div class="admin-review-shortcuts">
            <a href="{{ route('admin.users.index', ['status'=>'locked']) }}"><span class="admin-shortcut-icon"><x-journal-icon name="person" :size="19"/></span><span><strong>{{ number_format($counts['locked_users']) }} tài khoản đang khóa</strong><small>Kiểm tra lý do và thời hạn khóa</small></span><x-journal-icon name="chevron" :size="15"/></a>
            <a href="{{ route('admin.community.index', ['status'=>'hidden']) }}"><span class="admin-shortcut-icon"><x-journal-icon name="archive" :size="19"/></span><span><strong>{{ number_format($counts['hidden']) }} bài đăng đã ẩn</strong><small>Xem lại quyết định kiểm duyệt</small></span><x-journal-icon name="chevron" :size="15"/></a>
        </div>
        @if($counts['pending_comments'] > 0)<p class="admin-legacy-note"><x-journal-icon name="info" :size="16"/><a href="{{ route('admin.comments.index', ['status'=>'pending']) }}">{{ $counts['pending_comments'] }} bình luận blog cũ đang chờ duyệt</a></p>@endif
    </section>
    <section class="panel admin-growth-panel">
        <div class="section-heading"><h2>Thành viên mới</h2><span class="admin-growth-icon"><x-journal-icon name="people" :size="20"/></span></div>
        <div class="admin-growth-total"><strong>{{ number_format($counts['new_users']) }}</strong><span>tài khoản tham gia<br>trong {{ $days }} ngày gần đây</span></div>
        <form method="get" class="admin-period-form"><label class="visually-hidden" for="days">Khoảng thống kê tài khoản mới</label><select id="days" name="days" class="form-select">@foreach([7,30,90] as $range)<option value="{{ $range }}" @selected($days === $range)>{{ $range }} ngày gần đây</option>@endforeach</select><button class="btn btn-outline-forest">Cập nhật</button></form>
        <a class="admin-growth-link" href="{{ route('admin.users.index') }}">Quản lý thành viên<x-journal-icon name="arrow" :size="16"/></a>
    </section>
</div>

<div class="admin-dashboard-bottom">
    <section class="panel admin-recent-panel">
        <div class="section-heading"><div><h2>Nội dung gần đây</h2><p class="admin-section-description">Những bài đăng và trả lời vừa cập nhật</p></div><a class="admin-inline-link" href="{{ route('admin.community.index') }}">Xem tất cả<x-journal-icon name="arrow" :size="15"/></a></div>
        @forelse($latestPosts as $post)
            <a class="dashboard-list-item" href="{{ route($post->isReply() ? 'admin.replies.index' : 'admin.community.index', ['id'=>$post->id,'status'=>'all']) }}#post-{{ $post->id }}">
                <span class="admin-activity-icon"><x-journal-icon :name="$post->isReply() ? 'message' : 'book'" :size="19"/></span>
                <span class="admin-post-summary"><small>{{ $post->isReply() ? 'Trả lời' : 'Bài đăng' }} <span>· #{{ $post->id }}</span></small><span>{{ \Illuminate\Support\Str::limit(trim(strip_tags($post->content)), 100) ?: 'Nội dung đính kèm · Xem chi tiết' }}</span></span>
                <time datetime="{{ $post->updated_at->toIso8601String() }}">{{ $post->updated_at->format('d/m H:i') }}</time><x-journal-icon name="chevron" :size="14"/>
            </a>
        @empty<div class="admin-empty is-compact"><x-journal-icon name="book" :size="30"/><p>Chưa có nội dung để hiển thị.</p></div>@endforelse
    </section>
    <section class="panel admin-workspaces">
        <div class="section-heading"><h2>Danh mục quản lý</h2><x-journal-icon name="settings" :size="18"/></div>
        @foreach([
            ['admin.community.index','book','Bài đăng','Bài gốc, đăng lại, trích dẫn'],
            ['admin.replies.index','message','Trả lời','Phản hồi và ngữ cảnh bài gốc'],
            ['admin.users.index','people','Người dùng','Tài khoản, trạng thái, tích xanh'],
            ['admin.history.index','repost','Lịch sử xử lý','Theo dõi các quyết định quản trị'],
        ] as [$route,$icon,$label,$description])
            <a href="{{ route($route) }}"><span class="admin-workspace-icon"><x-journal-icon :name="$icon" :size="20"/></span><span><strong>{{ $label }}</strong><small>{{ $description }}</small></span><x-journal-icon name="chevron" :size="15"/></a>
        @endforeach
    </section>
</div>
@endsection
