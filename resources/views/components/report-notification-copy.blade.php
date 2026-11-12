@props(['report'])
<span class="report-notification-copy"><strong>Báo cáo #{{ $report->id }} {{ $report->status === 'dismissed' ? 'đã được bác bỏ' : 'đã được xử lý' }}</strong><span>{{ $report->outcomeMessage() }}</span>@if($report->reporter_message)<span>Phản hồi từ quản trị viên: {{ $report->reporter_message }}</span>@endif</span>
