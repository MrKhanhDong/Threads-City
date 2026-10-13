<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostRedirect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostRedirect>
 */
class PostRedirectFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['post_id' => Post::factory(), 'slug' => fake()->unique()->slug(6)];
    }
}
