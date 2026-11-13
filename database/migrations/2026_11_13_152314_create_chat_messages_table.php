<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('client_token');
            $table->foreignId('reply_to_id')->nullable()->constrained('chat_messages')->nullOnDelete();
            $table->text('body')->nullable();
            $table->string('kind', 12)->default('text');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_mime', 80)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('sticker', 30)->nullable();
            $table->json('reactions')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('recalled_at')->nullable();
            $table->timestamps();
            $table->unique(['chat_conversation_id', 'sender_id', 'client_token'], 'chat_message_token_unique');
            $table->index(['chat_conversation_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
