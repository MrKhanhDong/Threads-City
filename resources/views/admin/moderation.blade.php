@extends('layouts.admin')
@php($pageTitle = 'Báo cáo vi phạm')
@section('content')
<div class="admin-page-heading"><div><p class="eyebrow">AN TOÀN CỘNG ĐỒNG</p><h1>Báo cáo vi phạm</h1><p class="muted">Xem xét báo cáo bài viết và tài khoản. Mỗi quyết định đều ghi lại quản trị viên xử lý.</p></div></div>
<section class="panel">
    <form method="get" class="media-search"><label for="report-status">Trạng thái</label><select name="status" id="report-status" class="form-select" data-auto-submit>@foreach(['pending' => 'Chờ xử lý', 'resolved' => 'Đã xử lý', 'dismissed' => 'Đã bác bỏ', 'all' => 'Tất cả'] as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select><button class="btn btn-outline-forest">Lọc báo cáo</button></form>
    @forelse($reports as $report)
    <article class="moderation-report">
        <header><h2>{{ $report->post_id ? 'Bài viết' : 'Tài khoản' }} · #{{ $report->id }}</h2><span class="status">{{ ['pending' => 'Chờ xử lý', 'resolved' => 'Đã xử lý', 'dismissed' => 'Đã bác bỏ'][$report->status] }}</span></header>
        <p><strong>{{ \App\Models\ContentReport::REASONS[$report->reason] ?? $report->reason }}</strong> · {{ $report->created_at->format('d/m/Y H:i') }}</p>
        <blockquote>{{ $report->snapshot }}</blockquote>
        @if($report->details)<p class="moderation-report-details">{{ $report->details }}</p>@endif
        <p class="muted">Người báo cáo: {{ $report->reporter?->name ?? 'Tài khoản đã xóa' }} · {{ $report->reporter?->email }}</p>
        @if($report->status === 'pending')
        <form method="post" action="{{ route('admin.moderation.update', $report) }}" data-confirm="Xác nhận quyết định xử lý báo cáo này?">
            @csrf @method('patch')
            <label for="report-action-{{ $report->id }}">Quyết định</label>
            <select class="form-select" id="report-action-{{ $report->id }}" name="action" required><option value="resolve">Đã xem xét, giữ nguyên nội dung</option>@if($report->post)<option value="hide_thread">Ẩn bài viết (chuyển vào thùng rác)</option>@endif @if(($report->reportedUser ?? $report->post?->author) && !($report->reportedUser ?? $report->post?->author)->isAdmin())<option value="lock_user">Khóa tài khoản vi phạm</option>@endif<option value="dismiss">Bác bỏ báo cáo</option></select>
            <label for="report-message-{{ $report->id }}">Phản hồi cho người báo cáo</label><textarea class="form-control" name="reporter_message" id="report-message-{{ $report->id }}" rows="3" maxlength="1000" placeholder="Giải thích kết quả xử lý cho người đã gửi báo cáo"></textarea><small class="muted">Kết quả và phản hồi này sẽ được gửi qua chuông thông báo của người báo cáo.</small>
            <label for="report-note-{{ $report->id }}">Ghi chú nội bộ (chỉ admin)</label><textarea class="form-control" name="admin_note" id="report-note-{{ $report->id }}" rows="2" maxlength="1000"></textarea>
            <button type="submit" class="btn btn-forest">Xác nhận xử lý</button>
        </form>
        @else<p class="muted">Xử lý bởi {{ $report->handler?->name ?? 'Quản trị viên' }} · {{ $report->handled_at?->format('d/m/Y H:i') }}</p>@if($report->reporter_message)<p><strong>Phản hồi cho người báo cáo:</strong> {{ $report->reporter_message }}</p>@endif @if($report->admin_note)<p><strong>Ghi chú nội bộ:</strong> {{ $report->admin_note }}</p>@endif @endif
    </article>
    @empty<p class="muted">Không có báo cáo trong trạng thái này.</p>@endforelse
    {{ $reports->links() }}
</section>
@endsection
