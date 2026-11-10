@extends('layouts.public')
@php($pageTitle = $user->name)
@section('content')
<section class="stream-profile" data-follow-context @if($user->is_private) data-private-profile @endif>
    <div class="stream-profile-heading">
        <div><h1>{{ $user->name }}<x-verified-badge :user="$user" :size="20"/></h1><p>{{ $user->handle() }}</p>@if($user->is_private)<span class="private-account-label">Tài khoản riêng tư</span>@endif</div>
        <div class="profile-identity-actions"><x-avatar :user="$user"/>
            @auth @if(auth()->id() !== $user->id)
            <div class="dropdown"><button type="button" class="stream-icon-button" data-bs-toggle="dropdown" aria-label="Tùy chọn tài khoản {{ $user->name }}" aria-expanded="false"><x-journal-icon name="more"/></button><ul class="dropdown-menu dropdown-menu-end"><x-user-safety-menu :user="$user" :muted="$muted"/></ul></div>
            @endif @endauth
        </div>
    </div>
    @if($user->bio)<p class="stream-profile-bio">{{ $user->bio }}</p>@endif
    <div class="stream-profile-counts"><span><strong data-followers-count>{{ $followersCount }}</strong> người theo dõi</span><span>{{ $followingCount }} đang theo dõi</span></div>
    @if(auth()->id() === $user->id)
    <div class="stream-profile-buttons"><a class="btn-threads-outline" href="{{ route('account.edit') }}">Chỉnh sửa hồ sơ</a><a class="btn-threads-outline" href="{{ route('safety.index') }}">Quyền riêng tư</a><button type="button" class="stream-submit" data-compose="new">Bài viết mới</button></div>
    @elseif(auth()->check())
    <form method="post" action="{{ route('users.follow', $user) }}" data-stream-action="follow">@csrf<button class="btn-threads-outline" type="submit" aria-pressed="{{ $following || $requested ? 'true' : 'false' }}" data-follow-label>{{ $requested ? 'Đã gửi yêu cầu' : ($following ? 'Đang theo dõi' : ($user->is_private ? 'Yêu cầu theo dõi' : 'Theo dõi')) }}</button></form>
    @else<a class="btn-threads-outline" href="{{ route('login') }}">{{ $user->is_private ? 'Đăng nhập để yêu cầu theo dõi' : 'Đăng nhập để theo dõi' }}</a>@endif
</section>
@if($canViewPosts)
<nav class="stream-tabs" aria-label="Bài viết trên hồ sơ">@foreach(['threads' => 'Threads', 'replies' => 'Trả lời', 'reposts' => 'Đăng lại'] as $key => $label)<a href="{{ route('profile.show', ['username' => $user->username ?: $user->id, 'tab' => $key]) }}" @class(['is-active' => $tab === $key])>{{ $label }}</a>@endforeach @if(auth()->id() === $user->id)<a href="{{ route('profile.show', ['username' => $user->username ?: $user->id, 'tab' => 'likes']) }}" @class(['is-active' => $tab === 'likes'])>Đã thích</a>@endif</nav>
<div data-stream-feed>@include('threads.partials.thread_list', ['threads' => $threads])</div>
@if($threads->isEmpty())<div class="empty-state"><x-journal-icon name="message" :size="32"/><h2>Chưa có bài viết</h2><p>Những chia sẻ sẽ xuất hiện ở đây.</p></div>@endif
<div class="journal-pagination">{{ $threads->links() }}</div>
@else
<div class="empty-state"><x-journal-icon name="archive" :size="36"/><h2>Tài khoản này ở chế độ riêng tư</h2><p>{{ $requested ? 'Yêu cầu của bạn đang chờ chủ tài khoản chấp nhận.' : 'Gửi yêu cầu theo dõi và chờ được chấp nhận để xem bài viết.' }}</p></div>
@endif
@endsection
