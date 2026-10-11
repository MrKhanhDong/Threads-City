@extends('layouts.blog')
@section('title', 'Quản trị blog')
@section('robots', 'noindex,nofollow')
@section('content')
<header class="admin-heading"><div><span class="overline">GÓC NGƯỜI VIẾT</span><h1>Chào {{ auth()->user()->name }}<span class="heading-dot">.</span></h1><p class="muted">Một nơi để viết, chăm chút và quản lý blog của bạn.</p></div><a class="button button--black" href="{{ route('admin.editor') }}"><x-icon name="edit" size="16" /> Viết bài mới</a></header>
<x-admin-nav />
<div class="admin-stats">
@foreach(['posts'=>'Bài ngoài thùng rác','published'=>'Đang công khai','drafts'=>'Bản nháp','pending'=>'Bình luận chờ duyệt','readers'=>'Độc giả','views'=>'Lượt xem tích lũy'] as $key=>$label)
<div><span>{{ $label }}</span><strong>{{ number_format($stats[$key]) }}</strong></div>
@endforeach
</div>

<section class="analytics-panel" aria-labelledby="analytics-heading">
<div class="analytics-heading"><div><span class="overline">NHỮNG CON SỐ TỪ GÓC VIẾT</span><h2 id="analytics-heading">Lượt xem theo ngày</h2><p class="muted">{{ $analytics['from'] }} — {{ $analytics['to'] }} · Giờ Việt Nam</p></div>
<form method="get" action="{{ route('admin.index') }}" class="analytics-filter"><label for="analytics-range">Khoảng thời gian</label><select id="analytics-range" name="range">@foreach([7,30,90] as $days)<option value="{{ $days }}" @selected($analytics['days'] === $days)>{{ $days }} ngày gần nhất</option>@endforeach</select><button class="button" type="submit">Xem</button></form>
</div>
<div class="period-stats">
<div><span>Trong kỳ</span><strong data-period-total>{{ number_format($analytics['total']) }}</strong></div>
<div><span>Hôm nay</span><strong>{{ number_format($analytics['today']) }}</strong></div>
<div><span>Trung bình/ngày</span><strong>{{ number_format($analytics['average'], 1, ',', '.') }}</strong></div>
<div><span>Ngày cao nhất</span><strong>{{ $analytics['peak'] ? $analytics['peak']['label'] : '—' }}</strong><small>{{ $analytics['peak'] ? number_format($analytics['peak']['views']).' lượt' : 'Chưa có lượt xem' }}</small></div>
</div>
<div class="views-chart">
<svg viewBox="0 0 870 220" role="img" aria-labelledby="views-chart-title views-chart-desc" data-views-chart>
<title id="views-chart-title">Lượt xem bài viết trong {{ $analytics['days'] }} ngày gần nhất</title>
<desc id="views-chart-desc">Từ {{ $analytics['from'] }} đến {{ $analytics['to'] }}, tổng {{ $analytics['total'] }} lượt xem. Ngày không có lượt xem bằng 0. Mở bảng số liệu bên dưới để đọc từng ngày.</desc>
@foreach([0, 0.5, 1] as $fraction)
@php($y = (int)round(178 - 150*$fraction))
<line x1="52" y1="{{ $y }}" x2="842" y2="{{ $y }}" class="chart-grid" />
<text x="40" y="{{ $y+4 }}" text-anchor="end" class="chart-label">{{ number_format((int)($analytics['maximum']*$fraction)) }}</text>
@endforeach
<polygon points="52,178 {{ $analytics['line'] }} 842,178" class="chart-area" />
<polyline points="{{ $analytics['line'] }}" class="chart-line" />
@foreach($analytics['points'] as $point)
<circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="{{ $analytics['days'] === 7 ? 4 : 2.5 }}" class="chart-point"><title>{{ $point['full_label'] }}: {{ number_format($point['views']) }} lượt xem</title></circle>
@endforeach
@foreach($analytics['ticks'] as $tick)<text x="{{ $tick['x'] }}" y="208" text-anchor="middle" class="chart-label">{{ $tick['label'] }}</text>@endforeach
</svg>
</div>
@if($analytics['total'] === 0)<p class="chart-empty">Chưa có lượt xem trong kỳ. Mở một bài công khai bằng cửa sổ khách để bắt đầu ghi nhận.</p>@endif
<p class="analytics-help">Mỗi bài tính một lượt/phiên/ngày; tải lại không cộng thêm, admin đọc hoặc xem trước không được tính. Chỉ có dữ liệu ngày từ lúc nâng cấp, không phân bổ số lượt cũ vào biểu đồ. Bài trong thùng rác không được tính.</p>
<details class="daily-data"><summary>Xem bảng số liệu {{ $analytics['days'] }} ngày</summary><div class="table-wrap"><table class="admin-table"><thead><tr><th>Ngày</th><th>Lượt xem</th></tr></thead><tbody>@foreach($analytics['daily']->reverse() as $row)<tr><td><time datetime="{{ $row['date'] }}">{{ $row['full_label'] }}</time></td><td>{{ number_format($row['views']) }}</td></tr>@endforeach</tbody></table></div></details>
</section>

