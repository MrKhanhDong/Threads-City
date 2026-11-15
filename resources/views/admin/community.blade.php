@extends('layouts.admin')
@php
    $isReplies = $kind === 'reply';
    $pageTitle = $isReplies ? 'Quản lý trả lời' : 'Quản lý bài đăng';
    $sectionTitle = $isReplies ? 'Trả lời' : 'Bài đăng';
    $listRoute = $isReplies ? 'admin.replies.index' : 'admin.community.index';
    $statusLabels = ['active' => 'Chưa bị xóa', 'hidden' => 'Quản trị viên đã ẩn', 'deleted' => 'Tác giả đã xóa', 'all' => 'Tất cả trạng thái'];
@endphp
@section('content')
<div class="admin-page-heading">
    <div><p class="eyebrow">QUẢN LÝ NỘI DUNG</p><h1>{{ $sectionTitle }}</h1><p class="muted">{{ $isReplies ? 'Theo dõi phản hồi, xem ngữ cảnh và xử lý những trả lời vi phạm.' : 'Quản lý bài đăng, đăng lại và trích dẫn của thành viên cộng đồng.' }}</p></div>
    <span class="admin-result-count"><x-journal-icon :name="$isReplies ? 'message' : 'book'" :size="17"/><strong>{{ number_format($posts->total()) }}</strong> {{ $isReplies ? 'trả lời' : 'bài đăng' }}</span>
</div>
<section class="panel admin-filter-panel" aria-label="Bộ lọc {{ $sectionTitle }}">
    <details class="admin-filter-disclosure" data-admin-filter-disclosure data-has-filters="{{ request()->hasAny(['q', 'author', 'id', 'from', 'to']) || $status !== 'active' ? 'true' : 'false' }}" open>
    <summary><x-journal-icon name="filter" :size="18"/><span>Tìm kiếm và lọc {{ $isReplies ? 'trả lời' : 'bài đăng' }}</span><x-journal-icon name="chevron" :size="15"/></summary>
    <form method="get" action="{{ route($listRoute) }}" class="admin-filter-grid">
        <x-field name="q" label="Nội dung hoặc tác giả" :value="request('q')" maxlength="150" placeholder="Tìm nội dung, tên hoặc username…"/>
        <div class="field"><label for="status">Trạng thái</label><select name="status" id="status" class="form-select">@foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="admin-filter-dates"><x-field name="from" type="date" label="Từ ngày" :value="request('from')"/><x-field name="to" type="date" label="Đến ngày" :value="request('to')"/></div>
        @if(request('author'))<input type="hidden" name="author" value="{{ request('author') }}">@endif
        @if(request('id'))<input type="hidden" name="id" value="{{ request('id') }}">@endif
        <button type="submit" class="btn btn-forest"><x-journal-icon name="search" :size="17"/>Lọc {{ $isReplies ? 'trả lời' : 'bài đăng' }}</button>
    </form>
    @if(request()->hasAny(['q', 'author', 'id', 'from', 'to']) || $status !== 'active')
        <div class="admin-filter-summary"><span>{{ number_format($posts->total()) }} kết quả phù hợp</span><a href="{{ route($listRoute) }}">Xóa bộ lọc</a></div>
    @endif
    </details>
