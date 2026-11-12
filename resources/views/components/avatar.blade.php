@props(['user'=>null, 'src'=>null])
@if($user?->avatar_path)<img class="avatar" src="{{ $src ?? route('avatar.show',$user) }}" alt="Ảnh đại diện {{ $user->name }}" width="36" height="36">
@else<span class="avatar avatar-letter" aria-hidden="true">{{ mb_substr($user?->name ?? 'M',0,1) }}</span>@endif
