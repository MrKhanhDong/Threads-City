<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_private')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_private')->default(false);
            });
        }

        Schema::create('user_safety_blocks', function (Blueprint $table): void {
            $table->foreignId('blocker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('blocked_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['blocker_id', 'blocked_id']);
        });

        if (Schema::hasTable('user_blocks') && Schema::hasColumns('user_blocks', ['user_id', 'blocked_user_id', 'created_at'])) {
            DB::table('user_safety_blocks')->insertUsing(['blocker_id', 'blocked_id', 'created_at'], DB::table('user_blocks')->select(['user_id', 'blocked_user_id', 'created_at']));
        }

        Schema::create('user_mutes', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('muted_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['user_id', 'muted_user_id']);
        });

        Schema::create('follow_requests', function (Blueprint $table): void {
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['requester_id', 'target_id']);
            $table->index(['target_id', 'created_at']);
        });

        Schema::create('content_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('post_id')->nullable()->constrained('posts')->nullOnDelete();
            $table->foreignId('reported_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 30);
            $table->text('details')->nullable();
            $table->text('snapshot');
            $table->string('status', 20)->default('pending');
            $table->string('action', 30)->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
            $table->unique(['reporter_id', 'post_id']);
            $table->unique(['reporter_id', 'reported_user_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_reports');
        Schema::dropIfExists('follow_requests');
        Schema::dropIfExists('user_mutes');
        Schema::dropIfExists('user_safety_blocks');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_private');
        });
    }
};
