@extends('layouts.public')
@php
    $pageTitle = 'Thông tin chi tiết';
    $days = $insights['days'];
    $notes = [
        'views' => 'Số lượt mở bài được ghi nhận theo ngày, giới hạn một lần cho mỗi phiên, mỗi bài, mỗi ngày. Không tính lượt mở của bạn hoặc quản trị viên; đây không phải số người xem duy nhất.',
        'likes' => 'Các lượt thích nhận được trong khoảng thời gian đã chọn và hiện vẫn còn trên bài viết. Không tính lượt tự thích hoặc lượt đã bỏ thích.',
        'followers' => 'Các tài khoản bắt đầu theo dõi bạn trong khoảng thời gian đã chọn và hiện vẫn đang theo dõi. Đây không phải số tăng ròng vì chưa có lịch sử hủy theo dõi.',
        'interactions' => 'Tổng lượt thích, trả lời, bình luận, đăng lại và trích dẫn nhận được trong kỳ trên bài đang công khai. Không tính tương tác của chính bạn hoặc nội dung đã ẩn, xóa.',
    ];
@endphp
@section('content')
<div class="insights-page" data-insights>
    <header class="insights-toolbar"><div><h1>Tổng quan</h1><form method="get" action="{{ route('insights.index') }}"><label class="visually-hidden" for="insight-days">Khoảng thời gian</label><select id="insight-days" name="days" data-auto-submit>@foreach([7, 30, 90] as $period)<option value="{{ $period }}" @selected($days === $period)>{{ $period }} ngày qua</option>@endforeach</select><input type="hidden" name="metric" value="{{ $metric }}" data-insight-metric-input><noscript><button type="submit" class="btn-threads-outline">Áp dụng</button></noscript></form></div><button type="button" class="btn-threads-outline insights-export" data-insights-print>Xuất dưới dạng PDF</button></header>
    <p class="insights-period">{{ $insights['start']->format('d/m/Y') }} – {{ $insights['end']->format('d/m/Y') }} · {{ auth()->user()->handle() }}</p>
    <section class="insights-summary" id="insight-summary"><h2>Tóm tắt</h2><p class="insights-hint">Chọn một ô để xem biểu đồ theo ngày.</p>
        <nav class="insights-metrics" aria-label="Chọn chỉ số thống kê">@foreach($insights['metrics'] as $key => $summary)
            <a href="{{ route('insights.index', ['days' => $days, 'metric' => $key]) }}#insight-summary" data-insight-metric="{{ $key }}" aria-controls="insight-panel-{{ $key }}" @class(['insight-metric-card', 'is-active' => $metric === $key]) @if($metric === $key) aria-current="true" @endif><span>{{ $summary['label'] }}</span><strong>{{ number_format($summary['total'], 0, ',', '.') }}</strong>@include('threads.partials.insight_change', ['summary' => $summary])</a>
        @endforeach</nav>
        <p class="visually-hidden" role="status" data-insight-announcement>Đang xem biểu đồ {{ $insights['metrics'][$metric]['label'] }}</p>
        @foreach($insights['metrics'] as $key => $summary)
        <section id="insight-panel-{{ $key }}" data-insight-panel="{{ $key }}" aria-label="Biểu đồ {{ $summary['label'] }}" @if($metric !== $key) hidden @endif>
            @include('threads.partials.insight_chart', ['chart' => $summary['chart'], 'values' => $summary['values'], 'dates' => $insights['dates'], 'label' => $summary['label'], 'chartKey' => $key])
            <div class="insight-metric-detail"><h2>{{ $summary['label'] }}</h2><strong class="insight-detail-total">{{ number_format($summary['total'], 0, ',', '.') }}</strong><div class="insight-detail-comparison">@include('threads.partials.insight_change', ['summary' => $summary])<span>so với {{ $insights['previous_start']->format('d/m') }} – {{ $insights['previous_end']->format('d/m') }}</span></div><p class="insight-definition">{{ $notes[$key] }}</p>
                @if($key === 'views')<p class="insight-definition">Biểu đồ dùng số liệu đã lưu theo ngày. Lượt xem cũ chỉ có tổng cộng sẽ không được tự phân bổ vào các ngày.</p>
                @elseif($key === 'likes')<div class="insight-breakdown"><div><span>Lượt thích trong kỳ</span><strong>{{ number_format($summary['total'], 0, ',', '.') }}</strong></div><div><span>Lượt thích kỳ trước</span><strong>{{ number_format($summary['previous'], 0, ',', '.') }}</strong></div></div>
                @elseif($key === 'followers')<div class="insight-breakdown"><div><span>Đang theo dõi bạn</span><strong>{{ number_format($insights['followers'], 0, ',', '.') }}</strong></div><div><span>Bạn đang theo dõi</span><strong>{{ number_format($insights['following'], 0, ',', '.') }}</strong></div></div>
                @else<h3>Loại tương tác</h3><div class="insight-breakdown">@foreach(['likes' => 'Lượt thích', 'replies' => 'Trả lời', 'comments' => 'Bình luận', 'reposts' => 'Đăng lại & trích dẫn'] as $part => $name)<div><span>{{ $name }}</span><strong>{{ number_format($insights['breakdown'][$part], 0, ',', '.') }}</strong></div>@endforeach</div>@endif
            </div>
        </section>
        @endforeach
    </section>
    <section class="insights-posts"><div class="reader-space-heading reader-space-subheading"><h2>Bài viết của bạn</h2><p>{{ $insights['posts'] }} bài viết mới trong {{ $days }} ngày qua. Danh sách dưới đây gồm các bài đang công khai, xếp theo lượt xem và lượt thích trong kỳ.</p></div>
        @forelse($threads as $thread)<a class="reader-insight-post" href="{{ \Illuminate\Support\Str::startsWith($thread->slug ?? '', 'thread-') ? route('threads.show', $thread) : route('posts.show', $thread->slug) }}"><h3>{{ $thread->title ?: 'Bài viết của bạn' }}</h3><time>{{ $thread->published_at->format('d/m/Y') }}</time><div><span>{{ (int) $thread->period_views }} lượt xem</span><span>{{ $thread->period_likes }} lượt thích</span><span>{{ $thread->period_replies + $thread->period_comments }} trả lời & bình luận</span><span>{{ $thread->period_reposts }} đăng lại</span></div></a>@empty<div class="empty-state"><x-journal-icon name="chart" :size="36"/><h2>Bắt đầu từ bài viết đầu tiên</h2><p>Khi bạn đăng bài, số liệu thật sẽ xuất hiện ở đây.</p><button type="button" class="btn-threads-outline" data-compose="new">Bài viết mới</button></div>@endforelse
        <div class="journal-pagination">{{ $threads->links() }}</div>
    </section>
    @include('threads.partials.insights_report')
</div>
@endsection
