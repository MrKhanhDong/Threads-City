<article class="insights-print-report" aria-label="Báo cáo thống kê Threads">
    <header class="insight-report-header">
        <div class="insight-report-identity"><x-avatar :user="auth()->user()"/><div><strong>{{ auth()->user()->name }}</strong><span>{{ auth()->user()->handle() }}</span></div></div>
        <div class="insight-report-period"><strong>{{ $insights['start']->format('d/m/Y') }} - {{ $insights['end']->format('d/m/Y') }}</strong><span>{{ $days }} ngày qua</span></div>
        <div class="insight-report-brand">@threads</div>
    </header>

    <section class="insight-report-summary"><h1>Tóm tắt</h1>
        <table class="insight-report-metrics"><tbody><tr>@foreach($insights['metrics'] as $key => $summary)
            <td><h2>{{ $summary['label'] }}</h2><div class="insight-report-value"><strong>{{ number_format($summary['total'], 0, ',', '.') }}</strong>@include('threads.partials.insight_change', ['summary' => $summary])</div>
                <svg viewBox="35 10 640 195" role="img" aria-label="{{ $summary['label'] }} trong {{ $days }} ngày">
                    @foreach([20, 105, 190] as $lineY)<line x1="45" x2="665" y1="{{ $lineY }}" y2="{{ $lineY }}" stroke="#ededed" stroke-width="1" vector-effect="non-scaling-stroke"/>@endforeach
                    <path d="{{ $summary['chart']['line'] }}" fill="none" stroke="#171717" stroke-width="1.5" vector-effect="non-scaling-stroke"/>
                </svg>
                <div class="insight-report-chart-dates"><span>{{ $insights['start']->format('d/m') }}</span><span>{{ $insights['start']->addDays(intdiv($days - 1, 2))->format('d/m') }}</span><span>{{ $insights['end']->format('d/m') }}</span></div>
            </td>
        @endforeach</tr></tbody></table>
        <p class="insight-report-comparison">So với {{ $insights['previous_start']->format('d/m/Y') }} - {{ $insights['previous_end']->format('d/m/Y') }}. “Mới trong kỳ này” nghĩa là kỳ trước chưa ghi nhận số liệu.</p>
    </section>

    <section class="insight-report-section"><h2>Lượt tương tác</h2><div class="insight-report-section-total"><strong>{{ number_format($insights['metrics']['interactions']['total'], 0, ',', '.') }}</strong>@include('threads.partials.insight_change', ['summary' => $insights['metrics']['interactions']])<span>trong {{ $days }} ngày qua</span></div>
        <table class="insight-report-breakdown"><tbody><tr>@foreach(['likes' => 'Lượt thích', 'replies' => 'Trả lời', 'comments' => 'Bình luận', 'reposts' => 'Đăng lại & trích dẫn'] as $key => $label)<td><span>{{ $label }}</span><strong>{{ number_format($insights['breakdown'][$key], 0, ',', '.') }}</strong></td>@endforeach</tr></tbody></table>
    </section>

    <section class="insight-report-section"><h2>Người theo dõi</h2><div class="insight-report-section-total"><strong>{{ number_format($insights['metrics']['followers']['total'], 0, ',', '.') }}</strong>@include('threads.partials.insight_change', ['summary' => $insights['metrics']['followers']])<span>người theo dõi mới trong kỳ</span></div>
        <div class="insight-report-followers"><p>Hiện có <strong>{{ number_format($insights['followers'], 0, ',', '.') }}</strong> người theo dõi bạn.</p><p>Bạn đang theo dõi <strong>{{ number_format($insights['following'], 0, ',', '.') }}</strong> tài khoản.</p></div>
    </section>

    <section class="insight-report-section insight-report-notes"><h2>Phạm vi thống kê</h2><p>{{ $insights['posts'] }} bài viết mới được công khai trong kỳ. Các chỉ số phản ánh bài viết gốc hiện đang công khai của tài khoản này.</p><ul><li>Lượt xem tính theo phiên, bài và ngày; không phải số người xem duy nhất. Lượt xem cũ chỉ có tổng cộng không được tự chia vào từng ngày.</li><li>Lượt thích và theo dõi chỉ tính những lượt hiện vẫn còn. Số người theo dõi mới không phải số tăng ròng.</li><li>Tương tác không bao gồm hoạt động của chính bạn hoặc nội dung đã ẩn, xóa.</li></ul></section>
    <footer class="insight-report-footer"><span>Threads · Báo cáo {{ $days }} ngày</span><span>{{ $insights['end']->format('d/m/Y H:i') }}</span></footer>
</article>
