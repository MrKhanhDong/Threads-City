<?php

namespace App\Services;

use App\Models\Media;
use DOMDocument;
use DOMElement;

class MediaAltResolver
{
    public function apply(DOMDocument $document): void
    {
        $images = [];
        foreach ($document->getElementsByTagName('img') as $image) {
            if (preg_match('/[^\s\p{Z}]/u', $image->getAttribute('alt'))) {
                continue;
            }
            $id = $this->mediaId($image);
            if ($id !== null) {
                $images[$id][] = $image;
            }
        }
        if ($images === []) {
            return;
        }
        foreach (Media::whereKey(array_keys($images))->get(['id', 'alt']) as $media) {
            if (! preg_match('/[^\s\p{Z}]/u', $media->alt ?? '')) {
                continue;
            }
            foreach ($images[$media->id] as $image) {
                $image->setAttribute('alt', $media->alt);
            }
        }
    }

    private function mediaId(DOMElement $image): ?int
    {
        $source = trim($image->getAttribute('src'));
        $parts = parse_url($source);
        $base = parse_url(route('media.show', 0));
        if ($parts === false || preg_match('/[\x00-\x20\\\\]/', $source) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }
        if (isset($parts['scheme']) && ! isset($parts['host'])) {
            return null;
        }
        if (isset($parts['host']) && (strtolower($parts['host']) !== strtolower($base['host'])
            || strtolower($parts['scheme'] ?? $base['scheme']) !== strtolower($base['scheme'])
            || ($parts['port'] ?? (($parts['scheme'] ?? $base['scheme']) === 'https' ? 443 : 80)) !== ($base['port'] ?? ($base['scheme'] === 'https' ? 443 : 80)))) {
            return null;
        }
        $prefix = substr($base['path'], 0, -1);

        return preg_match('~^'.preg_quote($prefix, '~').'([1-9][0-9]*)$~', $parts['path'] ?? '', $matches) ? (int) $matches[1] : null;
    }
}
