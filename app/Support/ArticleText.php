<?php

namespace App\Support;

/** Plain text with optional ## headings; never renders untrusted HTML. */
class ArticleText
{
    public static function sections(string $text): array
    {
        $sections = [];
        $current = ['heading' => '', 'paragraphs' => []];
        foreach (preg_split('/\R\s*\R/u', trim($text)) ?: [] as $block) {
            foreach (preg_split('/(?=^##\s)/m', $block) ?: [] as $part) {
                $part = trim($part);
                if ($part === '') continue;
                if (preg_match('/^##\s+([^\r\n]+)(?:\R(.*))?$/us', $part, $matches)) {
                    if ($current['heading'] !== '' || $current['paragraphs']) $sections[] = $current;
                    $current = ['heading' => trim($matches[1]), 'paragraphs' => []];
                    if (isset($matches[2]) && trim($matches[2]) !== '') $current['paragraphs'][] = trim($matches[2]);
                } else {
                    $current['paragraphs'][] = $part;
                }
            }
        }
        if ($current['heading'] !== '' || $current['paragraphs']) $sections[] = $current;
        return $sections;
    }
    public static function readingMinutes(string $text): int
    {
        return max(1, (int) ceil(count(preg_split('/\s+/u', trim($text)) ?: []) / 200));
    }
}
