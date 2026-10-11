<?php

namespace Tests\Unit;

use App\Repositories\DemoBlogRepository;
use PHPUnit\Framework\TestCase;

class DemoBlogRepositoryTest extends TestCase
{
    public function test_posts_have_unique_identifiers_and_readable_content(): void
    {
        $posts = (new DemoBlogRepository)->posts();
        $this->assertSame($posts->count(), $posts->pluck('id')->unique()->count());
        $this->assertSame($posts->count(), $posts->pluck('slug')->unique()->count());
        foreach ($posts as $post) {
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $post['slug']);
            $this->assertNotEmpty($post['sections']);
            $this->assertNotEmpty($post['sections'][0]['paragraphs']);
        }
    }

    public function test_lookup_and_chronological_order(): void
    {
        $blog = new DemoBlogRepository;
        $this->assertSame(1, $blog->find('tu-php-thuan-den-laravel')['id']);
        $this->assertNull($blog->find('missing'));
        $dates = $blog->posts()->pluck('date')->all();
        $sorted = $dates;
        rsort($sorted);
        $this->assertSame($sorted, $dates);
    }
}
