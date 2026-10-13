@props(['post', 'liked' => false, 'count' => 0])
@auth
    @if(auth()->user()->hasVerifiedEmail())
        <form method="post" action="{{ route($liked ? 'likes.destroy' : 'likes.store', $post->id) }}" data-like-action>
            @csrf
            @if($liked)
                @method('delete')
            @endif
            <button type="submit" class="btn btn-outline-forest btn-sm" aria-pressed="{{ $liked ? 'true' : 'false' }}">{{ $liked ? 'Đã thích' : 'Thích' }} · {{ $count }}</button>
        </form>
    @else
        <a class="btn btn-outline-forest btn-sm" href="{{ route('verification.notice') }}" title="Xác minh email để thích bài">Thích · {{ $count }}</a>
    @endif
@else
    <a class="btn btn-outline-forest btn-sm" href="{{ route('login') }}" title="Đăng nhập để thích bài">Thích · {{ $count }}</a>
@endauth
