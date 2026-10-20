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
        Schema::table('posts', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->index();
            $table->unsignedBigInteger('archive_root_id')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropIndex(['archived_at']);
            $table->dropIndex(['archive_root_id']);
            $table->dropColumn(['archived_at', 'archive_root_id']);
        });
    }
};
