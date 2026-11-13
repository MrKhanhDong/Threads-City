<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\DirectMessages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_places_chat_below_search_and_guests_cannot_read_chat(): void
    {
        $this->get(route('home'))->assertOk()->assertSeeInOrder(['aria-label="Tìm kiếm"', 'aria-label="Chat"', 'aria-label="Hoạt động"'], false);
        $this->get(route('chat.index'))->assertRedirect(route('login'));
        $this->getJson(route('chat.unread'))->assertUnauthorized();
    }

    public function test_pair_is_canonical_and_reused_from_both_sides(): void
    {
        [$first, $second] = User::factory()->count(2)->create()->all();
        $firstResponse = $this->actingAs($first)->postJson(route('chat.start'), ['recipient_id' => $second->id])->assertOk();
        $this->actingAs($second)->postJson(route('chat.start'), ['recipient_id' => $first->id])->assertOk()->assertJsonPath('url', $firstResponse->json('url'));
        $this->assertDatabaseCount('chat_conversations', 1);
        $this->assertDatabaseHas('chat_conversations', ['first_user_id' => $first->id, 'second_user_id' => $second->id]);
        $this->postJson(route('chat.start'), ['recipient_id' => $second->id])->assertUnprocessable();
    }

    public function test_polling_does_not_consume_the_new_conversation_rate_limit(): void
    {
        [$first, $second] = User::factory()->count(2)->create()->all();
        $this->actingAs($first);
        for ($request = 0; $request < 31; $request++) {
            $this->getJson(route('chat.unread'))->assertOk();
        }
        $this->postJson(route('chat.start'), ['recipient_id' => $second->id])->assertOk();
    }

    public function test_message_retry_is_idempotent_sender_is_not_spoofable_and_text_is_escaped(): void
    {
        $conversation = ChatConversation::factory()->create();
        $this->actingAs($conversation->firstUser);
        $payload = ['client_token' => Str::uuid()->toString(), 'kind' => 'text', 'body' => '<script>alert(1)</script>', 'sender_id' => $conversation->second_user_id];
        $sent = $this->postJson(route('chat.send', $conversation), $payload)->assertOk();
        $this->postJson(route('chat.send', $conversation), $payload)->assertOk()->assertJsonPath('id', $sent->json('id'));
        $this->assertDatabaseCount('chat_messages', 1);
        $this->assertDatabaseHas('chat_messages', ['sender_id' => $conversation->first_user_id, 'body' => $payload['body']]);
        $this->get(route('chat.show', $conversation))->assertOk()->assertSee($payload['body'])->assertDontSee($payload['body'], false);
        $this->getJson(route('chat.index'))->assertOk()->assertJsonPath('unread_count', 0);
    }

    public function test_read_marker_only_moves_forward_and_rejects_foreign_message_ids(): void
    {
        $conversation = ChatConversation::factory()->create();
        $messages = ChatMessage::factory()->count(3)->create(['chat_conversation_id' => $conversation->id, 'sender_id' => $conversation->first_user_id]);
        $this->actingAs($conversation->secondUser)->getJson(route('chat.unread'))->assertOk()->assertJsonPath('unread_count', 3);
        $this->get(route('chat.show', $conversation))->assertOk();
        $this->getJson(route('chat.unread'))->assertJsonPath('unread_count', 3);
        $this->postJson(route('chat.read', $conversation), ['message_id' => $messages[1]->id])->assertOk()->assertJsonPath('unread_count', 1);
        $this->postJson(route('chat.read', $conversation), ['message_id' => $messages[0]->id])->assertOk()->assertJsonPath('unread_count', 1);
        $foreign = ChatMessage::factory()->create();
        $this->postJson(route('chat.read', $conversation), ['message_id' => $foreign->id])->assertNotFound();
        $this->postJson(route('chat.read', $conversation), ['message_id' => $messages[2]->id])->assertJsonPath('unread_count', 0);
    }

    public function test_images_and_voice_are_private_and_recall_removes_the_file(): void
    {
        Storage::fake('local');
        config(['services.cloudinary' => []]);
        $conversation = ChatConversation::factory()->create();
        $this->actingAs($conversation->firstUser);
        $sent = $this->postJson(route('chat.send', $conversation), ['client_token' => (string) Str::uuid(), 'kind' => 'image', 'image' => UploadedFile::fake()->image('private.png')])->assertOk();
        $message = ChatMessage::findOrFail($sent->json('id'));
        Storage::disk('local')->assertExists($message->attachment_path);
        $attachmentUrl = route('chat.attachment', [$conversation, $message]);
        $this->actingAs($conversation->secondUser)->get($attachmentUrl)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs(User::factory()->create())->get($attachmentUrl)->assertNotFound();
        $this->actingAs($conversation->firstUser)->deleteJson(route('chat.recall', [$conversation, $message]))->assertOk();
        Storage::disk('local')->assertMissing($message->attachment_path);
        $this->get($attachmentUrl)->assertNotFound();
        $voice = $this->postJson(route('chat.send', $conversation), ['client_token' => (string) Str::uuid(), 'kind' => 'voice', 'voice' => UploadedFile::fake()->create('voice.webm', 20, 'audio/webm'), 'duration_ms' => 12500])->assertOk();
        $this->assertDatabaseHas('chat_messages', ['id' => $voice->json('id'), 'kind' => 'voice', 'duration_ms' => 12500]);
        $this->get(route('chat.show', $conversation))->assertOk()->assertSee('audio', false)->assertSee('00:13');
    }

    public function test_stickers_replies_reactions_and_edits_keep_conversation_boundaries(): void
    {
        $conversation = ChatConversation::factory()->create();
        $original = ChatMessage::factory()->create(['chat_conversation_id' => $conversation->id, 'sender_id' => $conversation->first_user_id, 'body' => 'Original']);
        $this->actingAs($conversation->secondUser);
        $this->postJson(route('chat.send', $conversation), ['client_token' => (string) Str::uuid(), 'kind' => 'sticker', 'sticker' => 'hello', 'reply_to_id' => $original->id])->assertOk();
        $this->putJson(route('chat.react', [$conversation, $original]), ['reaction' => '❤️'])->assertOk();
        $this->putJson(route('chat.react', [$conversation, $original]), ['reaction' => '👍'])->assertOk();
        $this->assertSame([(string) $conversation->second_user_id => '👍'], $original->fresh()->reactions);
        $this->patchJson(route('chat.update', [$conversation, $original]), ['body' => 'Spoofed'])->assertNotFound();
        $this->actingAs($conversation->firstUser)->patchJson(route('chat.update', [$conversation, $original]), ['body' => 'Edited'])->assertOk();
        $this->assertNotNull($original->fresh()->edited_at);
        $this->assertSame('Edited', $original->fresh()->body);
        $foreign = ChatMessage::factory()->create();
        $this->postJson(route('chat.send', $conversation), ['client_token' => (string) Str::uuid(), 'kind' => 'text', 'body' => 'Reply', 'reply_to_id' => $foreign->id])->assertUnprocessable();
        $this->putJson(route('chat.react', [$conversation, $foreign]), ['reaction' => '❤️'])->assertNotFound();
        $this->deleteJson(route('chat.recall', [$conversation, $original]))->assertOk();
        $this->patchJson(route('chat.update', [$conversation, $original]), ['body' => 'Revive'])->assertUnprocessable();
        $this->putJson(route('chat.react', [$conversation, $original]), ['reaction' => '❤️'])->assertUnprocessable();
    }

    public function test_remote_attachments_are_proxied_privately_and_support_voice_ranges(): void
    {
        $conversation = ChatConversation::factory()->create();
        $path = 'https://res.cloudinary.com/demo/video/upload/v123/threads-city/media/private.webm';
        $message = ChatMessage::factory()->create(['chat_conversation_id' => $conversation->id, 'sender_id' => $conversation->first_user_id, 'kind' => 'voice', 'attachment_path' => $path, 'attachment_mime' => 'audio/webm']);
        Http::fake([$path => Http::response('voice-bytes', 206, ['Content-Range' => 'bytes 0-10/100', 'Accept-Ranges' => 'bytes'])]);
        $this->actingAs($conversation->secondUser)->get(route('chat.attachment', [$conversation, $message]), ['Range' => 'bytes=0-10'])
            ->assertStatus(206)->assertContent('voice-bytes')->assertHeader('Content-Range', 'bytes 0-10/100')->assertHeaderMissing('Location')->assertHeader('Cache-Control', 'no-store, private');
        Http::assertSent(fn ($request): bool => $request->hasHeader('Range', 'bytes=0-10'));
        $this->actingAs(User::factory()->create())->get(route('chat.attachment', [$conversation, $message]))->assertNotFound();
    }

    public function test_outsiders_and_blocked_or_locked_people_cannot_access_messages_or_attachments(): void
    {
        $conversation = ChatConversation::factory()->create();
        $message = ChatMessage::factory()->create(['chat_conversation_id' => $conversation->id, 'sender_id' => $conversation->first_user_id]);
        $this->actingAs(User::factory()->create());
        foreach ([route('chat.show', $conversation), route('chat.messages', $conversation), route('chat.attachment', [$conversation, $message])] as $url) {
            $this->getJson($url)->assertNotFound();
        }
        $this->postJson(route('chat.typing', $conversation), ['typing' => true])->assertNotFound();
        $this->postJson(route('chat.send', $conversation), ['client_token' => (string) Str::uuid(), 'kind' => 'text', 'body' => 'Intrusion'])->assertNotFound();
        $conversation->firstUser->blockedUsers()->attach($conversation->secondUser);
        $this->actingAs($conversation->secondUser)->getJson(route('chat.messages', $conversation))->assertNotFound();
        $this->getJson(route('chat.index'))->assertOk()->assertJsonMissing(['html' => $conversation->firstUser->name]);
        $this->postJson(route('chat.start'), ['recipient_id' => $conversation->first_user_id])->assertNotFound();
        $this->getJson(route('chat.people', ['q' => $conversation->firstUser->username]))->assertOk()->assertDontSee($conversation->firstUser->name);
        $conversation->firstUser->blockedUsers()->detach();
        $conversation->firstUser->forceFill(['is_locked' => true])->save();
        $this->getJson(route('chat.messages', $conversation))->assertNotFound();
    }

    public function test_pagination_incremental_refresh_and_typing_expiry(): void
    {
        $conversation = ChatConversation::factory()->create();
        $messages = ChatMessage::factory()->count(45)->create(['chat_conversation_id' => $conversation->id, 'sender_id' => $conversation->first_user_id]);
        $this->actingAs($conversation->secondUser);
        $latest = $this->getJson(route('chat.messages', $conversation))->assertOk()->assertJsonPath('has_older', true);
        $this->assertSame(40, substr_count($latest->json('html'), 'data-chat-message='));
        $this->getJson(route('chat.messages', [$conversation, 'after' => 0]))->assertOk()->assertJsonPath('cursor', $messages[39]->id)->assertJsonPath('has_newer', true);
        $before = $this->getJson(route('chat.messages', [$conversation, 'before' => $messages[5]->id]))->assertOk()->assertJsonPath('has_older', false);
        $this->assertSame(5, substr_count($before->json('html'), 'data-chat-message='));
        $newer = $this->getJson(route('chat.messages', [$conversation, 'after' => $messages[42]->id, 'refresh' => [$messages[0]->id]]))->assertOk();
        $this->assertSame(3, substr_count($newer->json('html'), 'data-chat-message='));
        $this->postJson(route('chat.typing', $conversation), ['typing' => true])->assertOk();
        $this->actingAs($conversation->firstUser)->getJson(route('chat.messages', $conversation))->assertJsonPath('typing', true);
        $this->travel(7)->seconds();
        $this->getJson(route('chat.messages', $conversation))->assertJsonPath('typing', false);
    }

    public function test_invalid_payloads_do_not_save_messages_and_search_is_scoped(): void
    {
        Storage::fake('local');
        $conversation = ChatConversation::factory()->create();
        $this->actingAs($conversation->firstUser);
        foreach ([['kind' => 'text', 'body' => '  '], ['kind' => 'sticker', 'sticker' => '../bad'], ['kind' => 'voice', 'duration_ms' => 120001], ['kind' => 'image', 'image' => UploadedFile::fake()->create('bad.svg', 5, 'image/svg+xml')], ['kind' => 'text', 'body' => str_repeat('a', 4001)]] as $payload) {
            $this->postJson(route('chat.send', $conversation), $payload + ['client_token' => (string) Str::uuid()])->assertUnprocessable();
        }
        $this->assertDatabaseCount('chat_messages', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles());
        ChatMessage::factory()->create(['chat_conversation_id' => $conversation->id, 'sender_id' => $conversation->first_user_id, 'body' => 'Needle']);
        ChatMessage::factory()->create(['body' => 'Needle private']);
        $this->getJson(route('chat.messages', [$conversation, 'q' => 'Needle']))->assertOk()->assertSee('Needle')->assertDontSee('Needle private');
        $this->assertCount(8, DirectMessages::STICKERS);
        foreach (array_keys(DirectMessages::STICKERS) as $sticker) {
            $this->assertFileExists(public_path('chat-stickers/'.$sticker.'.svg'));
        }
    }

    public function test_zero_is_a_valid_message_and_a_real_search_term(): void
    {
        $conversation = ChatConversation::factory()->create();
        $this->actingAs($conversation->firstUser)->postJson(route('chat.send', $conversation), ['client_token' => (string) Str::uuid(), 'kind' => 'text', 'body' => '0'])->assertOk();
        $message = $conversation->messages()->firstOrFail();
        $this->assertSame('0', $message->preview());
        $this->assertSame('0', $message->body);
        ChatMessage::factory()->create(['chat_conversation_id' => $conversation->id, 'sender_id' => $conversation->second_user_id, 'body' => 'Other message']);
        $result = $this->getJson(route('chat.messages', [$conversation, 'q' => '0']))->assertOk();
        $this->assertStringContainsString('data-chat-body>0</p>', $result->json('html'));
        $this->assertStringNotContainsString('Other message', $result->json('html'));
    }
}