</section>
<div class="admin-record-list">
    @forelse($posts as $post)
        @php
            $text = trim(html_entity_decode(strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $post->content)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $state = $post->trashed() ? ($post->moderation_hidden_at ? 'hidden' : 'deleted') : ($post->archived_at ? 'archived' : $post->status);
            $stateLabel = ['hidden' => 'Đã ẩn', 'deleted' => 'Đã xóa', 'archived' => 'Đã lưu trữ', 'draft' => 'Bản nháp', 'published' => 'Đã đăng', 'scheduled' => 'Đã lên lịch'][$state] ?? $state;
            $typeLabel = $post->isReply() ? 'Trả lời' : (['repost' => 'Đăng lại', 'quote' => 'Trích dẫn'][$post->type] ?? 'Bài đăng');
        @endphp
        <article class="panel admin-record-card" id="post-{{ $post->id }}">
            <header class="admin-record-header">
                <div class="admin-record-identity"><span class="avatar admin-record-avatar" aria-hidden="true">{{ mb_substr($post->author?->name ?? 'T', 0, 1) }}</span><div><h2 class="admin-record-title">{{ $post->author?->name ?? 'Tài khoản đã xóa' }}<x-verified-badge :user="$post->author" :size="15"/></h2><p class="admin-record-meta">{{ $post->author?->handle() }} <span>·</span> {{ $typeLabel }} #{{ $post->id }}</p></div></div>
                <div class="admin-record-state"><span @class(['admin-state', 'is-success' => $state === 'published', 'is-warning' => $state === 'hidden', 'is-muted' => !in_array($state, ['published', 'hidden'])])><i aria-hidden="true"></i>{{ $stateLabel }}</span><time datetime="{{ $post->created_at->toIso8601String() }}">{{ $post->created_at->format('d/m/Y H:i') }}</time></div>
            </header>
            @if($post->parent)
                <details class="admin-context"><summary><x-journal-icon name="message" :size="16"/><span>Ngữ cảnh: trả lời bài #{{ $post->parent_id }}</span><x-journal-icon name="chevron" :size="14"/></summary><div class="admin-context-body"><strong>{{ $post->parent->author?->name ?? 'Tài khoản đã xóa' }}</strong><p class="preserve-lines">{{ strip_tags($post->parent->content) }}</p><a href="{{ route($post->parent->isReply() ? 'admin.replies.index' : 'admin.community.index', ['id' => $post->parent_id, 'status' => 'all']) }}">Xem nội dung gốc ↗</a></div></details>
            @elseif($post->isReply())
                <p class="admin-context-note"><x-journal-icon name="info" :size="15"/>Nội dung gốc không còn khả dụng. Trả lời vẫn được giữ lại để quản lý.</p>
            @endif
            @if($post->repostOf)
                <details class="admin-context"><summary><x-journal-icon name="repost" :size="16"/><span>Bài gốc #{{ $post->repost_of_id }}</span><x-journal-icon name="chevron" :size="14"/></summary><div class="admin-context-body"><strong>{{ $post->repostOf->author?->name ?? 'Tài khoản đã xóa' }}</strong><p class="preserve-lines">{{ strip_tags($post->repostOf->content) }}</p><a href="{{ route($post->repostOf->isReply() ? 'admin.replies.index' : 'admin.community.index', ['id' => $post->repost_of_id, 'status' => 'all']) }}">Xem nội dung gốc ↗</a></div></details>
            @endif
            @if($text !== '')<div class="admin-record-text preserve-lines" tabindex="0" aria-label="Nội dung {{ $isReplies ? 'trả lời' : 'bài đăng' }}">{{ $text }}</div>@else<p class="admin-record-placeholder">Nội dung đính kèm</p>@endif
            @if($post->cover || $post->media->isNotEmpty())
                <div class="admin-attachments">
                    @if($post->cover)<a href="{{ route('media.show', $post->cover) }}" target="_blank" rel="noopener"><x-journal-icon name="book" :size="15"/>Xem ảnh bìa ↗</a>@endif
                    @foreach($post->media as $media)<a href="{{ route('media.show', $media) }}" target="_blank" rel="noopener"><x-journal-icon name="book" :size="15"/><span>{{ $media->original_name }}</span><small>{{ $media->mime_type }}</small>↗</a>@endforeach
                </div>
            @endif
            <div class="admin-record-links"><a href="{{ route('admin.history.index', ['target_type' => 'post', 'target_id' => $post->id]) }}"><x-journal-icon name="repost" :size="15"/>Lịch sử xử lý</a>@unless($post->trashed())<a href="{{ route('threads.show', $post->id) }}" target="_blank" rel="noopener"><x-journal-icon name="arrow" :size="15"/>Xem trên website</a>@endunless</div>
            @if(!$post->trashed() || $post->moderation_hidden_at)
                <details class="admin-action-disclosure" @if($post->trashed()) open @endif>
                <summary><x-journal-icon name="settings" :size="17"/><span>{{ $post->trashed() ? 'Khôi phục nội dung' : 'Kiểm duyệt nội dung' }}</span><x-journal-icon name="chevron" :size="15"/></summary>
                <form method="post" action="{{ route('admin.community.update', $post->id) }}" class="admin-action-form" data-confirm="Xác nhận quyết định kiểm duyệt?">
                    @csrf @method('patch')
                    <input type="hidden" name="action" value="{{ $post->trashed() ? 'restore' : 'hide' }}">
                    <div class="field"><label for="reason-{{ $post->id }}">Lý do xử lý (gửi cho tác giả)</label><input name="reason" id="reason-{{ $post->id }}" class="form-control" placeholder="Giải thích quyết định kiểm duyệt…" required maxlength="1000"></div>
                    <button type="submit" @class(['btn', 'admin-danger-button' => !$post->trashed(), 'btn-outline-forest' => $post->trashed()])>{{ $post->trashed() ? 'Khôi phục' : ($isReplies ? 'Ẩn trả lời' : 'Ẩn bài đăng') }}</button>
                </form>
                </details>
            @endif
        </article>
    @empty
        <section class="panel admin-empty"><x-journal-icon :name="$isReplies ? 'message' : 'book'" :size="34"/><h2>Không có {{ $isReplies ? 'trả lời' : 'bài đăng' }} phù hợp</h2><p>Thử thay đổi từ khóa, trạng thái hoặc khoảng thời gian.</p><a href="{{ route($listRoute) }}" class="btn btn-outline-forest">Đặt lại bộ lọc</a></section>
    @endforelse
</div>
<div class="admin-pagination">{{ $posts->links() }}</div>
@endsection
