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
        Schema::create('trending_topics', function (Blueprint $table) {
            $table->id();
            $table->string('tag')->nullable()->index();
            $table->string('title');
            $table->text('desc')->nullable();
            $table->string('count')->nullable(); // e.g. '5K', '1K', or formatted
            $table->unsignedInteger('posts_count')->default(0);
            $table->integer('order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trending_topics');
    }
};
