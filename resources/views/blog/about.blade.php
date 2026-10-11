@extends('layouts.blog')
@section('title', 'Về mình')
@section('content')
    <header class="page-heading"><span class="overline">NGƯỜI ĐỨNG SAU NHỮNG DÒNG VIẾT</span><h1>Chào bạn, mình là {{ $author['name'] }}<span class="heading-dot">.</span></h1></header>
    <div class="about-card">
        @if($author['avatar'])<img class="avatar avatar--large" src="{{ $author['avatar'] }}" alt="Ảnh {{ $author['name'] }}">@else<span class="avatar avatar--large">{{ $author['initials'] }}</span>@endif
        <div><h2>{{ $author['name'] }}</h2><p>{{ $author['role'] }}</p><span class="pill">Viết từ những trải nghiệm thật</span></div>
    </div>
    <div class="prose">
        <p class="lead">{{ $author['bio'] }}</p>
        @foreach(preg_split('/\R\s*\R/u', $author['about'] ?? '') ?: [] as $paragraph)<p class="preserve-lines">{{ $paragraph }}</p>@endforeach
        <blockquote>Không cần viết điều gì thật lớn. Chỉ cần viết điều gì thật với mình.</blockquote>
        @if($author['website'])<p><a href="{{ $author['website'] }}" rel="noopener noreferrer" target="_blank">Website của mình ↗</a></p>@endif
        <a class="button button--black" href="{{ route('posts.index') }}">Đọc những ghi chép <x-icon name="arrow" size="17" /></a>
    </div>
@endsection
