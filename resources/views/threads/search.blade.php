@extends('layouts.threads')
@php
    $pageTitle = 'Tìm kiếm';
    $selectedTopic = $filters['category'] !== '' ? $categories->firstWhere('slug', $filters['category'])?->name : ($filters['tag'] !== '' ? $tags->firstWhere('slug', $filters['tag'])?->name : null);
    $activeFilterCount = (int)($filters['period'] !== 'any') + (int)($filters['scope'] !== 'all');
@endphp
@section('search-bar')
<form class="discovery-search-form" method="get" action="{{ route('search.index') }}" role="search">
    <x-journal-icon name="search" :size="23"/>
    <input id="discovery-query" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Tìm kiếm" aria-label="Tìm kiếm bài viết, chủ đề hoặc tài khoản" maxlength="150" autocomplete="off" enterkeyhint="search">
    <input type="hidden" name="type" value="{{ $filters['type'] }}">
    @if($filters['category'] !== '')<input type="hidden" name="category" value="{{ $filters['category'] }}">@endif
    @if($filters['tag'] !== '')<input type="hidden" name="tag" value="{{ $filters['tag'] }}">@endif
    <button class="discovery-search-submit" type="submit" aria-label="Tìm kiếm"><x-journal-icon name="arrow" :size="21"/></button>
    <details class="discovery-filter-menu" @if($errors->hasAny(['period', 'scope'])) open @endif><summary aria-label="Bộ lọc tìm kiếm" title="Bộ lọc tìm kiếm"><x-journal-icon name="filter" :size="24"/>@if($activeFilterCount)<span class="discovery-filter-badge">{{ $activeFilterCount }}</span>@endif</summary><div class="discovery-filter-panel"><h2>Bộ lọc tìm kiếm</h2><label for="discovery-period">Thời gian</label><select id="discovery-period" name="period"><option value="any" @selected($filters['period'] === 'any')>Tất cả thời gian</option><option value="week" @selected($filters['period'] === 'week')>7 ngày qua</option><option value="month" @selected($filters['period'] === 'month')>30 ngày qua</option></select><label for="discovery-scope">Nội dung từ</label><select id="discovery-scope" name="scope"><option value="all" @selected($filters['scope'] === 'all')>Tất cả mọi người</option>@auth<option value="following" @selected($filters['scope'] === 'following')>Người bạn đang theo dõi</option>@endauth</select>@guest<a class="discovery-filter-login" href="{{ route('login') }}">Đăng nhập để lọc người đang theo dõi ↗</a>@endguest<button type="submit" class="stream-submit">Áp dụng</button></div></details>
