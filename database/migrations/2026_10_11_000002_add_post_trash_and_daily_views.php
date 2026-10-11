<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->softDeletes();
            $table->index('deleted_at');
        });
        Schema::create('post_daily_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->date('view_date');
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamps();
            $table->unique(['post_id', 'view_date']);
            $table->index('view_date');
        });
        // No historical backfill: existing views_count has no trustworthy dates.
    }

    public function down(): void
    {
        if (DB::table('posts')->whereNotNull('deleted_at')->exists()) {
            throw new RuntimeException('Khôi phục hoặc xóa vĩnh viễn các bài trong thùng rác trước khi rollback.');
        }
        Schema::dropIfExists('post_daily_views');
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
            $table->dropSoftDeletes();
        });
    }
};
