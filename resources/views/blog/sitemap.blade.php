{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach(['home'] as $route)
    <url><loc>{{ route($route) }}</loc></url>
@endforeach
@foreach($posts as $post)
    <url><loc>{{ route('threads.show', $post->id) }}</loc><lastmod>{{ $post->updated_at->toIso8601String() }}</lastmod></url>
@endforeach
</urlset>
