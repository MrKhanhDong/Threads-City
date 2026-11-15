@extends('layouts.admin')
@php
    $pageTitle = 'Lịch sử xử lý';
    $targetLabels = ['user' => 'Tài khoản', 'post' => 'Nội dung', 'comment' => 'Bình luận blog cũ', 'report' => 'Báo cáo nội dung', 'comment_report' => 'Báo cáo bình luận blog', 'settings' => 'Cài đặt cộng đồng'];
@endphp
@section('content')
<div class="admin-page-heading"><div><p class="eyebrow">NHẬT KÝ QUẢN TRỊ</p><h1>Lịch sử xử lý</h1><p class="muted">Tra cứu người xử lý, quyết định và lý do của từng thao tác quản trị.</p></div><span class="admin-result-count"><x-journal-icon name="repost" :size="17"/><strong>{{ number_format($logs->total()) }}</strong> thao tác</span></div>
<section class="panel admin-filter-panel">
    <details class="admin-filter-disclosure" data-admin-filter-disclosure data-has-filters="{{ request()->filled('target_type') || request()->filled('target_id') || request()->filled('action') ? 'true' : 'false' }}" open>
    <summary><x-journal-icon name="filter" :size="18"/><span>Tra cứu lịch sử xử lý</span><x-journal-icon name="chevron" :size="15"/></summary>
    <form method="get" class="admin-filter-grid is-history">
        <div class="field"><label for="target_type">Đối tượng</label><select id="target_type" name="target_type" class="form-select"><option value="">Tất cả đối tượng</option>@foreach($targetLabels as $value => $label)<option value="{{ $value }}" @selected(request('target_type') === $value)>{{ $label }}</option>@endforeach</select></div>
        <x-field name="target_id" type="number" label="ID đối tượng" :value="request('target_id')" min="1" placeholder="Ví dụ: 123"/>
        <div class="field"><label for="action">Thao tác</label><select name="action" id="action" class="form-select"><option value="">Tất cả thao tác</option>@foreach($actions as $value => $label)<option value="{{ $value }}" @selected(request('action') === $value)>{{ $label }}</option>@endforeach</select></div>
        <button class="btn btn-forest"><x-journal-icon name="search" :size="17"/>Lọc lịch sử</button>
    </form>
    @if(request()->filled('target_type') || request()->filled('target_id') || request()->filled('action'))<div class="admin-filter-summary"><span>{{ number_format($logs->total()) }} kết quả phù hợp</span><a href="{{ route('admin.history.index') }}">Xóa bộ lọc</a></div>@endif
    </details>
</section>
<ol class="admin-history-list">
    @forelse($logs as $log)
        <li><span class="admin-history-marker" aria-hidden="true"><x-journal-icon name="repost" :size="14"/></span><article class="panel admin-record-card">
            <header class="admin-record-header"><div><h2 class="admin-record-title">{{ $actions[$log->action] ?? $log->action }}</h2><p class="admin-record-meta">{{ $log->admin_name ?? 'Quản trị viên đã xóa' }}<span>·</span>{{ $targetLabels[$log->target_type] ?? $log->target_type }} #{{ $log->target_id }}</p></div><time class="admin-record-time">{{ \Illuminate\Support\Carbon::parse($log->created_at)->format('d/m/Y H:i') }}</time></header>
            <div class="admin-history-reason"><span>Lý do xử lý</span><p class="preserve-lines">{{ $log->reason }}</p></div>
            @if($log->target_type === 'post')<div class="admin-record-links"><a href="{{ route('admin.community.index', ['id' => $log->target_id, 'status' => 'all']) }}"><x-journal-icon name="book" :size="15"/>Xem nội dung</a></div>@endif
            <details class="admin-context admin-state-details"><summary><span>Chi tiết thay đổi</span><x-journal-icon name="chevron" :size="14"/></summary><div class="admin-state-comparison"><div><h3>Trước xử lý</h3><pre>{{ json_encode(json_decode($log->before_state), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></div><div><h3>Sau xử lý</h3><pre>{{ json_encode(json_decode($log->after_state), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></div></div></details>
        </article></li>
    @empty
        <li class="admin-history-empty"><section class="panel admin-empty"><x-journal-icon name="repost" :size="34"/><h2>Chưa có thao tác phù hợp</h2><p>Những quyết định quản trị sẽ được ghi lại tại đây.</p></section></li>
    @endforelse
</ol>
<div class="admin-pagination">{{ $logs->links() }}</div>
@endsection
