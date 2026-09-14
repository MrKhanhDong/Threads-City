<?php

declare(strict_types=1);

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $page = 'home', array $params = []): string
{
    return 'index.php?' . http_build_query(array_merge(['page' => $page], $params));
}

function initials(string $name): string
{
    $words = preg_split('/\s+/u', trim($name)) ?: [];
    $letters = '';

    foreach (array_slice($words, -2) as $word) {
        preg_match('/^./u', $word, $matches);
        $letters .= $matches[0] ?? '';
    }

    return strtoupper($letters ?: 'U');
}

function find_post(array $posts, int $id): ?array
{
    foreach ($posts as $post) {
        if ((int) $post['id'] === $id) {
            return $post;
        }
    }

    return null;
}

function nav_is_active(string $currentPage, string $target): bool
{
    return $currentPage === $target || ($target === 'home' && $currentPage === 'post');
}
