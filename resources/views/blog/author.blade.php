@extends('layouts.public')
@php $pageTitle='Tác giả'; @endphp
@section('content')<section class="container page-section"><div class="author-intro"><x-avatar :user="$author" /><p class="eyebrow">NGƯỜI ĐỨNG SAU NHỮNG CÂU CHUYỆN</p><h1>{{ $author?->name ?? $site['site_name'] }}</h1><p class="lead-muted">{{ $author?->bio ?: $site['site_tagline'] }}</p></div><div class="section-heading"><h2>Từ cuốn sổ của tác giả</h2></div><div class="post-grid">@forelse($posts as $post)<x-post-card :post="$post" />@empty<p>Chưa có bài công khai.</p>@endforelse</div>{{ $posts->links() }}</section>@endsection
