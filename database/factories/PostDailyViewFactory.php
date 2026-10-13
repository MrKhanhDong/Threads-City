<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostDailyView;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostDailyView>
 */
class PostDailyViewFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['post_id' => Post::factory()->published(), 'view_date' => now()->toDateString(), 'views' => 0];
    }
}
