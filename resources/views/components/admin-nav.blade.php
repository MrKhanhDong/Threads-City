<nav class="admin-tabs" aria-label="Quản trị blog">
@foreach(['admin.index'=>'Tổng quan', 'admin.posts.index'=>'Bài viết', 'admin.posts.trash'=>'Thùng rác', 'admin.categories.index'=>'Chuyên mục', 'admin.tags.index'=>'Thẻ', 'admin.comments.index'=>'Bình luận', 'admin.users.index'=>'Độc giả', 'admin.settings.edit'=>'Cài đặt'] as $route => $label)
<a href="{{ route($route) }}" @class(['is-active'=>request()->routeIs($route) || ($route === 'admin.posts.index' && request()->routeIs('admin.editor','admin.posts.edit'))]) @if(request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
@endforeach
</nav>
