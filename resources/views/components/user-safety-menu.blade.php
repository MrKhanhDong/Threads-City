@props(['user', 'muted' => null])
@auth
@if(auth()->id() !== $user->id)
    @php($isMuted = $muted ?? auth()->user()->mutedUsers()->whereKey($user->id)->exists())
    <li><form method="post" action="{{ route('safety.mute', $user) }}">@csrf @method($isMuted ? 'delete' : 'put')<button type="submit" class="dropdown-item">{{ $isMuted ? 'Bỏ tắt tiếng' : 'Tắt tiếng' }} {{ $user->handle() }}</button></form></li>
    <li><form method="post" action="{{ route('safety.block', $user) }}" data-confirm="Chặn {{ $user->handle() }}? Hai bên sẽ không thể xem hoặc tương tác với nhau và các lượt theo dõi sẽ bị hủy.">@csrf @method('put')<button type="submit" class="dropdown-item text-danger">Chặn {{ $user->handle() }}</button></form></li>
    <li><a class="dropdown-item" href="{{ route('reports.user', $user) }}">Báo cáo tài khoản</a></li>
@endif
@endauth
