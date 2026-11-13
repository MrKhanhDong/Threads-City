<?php

namespace Database\Factories;

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatConversation>
 */
class ChatConversationFactory extends Factory
{
    public function mutual(): static
    {
        return $this->afterCreating(function (ChatConversation $conversation): void {
            $conversation->firstUser->following()->syncWithoutDetaching([$conversation->second_user_id => ['created_at' => now()]]);
            $conversation->secondUser->following()->syncWithoutDetaching([$conversation->first_user_id => ['created_at' => now()]]);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_user_id' => User::factory(),
            'second_user_id' => User::factory(),
        ];
    }
}
