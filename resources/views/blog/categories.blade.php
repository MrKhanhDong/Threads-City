@extends('layouts.blog')
@section('title', 'Chuyên mục')
@section('content')
    <header class="page-heading"><span class="overline">NHỮNG ĐIỀU MÌNH VIẾT</span><h1>Chuyên mục<span class="heading-dot">.</span></h1><p>Mỗi chủ đề là một lát cắt trong hành trình của mình.</p></header>
    <div class="category-grid">@foreach($categories as $item)<a class="category-card" href="{{ route('posts.index', ['category' => $item['slug']]) }}"><span class="category-icon"><x-icon :name="$item['icon']" size="26" /></span><h2>{{ $item['name'] }}</h2><p>{{ $item['description'] }}</p><div><span>{{ $item['count'] }} bài viết</span><x-icon name="arrow" /></div></a>@endforeach</div>
@endsection
