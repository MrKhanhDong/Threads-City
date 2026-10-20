@extends('layouts.public')
@php
    $pageTitle = 'Hoạt động';
@endphp
@section('content')
<div class="reader-space-heading reader-activity-heading"><div><h1>Hoạt động</h1><p>Những tương tác với bạn và bài viết của bạn.</p></div><a class="btn-threads-outline" href="{{ route('notifications.index') }}">Thông báo</a></div>
<nav class="reader-activity-tabs" aria-label="Lọc hoạt động">@foreach(['all' => 'Tất cả', 'likes' => 'Lượt thích', 'replies' => 'Trả lời', 'follows' => 'Theo dõi', 'reposts' => 'Đăng lại'] as $key => $label)<a href="{{ route('activity.index', ['type' => $key]) }}" @class(['is-active' => $type === $key]) @if($type === $key) aria-current="page" @endif>{{ $label }}</a>@endforeach</nav>
@forelse($activities as $activity)
    @php
        $actor = $actors->get($activity->actor_id);
        $post = $posts->get($activity->post_id);
        $comment = $comments->get($activity->comment_id);
        $target = $activity->kind === 'follows' ? route('profile.show', $actor?->username ?: $activity->actor_id) : ($comment ? $commentThreads->url($comment, auth()->id()) : route('threads.show', $activity->post_id));
        $description = ['likes' => 'đã thích bài viết của bạn', 'replies' => 'đã trả lời bạn', 'follows' => 'đã bắt đầu theo dõi bạn', 'reposts' => $post?->type === 'quote' ? 'đã trích dẫn bài viết của bạn' : 'đã đăng lại bài viết của bạn'][$activity->kind];
    @endphp
    @if($actor)<a class="reader-activity-row" href="{{ $target }}"><span class="reader-activity-avatar"><x-avatar :user="$actor"/><span><x-journal-icon :name="['likes' => 'heart', 'replies' => 'message', 'follows' => 'person', 'reposts' => 'repost'][$activity->kind]" :size="12"/></span></span><div><p><strong>{{ $actor->username ?: $actor->name }}</strong> {{ $description }} <time datetime="{{ \Illuminate\Support\Carbon::parse($activity->occurred_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($activity->occurred_at)->diffForHumans(null, true) }}</time></p>@if($comment || $post)<small>{{ \Illuminate\Support\Str::limit(html_entity_decode(strip_tags($comment?->body ?? ($post?->content ?: $post?->title)), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 150) }}</small>@else<small>{{ $actor->bio ?: $actor->handle() }}</small>@endif</div></a>@endif
@empty<div class="empty-state"><x-journal-icon name="heart" :size="36"/><h2>Chưa có hoạt động{{ $type !== 'all' ? ' trong mục này' : '' }}</h2><p>Lượt thích, trả lời, theo dõi và đăng lại sẽ xuất hiện khi có tương tác.</p></div>@endforelse
<div class="journal-pagination">{{ $activities->links() }}</div>
@endsection
