@props(['post'])
@auth
    <form action="{{ route($post['saved'] ? 'bookmarks.destroy' : 'bookmarks.store', $post['id']) }}" method="post" class="bookmark-form">
        @csrf
        @if($post['saved']) @method('DELETE') @endif
        <button class="save-button" type="submit" aria-label="{{ $post['saved'] ? 'Bỏ lưu' : 'Lưu để đọc sau' }}: {{ $post['title'] }}" aria-pressed="{{ $post['saved'] ? 'true' : 'false' }}"><x-icon name="bookmark" size="18" /></button>
    </form>
@else
    <a class="icon-button" href="{{ route('login') }}" aria-label="Đăng nhập để lưu bài viết"><x-icon name="bookmark" size="18" /></a>
@endauth