<div class="dashboard-columns">
<section class="analytics-panel" aria-labelledby="top-posts-heading">
<div class="section-heading"><h2 id="top-posts-heading">Bài được đọc nhiều</h2><span>{{ $analytics['days'] }} NGÀY</span></div>
<ol class="popular-posts">
@forelse($analytics['topPosts'] as $item)
<li><span class="rank-number">{{ str_pad((string)$loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><a href="{{ route('admin.posts.edit', $item) }}">{{ $item->title }}</a><small>{{ $item->category->name }} · {{ number_format((int)$item->period_views) }} lượt trong kỳ</small><div class="rank-progress" aria-hidden="true"><span style="width: {{ (int)round((int)$item->period_views / (int)$analytics['topPosts']->first()->period_views * 100) }}%"></span></div></div></li>
@empty<li class="muted">Chưa có bài được đọc trong khoảng thời gian này.</li>@endforelse
</ol>
</section>
<section class="analytics-panel" aria-labelledby="pending-heading">
<div class="section-heading"><h2 id="pending-heading">Bình luận chờ duyệt <span class="count">{{ $analytics['pendingCount'] }}</span></h2></div>
<div class="pending-list">
@forelse($analytics['pendingComments'] as $comment)
<article><div class="comment-top"><strong>{{ $comment->user->name }}</strong><small>{{ $comment->created_at->format('d/m H:i') }}</small></div><p>{{ $comment->body }}</p><a class="text-link" href="{{ route('admin.comments.index', ['status'=>'pending']) }}#comment-{{ $comment->id }}">{{ $comment->post->title }} <x-icon name="arrow" size="14" /></a></article>
@empty<p class="muted">Đã duyệt hết rồi. Không có lời nhắn đang chờ.</p>@endforelse
</div>
<a class="button" href="{{ route('admin.comments.index', ['status'=>'pending']) }}">Mở danh sách chờ duyệt</a>
</section>
</div>

<div class="section-heading"><h2>Bài viết gần đây</h2><a class="text-link" href="{{ route('admin.posts.index') }}">Quản lý tất cả <x-icon name="arrow" size="16" /></a></div>
<div class="table-wrap"><table class="admin-table"><thead><tr><th>Bài viết</th><th>Chuyên mục</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
@forelse($recent as $item)<tr><td><strong>{{ $item->title }}</strong><small>{{ $item->updated_at->format('d/m/Y') }}</small></td><td>{{ $item->category->name }}</td><td><span class="status-chip">{{ ['draft'=>'Bản nháp','published'=>'Xuất bản','archived'=>'Lưu trữ'][$item->status] }}</span></td><td><a href="{{ route('admin.posts.edit', $item) }}">Chỉnh sửa</a></td></tr>@empty<tr><td colspan="4">Chưa có bài viết.</td></tr>@endforelse
</tbody></table></div>
<div class="library-note"><p class="muted">{{ $trashCount }} bài đang ở thùng rác, có thể khôi phục.</p><a class="button" href="{{ route('admin.posts.trash') }}">Mở thùng rác</a></div>
@endsection
