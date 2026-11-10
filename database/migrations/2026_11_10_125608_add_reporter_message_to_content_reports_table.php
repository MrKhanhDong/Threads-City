<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_reports', function (Blueprint $table): void {
            $table->text('reporter_message')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('content_reports', function (Blueprint $table): void {
            $table->dropColumn('reporter_message');
        });
    }
};
