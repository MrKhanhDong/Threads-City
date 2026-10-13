@props(['post', 'media'])
@php
    $text = static function (string $key, mixed $fallback = ''): string {
        $value = old($key, $fallback);
        return is_scalar($value) ? trim((string) $value) : '';
    };
    $title = $text('title', $post->title) ?: 'Tiêu đề bài viết';
    $searchTitle = $text('seo_title', $post->seo_title) ?: $title;
    $description = $text('meta_description', $post->meta_description) ?: $text('excerpt', $post->excerpt) ?: 'Thêm mô tả để độc giả biết câu chuyện nói về điều gì.';
    $socialTitle = $text('og_title', $post->og_title) ?: $title;
    $socialDescription = $text('og_description', $post->og_description) ?: $description;
    $previewSlug = $text('slug', $post->slug) ?: Illuminate\Support\Str::slug($text('title', $post->title)) ?: 'duong-dan-bai-viet';
    $previewUrl = $text('canonical_url', $post->canonical_url);
    if (!filter_var($previewUrl, FILTER_VALIDATE_URL) || !in_array(parse_url($previewUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
        $previewUrl = route('posts.show', $previewSlug);
    }
    $imageId = $text('og_media_id', $post->og_media_id) ?: $text('cover_media_id', $post->cover_media_id);
    $previewImage = $media->firstWhere('id', (int) $imageId)?->url();
@endphp
<section class="seo-preview mt-4" data-seo-preview data-seo-base-url="{{ url('/bai-viet') }}" aria-label="Xem trước tìm kiếm và chia sẻ">
    <h3>Xem trước SEO & chia sẻ</h3>
    <p class="muted small">Cập nhật khi nhập. Đây là bản mô phỏng; Google và nền tảng chia sẻ có thể hiển thị khác.</p>
    <div class="search-preview">
        <small data-seo-url>{{ $previewUrl }}</small>
        <p data-seo-search-title>{{ $searchTitle }}</p>
        <div data-seo-description>{{ $description }}</div>
    </div>
    <p class="muted small mt-2" data-seo-lengths>{{ mb_strlen($searchTitle) }} ký tự tiêu đề · {{ mb_strlen($description) }} ký tự mô tả</p>
    <div class="social-preview">
        <img alt="Ảnh chia sẻ đã chọn" data-seo-image @if($previewImage) src="{{ $previewImage }}" @else hidden @endif>
        <div class="social-preview-copy">
            <small data-seo-domain>{{ parse_url($previewUrl, PHP_URL_HOST) }}</small>
            <h4 data-seo-social-title>{{ $socialTitle }}</h4>
            <p data-seo-social-description>{{ $socialDescription }}</p>
        </div>
    </div>
</section>
