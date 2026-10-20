<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;

class ThreadText
{
    public function __construct(private HtmlSanitizer $sanitizer) {}

    public function clean(string $html): string
    {
        return $this->sanitizer->cleanThread($html);
    }

    public function store(string $content, string $format = 'plain'): string
    {
        return $format === 'html' ? $this->clean($content) : nl2br(e($content));
    }

    public function plain(string $html): string
    {
        $separated = preg_replace('~</(?:p|div|h[1-6]|li|blockquote|pre)>|<br\s*/?>~i', "\n", $html) ?? $html;

        return trim(str_replace("\u{00A0}", ' ', html_entity_decode(strip_tags($separated), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    public function html(string $storedContent): string
    {
        $html = $this->clean($storedContent);
        if ($html === '') {
            return '';
        }
        $document = new DOMDocument;
        $previousErrors = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8"><html><body><div id="thread-content">'.$html.'</div></body></html>', LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
        $root = $document->getElementById('thread-content');
        $textNodes = (new DOMXPath($document))->query('//*[@id="thread-content"]//text()[not(ancestor::a) and not(ancestor::pre) and not(ancestor::code)]');
        foreach (iterator_to_array($textNodes) as $node) {
            $parts = preg_split('~(https?://[^\s<>]+|\#[\p{L}\p{N}_]+)~u', $node->textContent, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$node->textContent];
            $fragment = $document->createDocumentFragment();
            foreach ($parts as $part) {
                if (str_starts_with($part, '#') || (preg_match('~^https?://~i', $part) && filter_var($part, FILTER_VALIDATE_URL))) {
                    $link = $document->createElement('a');
                    $link->setAttribute('href', str_starts_with($part, '#') ? route('search.index', ['q' => $part]) : $part);
                    if (str_starts_with($part, '#')) {
                        $link->setAttribute('class', 'stream-hashtag');
                    } else {
                        $link->setAttribute('target', '_blank');
                    }
                    $link->appendChild($document->createTextNode($part));
                    $fragment->appendChild($link);
                } else {
                    $fragment->appendChild($document->createTextNode($part));
                }
            }
            $node->parentNode->replaceChild($fragment, $node);
        }
        foreach ($root->getElementsByTagName('a') as $link) {
            $link->setAttribute('rel', 'nofollow noopener noreferrer');
        }
        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }
}
