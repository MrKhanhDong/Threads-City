<?php

namespace Tests\Feature;

use App\Models\TrendingTopic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrendingTopicsTest extends TestCase
{
    use RefreshDatabase;

    public function test_trending_topics_are_loaded_from_database_and_rendered_on_threads_page(): void
    {
        TrendingTopic::create([
            'tag' => 'AI',
            'title' => 'Trí tuệ nhân tạo bùng nổ tại Việt Nam',
            'desc' => 'Nhiều tập đoàn công nghệ lớn công bố các giải pháp AI đột phá...',
            'count' => '10K',
            'posts_count' => 10000,
            'order' => 1,
            'is_active' => true,
        ]);

        TrendingTopic::create([
            'tag' => 'HUIT',
            'title' => 'Sinh viên HUIT đạt giải cao nghiên cứu khoa học',
            'desc' => 'Đoàn sinh viên xuất sắc giành giải nhất toàn quốc...',
            'count' => '2K',
            'posts_count' => 2000,
            'order' => 2,
            'is_active' => true,
        ]);

        TrendingTopic::create([
            'tag' => 'HiddenTopic',
            'title' => 'Chủ đề bị ẩn',
            'desc' => 'Mô tả ẩn...',
            'count' => '100',
            'is_active' => false,
        ]);

        $response = $this->get(route('threads.index'));

        $response->assertOk();
        $response->assertSee('Trí tuệ nhân tạo bùng nổ tại Việt Nam');
        $response->assertSee('Sinh viên HUIT đạt giải cao nghiên cứu khoa học');
        $response->assertSee('10K bài viết');
        $response->assertDontSee('Chủ đề bị ẩn');
    }
}
