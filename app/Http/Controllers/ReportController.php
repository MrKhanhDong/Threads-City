<?php

namespace App\Http\Controllers;

use App\Models\ContentReport;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $reports = ContentReport::where('reporter_id', $request->user()->id)->latest()->paginate(15);

        return view('account.reports', compact('reports'));
    }

    public function show(Request $request, ContentReport $report): View
    {
        abort_unless($report->reporter_id === $request->user()->id, 404);

        return view('account.report-result', compact('report'));
    }

    public function thread(Request $request, Post $thread): View|RedirectResponse|JsonResponse
    {
        abort_unless(Post::published()->visibleTo($request->user())->whereKey($thread->id)->exists(), 404);
        abort_if($thread->user_id === $request->user()->id, 422, 'Bạn không thể báo cáo bài viết của chính mình.');

        return $this->handle($request, $thread, null);
    }

    public function user(Request $request, User $user): View|RedirectResponse|JsonResponse
    {
        abort_if($request->user()->isBlockedWith($user), 404);
        abort_if($user->id === $request->user()->id, 422, 'Bạn không thể báo cáo chính mình.');

        return $this->handle($request, null, $user);
    }

    private function handle(Request $request, ?Post $thread, ?User $user): View|RedirectResponse|JsonResponse
    {
        $reasons = ContentReport::reasons();
        $targetName = $thread ? $thread->author->name : $user->name;
        $excerpt = $thread ? Str::limit(strip_tags($thread->content), 600) : $user->handle();

        if ($request->isMethod('get')) {
            return view('account.report', compact('thread', 'user', 'reasons', 'targetName', 'excerpt'));
        }

        $request->merge([
            'reason' => $request->input('reason') === 'hate_speech' ? 'hate' : $request->input('reason'),
            'details' => $request->input('details', $request->input('notes')),
        ]);
        $data = $request->validate(['reason' => ['required', Rule::in(array_keys($reasons))], 'details' => 'nullable|string|max:2000']);
        $report = ContentReport::firstOrCreate(
            ['reporter_id' => $request->user()->id, 'post_id' => $thread?->id, 'reported_user_id' => $user?->id],
            ['reason' => $data['reason'], 'details' => $data['details'] ?? null, 'snapshot' => $targetName.': '.$excerpt]
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'report_id' => $report->id, 'message' => 'Đã gửi báo cáo cho quản trị viên. Thông tin người báo cáo được giữ riêng tư.']);
        }

        return redirect()->route('home')->with('success', 'Đã gửi báo cáo cho quản trị viên. Thông tin người báo cáo được giữ riêng tư.');
    }
}
