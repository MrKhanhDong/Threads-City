<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('content_format', 10)->default('plain');
        });
        DB::table('posts')->where('slug', 'regexp', '^thread-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$')->update(['content_format' => 'html']);
        if (Schema::hasTable('user_blocks')) {
            foreach (DB::table('user_blocks')->get() as $block) {
                DB::table('user_safety_blocks')->insertOrIgnore(['blocker_id' => $block->user_id, 'blocked_id' => $block->blocked_user_id, 'created_at' => $block->created_at]);
            }
            foreach (DB::table('user_safety_blocks')->get() as $block) {
                DB::table('user_blocks')->insertOrIgnore(['user_id' => $block->blocker_id, 'blocked_user_id' => $block->blocked_id, 'created_at' => $block->created_at]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn('content_format');
        });
    }
};
