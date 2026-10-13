<?php

namespace App\Services;

use App\Models\Post;

class SeoAnalyzer
{
    /** @return array<string,bool> */
    public function analyze(Post $post): array
    {
        preg_match_all('~<img\b[^>]*>~i', $post->content, $images);
        $hasAlt = true;
        foreach ($images[0] as $image) {
            if (! preg_match('~\balt=["\x27][^"\x27]+["\x27]~i', $image)) {
                $hasAlt = false;
            }
        }
        $plain = mb_strtolower(strip_tags($post->content).' '.$post->title.' '.$post->excerpt);

        return [
            'Có tiêu đề SEO' => filled($post->seo_title),
            'Tiêu đề SEO khoảng 30–65 ký tự' => mb_strlen($post->seo_title ?? '') >= 30 && mb_strlen($post->seo_title ?? '') <= 65,
            'Mô tả khoảng 70–160 ký tự' => mb_strlen($post->meta_description ?? '') >= 70 && mb_strlen($post->meta_description ?? '') <= 160,
            'Đường dẫn hợp lệ' => (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $post->slug ?? ''),
            'Có heading trong bài' => (bool) preg_match('/<h[1-6]\b/i', $post->content),
            'Có ảnh bìa' => filled($post->cover_media_id),
            'Ảnh nội dung có ALT' => $hasAlt,
            'Từ khóa có trong nội dung' => filled($post->focus_keyword) && str_contains($plain, mb_strtolower($post->focus_keyword)),
            'Có liên kết nội bộ' => str_contains($post->content, '/bai-viet/'),
        ];
    }
}
