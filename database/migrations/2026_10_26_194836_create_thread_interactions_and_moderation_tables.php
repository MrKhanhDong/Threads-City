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
        if (! Schema::hasTable('post_reports')) {
            Schema::create('post_reports', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('reason', 100);
                $table->text('notes')->nullable();
                $table->boolean('resolved')->default(false);
                $table->timestamps();
                $table->unique(['post_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('hidden_posts')) {
            Schema::create('hidden_posts', function (Blueprint $table): void {
                $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('created_at')->useCurrent();
                $table->primary(['post_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('muted_posts')) {
            Schema::create('muted_posts', function (Blueprint $table): void {
                $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('created_at')->useCurrent();
                $table->primary(['post_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('user_blocks')) {
            Schema::create('user_blocks', function (Blueprint $table): void {
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('blocked_user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('created_at')->useCurrent();
                $table->primary(['user_id', 'blocked_user_id']);
            });
        }

        if (! Schema::hasTable('user_restrictions')) {
            Schema::create('user_restrictions', function (Blueprint $table): void {
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('restricted_user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('created_at')->useCurrent();
                $table->primary(['user_id', 'restricted_user_id']);
            });
        }

        if (! Schema::hasTable('custom_feeds')) {
            Schema::create('custom_feeds', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name', 120);
                $table->string('slug', 150);
                $table->text('description')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'slug']);
            });
        }

        if (! Schema::hasTable('custom_feed_posts')) {
            Schema::create('custom_feed_posts', function (Blueprint $table): void {
                $table->foreignId('custom_feed_id')->constrained('custom_feeds')->cascadeOnDelete();
                $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
                $table->timestamp('created_at')->useCurrent();
                $table->primary(['custom_feed_id', 'post_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_feed_posts');
        Schema::dropIfExists('custom_feeds');
        Schema::dropIfExists('user_restrictions');
        Schema::dropIfExists('user_blocks');
        Schema::dropIfExists('muted_posts');
        Schema::dropIfExists('hidden_posts');
        Schema::dropIfExists('post_reports');
    }
};
