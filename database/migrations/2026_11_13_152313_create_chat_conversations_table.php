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
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('first_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('second_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('first_read_id')->default(0);
            $table->unsignedBigInteger('second_read_id')->default(0);
            $table->timestamp('first_typing_at')->nullable();
            $table->timestamp('second_typing_at')->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['first_user_id', 'second_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};
