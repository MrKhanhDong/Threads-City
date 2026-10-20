@props(['items' => []])
<nav class="site-breadcrumbs" aria-label="Đường dẫn trang">
    <ol><li><a href="{{ route('home') }}">Threads</a></li>@foreach($items as $item)<li><span class="site-breadcrumb-separator" aria-hidden="true">/</span>@if(!$loop->last && !empty($item['url']))<a href="{{ $item['url'] }}">{{ $item['label'] }}</a>@else<span aria-current="page">{{ $item['label'] }}</span>@endif</li>@endforeach</ol>
</nav>
