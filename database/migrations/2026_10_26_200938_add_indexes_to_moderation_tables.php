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
        Schema::table('hidden_posts', function (Blueprint $table): void {
            $table->index('user_id');
        });

        Schema::table('muted_posts', function (Blueprint $table): void {
            $table->index('user_id');
        });

        Schema::table('user_blocks', function (Blueprint $table): void {
            $table->index('blocked_user_id');
        });

        Schema::table('user_restrictions', function (Blueprint $table): void {
            $table->index('restricted_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_restrictions', function (Blueprint $table): void {
            $table->dropIndex(['restricted_user_id']);
        });

        Schema::table('user_blocks', function (Blueprint $table): void {
            $table->dropIndex(['blocked_user_id']);
        });

        Schema::table('muted_posts', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
        });

        Schema::table('hidden_posts', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
        });
    }
};
