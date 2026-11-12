@foreach($people as $person)
<div class="connection-row">
    <a class="connection-person" href="{{ route('profile.show', $person->username ?: $person->id) }}"><x-avatar :user="$person"/><span><strong>{{ $person->name }}<x-verified-badge :user="$person"/></strong><small>{{ $person->handle() }}</small></span></a>
    @auth @if(auth()->id() !== $person->id)
    @php($requested = in_array($person->id, $requestedIds, true))
    <form method="post" action="{{ route('users.follow', $person) }}" data-stream-action="follow">@csrf<button type="submit" class="btn-threads-outline" aria-pressed="{{ $person->viewer_following || $requested ? 'true' : 'false' }}">{{ $requested ? 'Đã gửi yêu cầu' : ($person->viewer_following ? 'Đang theo dõi' : ($person->is_private ? 'Yêu cầu theo dõi' : 'Theo dõi')) }}</button></form>
    @endif @else<a href="{{ route('login') }}" class="btn-threads-outline">Đăng nhập</a>@endauth
</div>
@endforeach
