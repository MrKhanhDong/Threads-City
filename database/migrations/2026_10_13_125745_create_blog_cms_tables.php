<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t): void {
            $t->enum('role', ['admin', 'reader'])->default('reader')->index();
            $t->boolean('is_locked')->default(false);
            $t->string('google_id')->nullable()->unique();
            $t->string('avatar_path')->nullable();
            $t->text('bio')->nullable();
        });
        Schema::create('categories', function (Blueprint $t): void {
            $t->id();
            $t->string('name', 100)->unique();
            $t->string('slug', 150)->unique();
            $t->text('description')->nullable();
            $t->timestamps();
        });
        Schema::create('tags', function (Blueprint $t): void {
            $t->id();
            $t->string('name', 80)->unique();
            $t->string('slug', 100)->unique();
            $t->timestamps();
        });
        Schema::create('media', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('path')->unique();
            $t->string('original_name');
            $t->string('mime_type', 80);
            $t->unsignedBigInteger('size');
            $t->unsignedInteger('width')->nullable();
            $t->unsignedInteger('height')->nullable();
            $t->string('alt')->nullable();
            $t->timestamps();
        });
        Schema::create('posts', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $t->string('title', 200);
            $t->string('slug', 220)->unique();
            $t->text('excerpt')->nullable();
            $t->longText('content');
            $t->enum('status', ['draft', 'published', 'scheduled'])->default('draft');
            $t->boolean('is_featured')->default(false);
            $t->timestamp('published_at')->nullable();
            $t->unsignedBigInteger('views_count')->default(0);
            $t->unsignedInteger('lock_version')->default(1);
            $t->string('seo_title', 200)->nullable();
            $t->string('meta_description', 320)->nullable();
            $t->string('focus_keyword', 150)->nullable();
            $t->string('canonical_url', 500)->nullable();
            $t->string('og_title', 200)->nullable();
            $t->string('og_description', 320)->nullable();
            $t->foreignId('og_media_id')->nullable()->constrained('media')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['status', 'published_at']);
            $t->index('deleted_at');
        });
        foreach (['category' => 'categories', 'tag' => 'tags', 'media' => 'media'] as $singular => $plural) {
            Schema::create('post_'.$singular, function (Blueprint $t) use ($singular, $plural): void {
                $t->foreignId('post_id')->constrained()->cascadeOnDelete();
                $t->foreignId($singular.'_id')->constrained($plural)->restrictOnDelete();
                $t->primary(['post_id', $singular.'_id']);
            });
        }
        Schema::create('comments', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('post_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('parent_id')->nullable()->constrained('comments')->nullOnDelete();
            $t->text('body');
            $t->enum('status', ['pending', 'approved', 'hidden', 'spam'])->default('pending');
            $t->timestamps();
            $t->index(['post_id', 'status']);
        });
        Schema::create('comment_reports', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('comment_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('reason', 1000);
            $t->boolean('resolved')->default(false);
            $t->timestamps();
            $t->unique(['comment_id', 'user_id']);
        });
        Schema::create('bookmarks', function (Blueprint $t): void {
            $t->foreignId('post_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->primary(['post_id', 'user_id']);
        });
        Schema::create('post_redirects', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('post_id')->constrained()->cascadeOnDelete();
            $t->string('slug', 220)->unique();
            $t->timestamps();
        });
        Schema::create('post_daily_views', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('post_id')->constrained()->cascadeOnDelete();
            $t->date('view_date');
            $t->unsignedBigInteger('views')->default(0);
            $t->timestamps();
            $t->unique(['post_id', 'view_date']);
            $t->index('view_date');
        });
        Schema::create('settings', function (Blueprint $t): void {
            $t->string('key', 100)->primary();
            $t->text('value')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['settings', 'post_daily_views', 'post_redirects', 'bookmarks', 'comment_reports', 'comments', 'post_media', 'post_tag', 'post_category', 'posts', 'media', 'tags', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $t): void {
            $t->dropUnique(['google_id']);
            $t->dropIndex(['role']);
            $t->dropColumn(['role', 'is_locked', 'google_id', 'avatar_path', 'bio']);
        });
    }
};
