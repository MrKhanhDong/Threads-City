@extends('layouts.admin')
@php
    $pageTitle = 'Báo cáo vi phạm';
    $reasons = \App\Models\ContentReport::reasons();
    $statusLabels = ['pending' => 'Chờ xử lý', 'resolved' => 'Đã xử lý', 'dismissed' => 'Đã bác bỏ'];
@endphp
@section('content')
<div class="admin-page-heading"><div><p class="eyebrow">AN TOÀN CỘNG ĐỒNG</p><h1>Báo cáo vi phạm</h1><p class="muted">Xem xét nội dung được báo cáo và phản hồi rõ ràng cho thành viên.</p></div><span class="admin-result-count"><x-journal-icon name="info" :size="17"/><strong>{{ number_format($reports->total()) }}</strong> báo cáo</span></div>
<section class="panel admin-filter-panel">
    <div class="admin-report-toolbar"><div><h2>Danh sách báo cáo</h2><p>Lọc theo trạng thái để ưu tiên các trường hợp cần xử lý.</p></div><form method="get" class="admin-report-filter"><div class="field"><label for="report-status">Trạng thái</label><select name="status" id="report-status" class="form-select" data-auto-submit>@foreach($statusLabels + ['all' => 'Tất cả'] as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select></div><button class="btn btn-forest">Lọc báo cáo</button></form></div>
</section>
<div class="admin-record-list">
    @forelse($reports as $report)
        @php($targetLabel = $report->post_id ? ($report->post?->isReply() ? 'Trả lời' : 'Bài đăng') : 'Tài khoản')
        <article class="panel admin-record-card">
            <header class="admin-record-header"><div class="admin-record-identity"><span class="admin-record-symbol"><x-journal-icon name="info" :size="21"/></span><div><h2 class="admin-record-title">{{ $reasons[$report->reason] ?? $report->reason }}</h2><p class="admin-record-meta">{{ $targetLabel }} <span>·</span> Báo cáo #{{ $report->id }}</p></div></div><div class="admin-record-state"><span @class(['admin-state', 'is-warning' => $report->status === 'pending', 'is-success' => $report->status === 'resolved', 'is-muted' => $report->status === 'dismissed'])><i aria-hidden="true"></i>{{ $statusLabels[$report->status] ?? $report->status }}</span><time>{{ $report->created_at->format('d/m/Y H:i') }}</time></div></header>
            <blockquote class="admin-report-snapshot preserve-lines">{{ $report->snapshot }}</blockquote>
            @if($report->details)<p class="admin-report-details preserve-lines">{{ $report->details }}</p>@endif
            <p class="admin-record-meta">Người báo cáo: <strong>{{ $report->reporter?->name ?? 'Tài khoản đã xóa' }}</strong>@if($report->reporter?->email)<span>·</span>{{ $report->reporter->email }}@endif</p>
            <div class="admin-record-links">
                @if($report->post)<a href="{{ route($report->post->isReply() ? 'admin.replies.index' : 'admin.community.index', ['id' => $report->post_id, 'status' => 'all']) }}#post-{{ $report->post_id }}"><x-journal-icon name="book" :size="15"/>Xem nội dung và ngữ cảnh</a>@endif
                @if($report->reportedUser)<a href="{{ route('admin.users.index', ['q' => $report->reportedUser->email]) }}"><x-journal-icon name="people" :size="15"/>Xem tài khoản bị báo cáo</a>@endif
                <a href="{{ route('admin.history.index', ['target_type' => 'report', 'target_id' => $report->id]) }}"><x-journal-icon name="repost" :size="15"/>Lịch sử báo cáo</a>
            </div>
            @if($report->status === 'pending')
                <details class="admin-review" open>
                    <summary>Quyết định xử lý<x-journal-icon name="chevron" :size="15"/></summary>
                    <form method="post" action="{{ route('admin.moderation.update', $report) }}" data-confirm="Xác nhận quyết định xử lý báo cáo này?" class="admin-review-form">
                        @csrf @method('patch')
                        <div class="admin-review-grid">
                            <div class="field"><label for="report-action-{{ $report->id }}">Quyết định</label><select class="form-select" id="report-action-{{ $report->id }}" name="action" required><option value="resolve">Đã xem xét, giữ nguyên nội dung</option>@if($report->post && !$report->post->trashed())<option value="hide_thread">Ẩn {{ $report->post->isReply() ? 'trả lời' : 'bài viết' }} vi phạm</option>@endif @if(($report->reportedUser ?? $report->post?->author) && !($report->reportedUser ?? $report->post?->author)->isAdmin())<option value="lock_user">Khóa tài khoản vi phạm</option>@endif<option value="dismiss">Bác bỏ báo cáo</option></select></div>
                            <div class="field"><label for="report-expiry-{{ $report->id }}">Thời hạn nếu khóa tài khoản</label><input class="form-control" type="datetime-local" name="lock_expires_at" id="report-expiry-{{ $report->id }}" aria-describedby="report-expiry-help-{{ $report->id }}"></div>
                            <p class="admin-review-hint" id="report-expiry-help-{{ $report->id }}">Để trống nếu khóa vô thời hạn. Múi giờ: {{ config('app.timezone') }}.</p>
                        </div>
                        <div class="field"><label for="action-reason-{{ $report->id }}">Lý do gửi cho tác giả / tài khoản bị xử lý</label><textarea class="form-control" name="action_reason" id="action-reason-{{ $report->id }}" rows="2" maxlength="1000" placeholder="Bắt buộc khi ẩn nội dung hoặc khóa tài khoản"></textarea></div>
                        <div class="admin-review-grid">
                            <div class="field"><label for="report-message-{{ $report->id }}">Phản hồi cho người báo cáo</label><textarea class="form-control" name="reporter_message" required id="report-message-{{ $report->id }}" rows="3" maxlength="1000" placeholder="Giải thích kết quả xử lý…"></textarea><small>Được gửi qua thông báo của người báo cáo.</small></div>
                            <div class="field"><label for="report-note-{{ $report->id }}">Ghi chú nội bộ</label><textarea class="form-control" name="admin_note" required id="report-note-{{ $report->id }}" rows="3" maxlength="1000" placeholder="Ghi nhận căn cứ và quyết định…"></textarea><small>Chỉ quản trị viên xem được.</small></div>
                        </div>
                        <div class="admin-review-footer"><span>Kiểm tra kỹ nội dung trước khi xác nhận.</span><button class="btn btn-forest" type="submit">Xác nhận xử lý</button></div>
                    </form>
                </details>
            @else
                <div class="admin-review-result"><p class="admin-record-meta">Xử lý bởi <strong>{{ $report->handler?->name ?? 'Quản trị viên' }}</strong><span>·</span>{{ $report->handled_at?->format('d/m/Y H:i') }}</p>@if($report->reporter_message)<div><span>Phản hồi cho người báo cáo</span><p class="preserve-lines">{{ $report->reporter_message }}</p></div>@endif @if($report->admin_note)<div><span>Ghi chú nội bộ</span><p class="preserve-lines">{{ $report->admin_note }}</p></div>@endif</div>
            @endif
        </article>
    @empty
        <section class="panel admin-empty"><x-journal-icon name="info" :size="34"/><h2>Không có báo cáo phù hợp</h2><p>Các báo cáo trong trạng thái đã chọn sẽ xuất hiện tại đây.</p></section>
    @endforelse
</div>
<div class="admin-pagination">{{ $reports->links() }}</div>
@endsection
