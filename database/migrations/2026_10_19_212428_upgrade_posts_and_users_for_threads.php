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
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username', 50)->nullable()->unique()->after('name');
            }
        });

        Schema::table('posts', function (Blueprint $table): void {
            $table->string('title', 200)->nullable()->change();
            $table->string('slug', 220)->nullable()->change();
            $table->foreignId('parent_id')->nullable()->after('user_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('repost_of_id')->nullable()->after('parent_id')->constrained('posts')->cascadeOnDelete();
            $table->enum('type', ['post', 'reply', 'repost', 'quote'])->default('post')->after('repost_of_id')->index();
            $table->unsignedInteger('likes_count')->default(0)->after('type');
            $table->unsignedInteger('replies_count')->default(0)->after('likes_count');
            $table->unsignedInteger('reposts_count')->default(0)->after('replies_count');
        });

        Schema::create('follows', function (Blueprint $table): void {
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('following_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['follower_id', 'following_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follows');

        Schema::table('posts', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['repost_of_id']);
            $table->dropColumn(['parent_id', 'repost_of_id', 'type', 'likes_count', 'replies_count', 'reposts_count']);
            $table->string('title', 200)->nullable(false)->change();
            $table->string('slug', 220)->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('username');
        });
    }
};