</form>
@endsection
@section('content')
<div class="discovery-page">
    <nav class="discovery-topic-chips" aria-label="Chủ đề gợi ý">@foreach($topics->take(8) as $topic)<a href="{{ route('search.index', [$topic['kind'] => $topic['slug']]) }}" @class(['is-active' => $filters[$topic['kind']] === $topic['slug']])>{{ $topic['name'] }}</a>@endforeach</nav>
    @if($selectedTopic)<div class="discovery-selected-topic"><span><x-journal-icon name="book" :size="16"/>{{ $selectedTopic }}</span><a href="{{ route('search.index', ['q' => $filters['q'], 'type' => $filters['type']]) }}" aria-label="Bỏ lọc chủ đề"><x-journal-icon name="close" :size="17"/></a></div>@endif
    @if($hasQuery)
        <nav class="stream-tabs discovery-result-tabs" aria-label="Loại kết quả tìm kiếm">@foreach(['top' => 'Nổi bật', 'latest' => 'Mới nhất', 'people' => 'Tài khoản'] as $type => $label)<a href="{{ route('search.index', array_replace($filters, ['type' => $type])) }}" @class(['is-active' => $filters['type'] === $type]) @if($filters['type'] === $type) aria-current="page" @endif>{{ $label }}</a>@endforeach</nav>
        @php($results = $people ?? $threads)
        <div class="discovery-result-summary" role="status"><h1>{{ $filters['q'] !== '' ? 'Kết quả cho “'.$filters['q'].'”' : ($selectedTopic ?? 'Khám phá nội dung') }}</h1><span>{{ $results->total() }} {{ $people ? 'tài khoản' : 'bài viết' }}</span></div>
        @if($people)
            @foreach($people as $person)
            @php($pendingRequest = auth()->user()?->hasRequestedToFollow($person) ?? false)
            <article class="discovery-person" data-follow-context><a class="discovery-person-avatar" href="{{ route('profile.show', $person->username ?: $person->id) }}"><x-avatar :user="$person"/></a><div class="discovery-person-copy"><a href="{{ route('profile.show', $person->username ?: $person->id) }}"><strong>{{ $person->username ?: $person->name }}<x-verified-badge :user="$person"/></strong>@if($person->username)<span>{{ $person->name }}</span>@endif</a>@if($person->bio)<p>{{ $person->bio }}</p>@endif<small><span data-followers-count>{{ $person->followers_count }}</span> người theo dõi · {{ $person->public_posts_count }} bài viết</small></div>@if(auth()->id() !== $person->id)@auth<form method="post" action="{{ route('users.follow', $person) }}" data-stream-action="follow">@csrf<button type="submit" class="btn-threads-outline" aria-pressed="{{ $person->viewer_following || $pendingRequest ? 'true' : 'false' }}" data-follow-label>{{ $pendingRequest ? 'Đã gửi yêu cầu' : ($person->viewer_following ? 'Đang theo dõi' : ($person->is_private ? 'Yêu cầu theo dõi' : 'Theo dõi')) }}</button></form>@else<a href="{{ route('login') }}" class="btn-threads-outline">Theo dõi</a>@endauth @endif</article>
            @endforeach
        @else
            @if($threads->isNotEmpty())<div data-stream-feed>@include('threads.partials.thread_list', ['threads' => $threads])</div>@endif
        @endif
        @if($results->isEmpty())<div class="empty-state discovery-empty"><x-journal-icon name="search" :size="38"/><h2>Chưa tìm thấy {{ $people ? 'tài khoản' : 'bài viết' }}</h2><p>Thử một từ khóa khác hoặc giảm bớt bộ lọc nhé.</p><a class="btn-threads-outline" href="{{ route('search.index') }}">Khám phá chủ đề</a></div>@endif
        <div class="journal-pagination">{{ $results->links() }}</div>
    @else
        <header class="discovery-section-heading"><h1><span>Được quan tâm</span></h1><p>Những chủ đề có bài viết công khai trong góc chia sẻ này.</p></header>
        <div class="discovery-topics">
            @foreach($topics as $topic)
                @php($thumbnail = $topic['post']?->cover ?? $topic['post']?->media->first())
                <a class="discovery-topic-row" href="{{ route('search.index', [$topic['kind'] => $topic['slug']]) }}"><div class="discovery-topic-copy"><h2>{{ $topic['name'] }}</h2><p>{{ $topic['post']?->excerpt ?: \Illuminate\Support\Str::limit(html_entity_decode(strip_tags($topic['post']?->content ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 150) }}</p><small>{{ $topic['count'] }} bài viết · {{ $topic['kind'] === 'category' ? 'Chuyên mục' : 'Hashtag' }}</small></div>@if($thumbnail)<img src="{{ $thumbnail->url() }}" alt="" width="58" height="58" loading="lazy">@else<span class="discovery-topic-art" aria-hidden="true"><x-journal-icon :name="$topic['kind'] === 'category' ? 'book' : 'message'" :size="25"/></span>@endif</a>
            @endforeach
        </div>
        @if($recent->isNotEmpty())<section class="discovery-recent"><div class="discovery-section-heading"><h2>Mới được chia sẻ</h2><p>Một vài câu chuyện để bắt đầu khám phá.</p></div>@foreach($recent as $recentPost)@php($thumbnail = $recentPost->cover ?? $recentPost->media->first())<a class="discovery-topic-row" href="{{ \Illuminate\Support\Str::startsWith($recentPost->slug ?? '', 'thread-') ? route('threads.show', $recentPost) : route('posts.show', $recentPost->slug) }}"><div class="discovery-topic-copy"><h3>{{ $recentPost->title ?: 'Chia sẻ của '.$recentPost->author->name }}</h3><p>{{ $recentPost->excerpt ?: \Illuminate\Support\Str::limit(html_entity_decode(strip_tags($recentPost->content), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 150) }}</p><small>{{ $recentPost->author->name }} · {{ $recentPost->published_at->diffForHumans(null, true) }}</small></div>@if($thumbnail)<img src="{{ $thumbnail->url() }}" alt="" width="58" height="58" loading="lazy">@else<span class="discovery-topic-art" aria-hidden="true"><x-journal-icon name="write" :size="24"/></span>@endif</a>@endforeach</section>@endif
        @if($topics->isEmpty() && $recent->isEmpty())<div class="empty-state discovery-empty"><x-journal-icon name="search" :size="38"/><h2>Chưa có chủ đề để khám phá</h2><p>Khi có bài viết công khai, các chuyên mục và hashtag sẽ xuất hiện ở đây.</p><a class="btn-threads-outline" href="{{ route('home') }}">Về bảng tin</a></div>@endif
    @endif
</div>
@endsection
