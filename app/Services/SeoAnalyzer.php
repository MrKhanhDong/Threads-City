<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Post;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Str;

class SeoAnalyzer
{
    public function __construct(private MediaAltResolver $mediaAltResolver) {}

    /** @return array<string, bool|null> */
    public function analyze(Post $post): array
    {
        $document = new DOMDocument;
        $previousErrors = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8"><html><body>'.($post->content ?? '').'</body></html>', LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//script|//style|//template') as $node) {
            $node->parentNode->removeChild($node);
        }
        $this->mediaAltResolver->apply($document);
        $images = $document->getElementsByTagName('img');
        $hasAlt = $images->length > 0 ? true : null;
        foreach ($images as $image) {
            if ($this->normalizeText($image->getAttribute('alt')) === '') {
                $hasAlt = false;
            }
        }
        foreach ($xpath->query('//p|//div|//section|//article|//blockquote|//li|//td|//th|//h1|//h2|//h3|//h4|//h5|//h6|//br|//hr') as $node) {
            $node->parentNode->insertBefore($document->createTextNode(' '), $node);
            $node->parentNode->insertBefore($document->createTextNode(' '), $node->nextSibling);
        }
        $plain = mb_strtolower($this->normalizeText($document->getElementsByTagName('body')->item(0)?->textContent ?? ''));
        $hasHeading = false;
        foreach ($xpath->query('//h1|//h2|//h3|//h4|//h5|//h6') as $heading) {
            $hasHeading = $hasHeading || $this->normalizeText($heading->textContent) !== '';
        }
        $hasInternalLink = false;
        foreach ($document->getElementsByTagName('a') as $link) {
            $hasInternalLink = $hasInternalLink || $this->isInternalLink($link->getAttribute('href'));
        }
        $title = trim($post->seo_title ?? '') ?: trim($post->title ?? '');
        $description = trim($post->meta_description ?? '') ?: trim($post->excerpt ?? '');
        $slug = trim($post->slug ?? '') ?: Str::slug($post->title ?? '');
        $keyword = mb_strtolower($this->normalizeText($post->focus_keyword ?? ''));
        $coverAlt = $post->cover_media_id ? Media::whereKey($post->cover_media_id)->value('alt') : null;

        return [
            'Có tiêu đề tìm kiếm' => $title !== '',
            'Tiêu đề tìm kiếm khoảng 30–65 ký tự' => mb_strlen($title) >= 30 && mb_strlen($title) <= 65,
            'Mô tả khoảng 70–160 ký tự' => mb_strlen($description) >= 70 && mb_strlen($description) <= 160,
            'Đường dẫn đúng định dạng' => (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug),
            'Có heading trong bài' => $hasHeading,
            'Có ảnh bìa' => filled($post->cover_media_id),
            'Ảnh bìa có ALT' => $post->cover_media_id ? $this->normalizeText($coverAlt ?? '') !== '' : null,
            'Ảnh nội dung có ALT' => $hasAlt,
            'Từ khóa có trong nội dung' => $keyword !== '' && (bool) preg_match('/(?<![\p{L}\p{N}_])'.preg_quote($keyword, '/').'(?![\p{L}\p{N}_])/u', $plain),
            'Có liên kết nội bộ' => $hasInternalLink,
        ];
    }

    private function normalizeText(string $text): string
    {
        return trim(preg_replace('/[\s\p{Z}]+/u', ' ', $text) ?? '');
    }

    private function isInternalLink(string $href): bool
    {
        $href = trim($href);
        if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, '?') || preg_match('/[\x00-\x20\\\\]/', $href)) {
            return false;
        }
        $parts = parse_url($href);
        $site = parse_url(url('/'));
        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }
        $scheme = strtolower($parts['scheme'] ?? $site['scheme']);
        if (! in_array($scheme, ['http', 'https'], true) || (isset($parts['scheme']) && ! isset($parts['host']))) {
            return false;
        }
        if (! isset($parts['host'])) {
            return true;
        }

        return strtolower($parts['host']) === strtolower($site['host'])
            && $scheme === strtolower($site['scheme'])
            && ($parts['port'] ?? ($scheme === 'https' ? 443 : 80)) === ($site['port'] ?? ($site['scheme'] === 'https' ? 443 : 80));
    }
}
