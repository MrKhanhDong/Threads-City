<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('post_reports') || ! Schema::hasTable('content_reports')) {
            return;
        }
        DB::table('post_reports as legacy')->join('posts', 'posts.id', '=', 'legacy.post_id')
            ->leftJoin('users', 'users.id', '=', 'posts.user_id')
            ->select('legacy.*', 'posts.content', 'users.name as author_name')
            ->whereNotExists(function (Builder $query): void {
                $query->selectRaw('1')->from('content_reports')
                    ->whereColumn('content_reports.reporter_id', 'legacy.user_id')
                    ->whereColumn('content_reports.post_id', 'legacy.post_id');
            })->chunkById(200, function (Collection $reports): void {
                foreach ($reports as $report) {
                    DB::table('content_reports')->insertOrIgnore([
                        'reporter_id' => $report->user_id, 'post_id' => $report->post_id,
                        'reason' => $report->reason === 'hate_speech' ? 'hate' : $report->reason,
                        'details' => $report->notes,
                        'snapshot' => ($report->author_name ?? 'Tác giả').': '.Str::limit(strip_tags($report->content ?? ''), 600),
                        'status' => $report->resolved ? 'resolved' : 'pending',
                        'handled_at' => $report->resolved ? $report->updated_at : null,
                        'created_at' => $report->created_at, 'updated_at' => $report->updated_at,
                    ]);
                }
            }, 'legacy.id', 'id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
