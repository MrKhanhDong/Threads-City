@extends('layouts.public')
@php($pageTitle = 'Kết quả báo cáo #'.$report->id)
@section('content')
<div class="safety-page">
    <header class="safety-heading"><h1>Báo cáo #{{ $report->id }}</h1><p>Gửi lúc {{ $report->created_at->format('d/m/Y H:i') }}</p></header>
    <section class="safety-panel">
        <h2>{{ ['pending' => 'Đang chờ xử lý', 'resolved' => 'Đã xử lý', 'dismissed' => 'Đã bác bỏ'][$report->status] }}</h2>
        <p>{{ $report->outcomeMessage() }}</p>
        @if($report->handled_at)<p>Thời gian xử lý: {{ $report->handled_at->format('d/m/Y H:i') }}</p>@endif
        @if($report->status !== 'pending' && $report->reporter_message)
        <h2>Phản hồi từ quản trị viên</h2><blockquote class="safety-report-excerpt">{{ $report->reporter_message }}</blockquote>
        @endif
    </section>
    <section class="safety-panel"><h2>Nội dung đã báo cáo</h2><p>Lý do: {{ \App\Models\ContentReport::reasons()[$report->reason] ?? $report->reason }}</p><blockquote class="safety-report-excerpt">{{ $report->snapshot }}</blockquote>@if($report->details)<p class="report-user-details">{{ $report->details }}</p>@endif</section>
    <a class="btn-threads-outline" href="{{ route('reports.index') }}">Xem các báo cáo của tôi</a>
</div>
@endsection
