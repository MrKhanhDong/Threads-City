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
        if (! Schema::hasColumn('users', 'lock_reason')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->text('lock_reason')->nullable();
            });
        }
        if (! Schema::hasColumn('users', 'lock_expires_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('lock_expires_at')->nullable()->index();
            });
        }
        if (! Schema::hasColumn('posts', 'moderation_hidden_at')) {
            Schema::table('posts', function (Blueprint $table): void {
                $table->timestamp('moderation_hidden_at')->nullable();
            });
        }
        if (Schema::hasTable('moderation_logs')) {
            return;
        }
        Schema::create('moderation_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('target_type', 30);
            $table->unsignedBigInteger('target_id');
            $table->string('action', 40);
            $table->text('reason');
            $table->json('before_state');
            $table->json('after_state');
            $table->timestamp('created_at');
            $table->index(['target_type', 'target_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moderation_logs');
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn('moderation_hidden_at');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['lock_reason', 'lock_expires_at']);
        });
    }
};
