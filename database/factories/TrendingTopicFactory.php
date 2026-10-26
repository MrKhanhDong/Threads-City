<?php

namespace Database\Factories;

use App\Models\TrendingTopic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrendingTopic>
 */
class TrendingTopicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tag' => fake()->slug(1),
            'title' => fake()->sentence(4),
            'desc' => fake()->sentence(10),
            'count' => fake()->randomElement(['1K', '2.5K', '5K', '10K']),
            'posts_count' => fake()->numberBetween(50, 10000),
            'order' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}
