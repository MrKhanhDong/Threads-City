@extends('layouts.public')
@php($pageTitle = 'Báo cáo của tôi')
@section('content')
<div class="safety-page">
    <header class="safety-heading"><h1>Báo cáo của tôi</h1><p>Theo dõi báo cáo bạn đã gửi và xem phản hồi từ quản trị viên.</p></header>
    @forelse($reports as $report)
    <article class="safety-panel">
        <h2>Báo cáo #{{ $report->id }} · {{ ['pending' => 'Chờ xử lý', 'resolved' => 'Đã xử lý', 'dismissed' => 'Đã bác bỏ'][$report->status] }}</h2>
        <p>{{ \App\Models\ContentReport::REASONS[$report->reason] ?? $report->reason }} · {{ $report->created_at->format('d/m/Y H:i') }}</p>
        <p>{{ $report->outcomeMessage() }}</p>
        <a class="btn-threads-outline" href="{{ route('reports.show', $report) }}">Xem chi tiết và phản hồi</a>
    </article>
    @empty<section class="safety-panel"><p class="safety-empty">Bạn chưa gửi báo cáo nào.</p></section>@endforelse
    {{ $reports->links() }}
</div>
@endsection
