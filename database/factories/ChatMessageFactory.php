<?php

namespace Database\Factories;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_conversation_id' => ChatConversation::factory(),
            'sender_id' => fn (array $attributes): int => ChatConversation::findOrFail($attributes['chat_conversation_id'])->first_user_id,
            'client_token' => fake()->uuid(),
            'kind' => 'text',
            'body' => fake()->sentence(),
        ];
    }
}
