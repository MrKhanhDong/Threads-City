<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'title' => fake()->sentence(6), 'slug' => fake()->unique()->slug(5), 'excerpt' => fake()->paragraph(), 'content' => '<p>'.e(fake()->paragraphs(3, true)).'</p>', 'status' => 'draft', 'published_at' => null, 'views_count' => 0, 'lock_version' => 1, 'is_featured' => false];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'published', 'published_at' => now()->subDay()]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'scheduled', 'published_at' => now()->addDay()]);
    }
}
