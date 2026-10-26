<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Notifications\ThreadInteractionNotification;
use App\Services\ImageStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ThreadController extends Controller
{
    public function mentionSuggestions(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) === 0) {
            $users = User::query()
                ->whereNotNull('username')
                ->latest()
                ->limit(6)
                ->get(['id', 'name', 'username', 'avatar_path']);
        } else {
            $users = User::query()
                ->where(function ($query) use ($q): void {
                    $query->where('username', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%");
                })
                ->limit(6)
                ->get(['id', 'name', 'username', 'avatar_path']);
        }

        $results = $users->map(fn (User $u): array => [
            'id' => $u->id,
            'name' => $u->name,
            'username' => $u->username,
            'handle' => '@'.$u->username,
            'avatar_url' => $u->avatar_path ? route('avatar.show', $u->id) : null,
        ]);

        return response()->json($results);
    }

    public function index(Request $request): View|JsonResponse
    {
        $tab = $request->query('tab', 'for-you');
        $query = Post::published()
            ->whereNull('parent_id')
            ->where(function ($q): void {
                $q->whereNull('type')->orWhere('type', '!=', 'reply');
            })
            ->with(['author', 'media', 'repostOf.author', 'repostOf.media', 'tags'])
            ->latest('published_at');

        if ($tab === 'following' && $request->user()) {
            $followingIds = $request->user()->following()->pluck('users.id')->push($request->user()->id);
            $query->whereIn('user_id', $followingIds);
        }

        $threads = $query->paginate(15)->withQueryString();

        if ($request->ajax() && $request->wantsJson()) {
            return response()->json([
                'html' => view('threads.partials.thread_list', ['threads' => $threads])->render(),
                'hasMore' => $threads->hasMorePages(),
                'nextPageUrl' => $threads->nextPageUrl(),
            ]);
        }

        return view('threads.index', compact('threads', 'tab'));
    }

    public function show(Request $request, Post $thread): View
    {
        abort_unless($thread->status === 'published' && $thread->published_at <= now(), 404);

        $thread->load(['author', 'media', 'repostOf.author', 'repostOf.media', 'tags', 'parent.author']);

        $replies = Post::published()
            ->where('parent_id', $thread->id)
            ->with(['author', 'media', 'repostOf.author', 'repostOf.media', 'tags'])
            ->oldest('published_at')
            ->get();

        $thread->increment('views_count');

        return view('threads.show', compact('thread', 'replies'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'content' => 'required_without_all:media_ids,images,poll_options|nullable|string|max:2000',
            'parent_id' => 'nullable|exists:posts,id',
            'repost_of_id' => 'nullable|exists:posts,id',
            'type' => 'nullable|in:post,reply,repost,quote',
            'media_ids' => 'nullable|array|max:4',
            'media_ids.*' => 'exists:media,id',
            'images' => 'nullable|array|max:4',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'poll_options' => 'nullable|array|min:2|max:4',
            'poll_options.*' => 'nullable|string|max:100',
        ]);

        $type = $data['type'] ?? 'post';
        if (! empty($data['parent_id'])) {
            $type = 'reply';
        } elseif (! empty($data['repost_of_id']) && ! empty($data['content'])) {
            $type = 'quote';
        } elseif (! empty($data['repost_of_id'])) {
            $type = 'repost';
        }

        $post = DB::transaction(function () use ($request, $data, $type): Post {
            $content = trim($data['content'] ?? '');

            $pollData = null;
            $rawPoll = $request->input('poll_options', []);
            if (is_array($rawPoll)) {
                $cleanOptions = array_values(array_filter(array_map('trim', $rawPoll)));
                if (count($cleanOptions) >= 2) {
                    $optionsList = [];
                    foreach ($cleanOptions as $idx => $optText) {
                        $optionsList[] = [
                            'id' => $idx + 1,
                            'text' => $optText,
                            'votes' => 0,
                        ];
                    }
                    $pollData = [
                        'is_poll' => true,
                        'options' => $optionsList,
                        'voters' => (object) [],
                        'total_votes' => 0,
                        'duration_hours' => 24,
                        'expires_at' => now()->addHours(24)->toIso8601String(),
                    ];
                }
            }

            $post = Post::create([
                'user_id' => $request->user()->id,
                'parent_id' => $data['parent_id'] ?? null,
                'repost_of_id' => $data['repost_of_id'] ?? null,
                'type' => $type,
                'title' => Str::limit(strip_tags($content ?: ($cleanOptions[0] ?? 'Cuộc thăm dò ý kiến')), 60, ''),
                'slug' => 'thread-'.Str::lower(Str::random(10)).'-'.time(),
                'excerpt' => $pollData ? json_encode($pollData, JSON_UNESCAPED_UNICODE) : null,
                'content' => $content,
                'status' => 'published',
                'published_at' => now(),
            ]);

            $mediaIds = $data['media_ids'] ?? [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $imgFile) {
                    $dimensions = @getimagesize($imgFile->getRealPath()) ?: [null, null];
                    $path = app(ImageStorage::class)->store($imgFile, 'media');
                    $m = Media::create([
                        'user_id' => $request->user()->id,
                        'path' => $path,
                        'original_name' => mb_substr(basename($imgFile->getClientOriginalName()), 0, 255),
                        'mime_type' => $imgFile->getMimeType(),
                        'size' => $imgFile->getSize(),
                        'width' => $dimensions[0] ?? null,
                        'height' => $dimensions[1] ?? null,
                        'alt' => 'Thread attachment',
                    ]);
                    $mediaIds[] = $m->id;
                }
            }

            if (! empty($mediaIds)) {
                $post->media()->sync(array_slice(array_unique($mediaIds), 0, 4));
            }

            preg_match_all('/#([\p{L}\p{N}_]+)/u', $content, $matches);
            if (! empty($matches[1])) {
                $tagIds = [];
                foreach (array_unique($matches[1]) as $tagName) {
                    $tag = Tag::firstOrCreate(
                        ['slug' => Str::slug($tagName)],
                        ['name' => $tagName]
                    );
                    $tagIds[] = $tag->id;
                }
                $post->tags()->sync($tagIds);
            }

            if ($post->parent_id) {
                Post::where('id', $post->parent_id)->increment('replies_count');
            }
            if ($post->repost_of_id) {
                Post::where('id', $post->repost_of_id)->increment('reposts_count');
            }

            return $post;
        });

        $author = $request->user();
        $notifiedUserIds = [$author->id];

        if ($post->parent_id) {
            $parent = Post::with('author')->find($post->parent_id);
            if ($parent && $parent->user_id && ! in_array($parent->user_id, $notifiedUserIds, true)) {
                $notifiedUserIds[] = $parent->user_id;
                $parent->author?->notify(new ThreadInteractionNotification([
                    'action' => 'reply',
                    'actor_id' => $author->id,
                    'actor_name' => $author->name,
                    'actor_handle' => $author->username ? '@'.$author->username : '@'.Str::slug($author->name, '_'),
                    'actor_avatar' => $author->avatar_path ? route('avatar.show', $author->id) : null,
                    'thread_id' => $post->id,
                    'content_snippet' => Str::limit(strip_tags($data['content'] ?? ''), 80),
                    'url' => route('threads.show', $post->id),
                ]));
            }
        }

        preg_match_all('/@([A-Za-z0-9_]{3,30})/', $data['content'] ?? '', $mentionMatches);
        if (! empty($mentionMatches[1])) {
            $mentionedUsernames = array_unique($mentionMatches[1]);
            $mentionedUsers = User::whereIn('username', $mentionedUsernames)
                ->whereNotIn('id', $notifiedUserIds)
                ->get();

            foreach ($mentionedUsers as $mentionedUser) {
                $notifiedUserIds[] = $mentionedUser->id;
                $mentionedUser->notify(new ThreadInteractionNotification([
                    'action' => 'mention',
                    'actor_id' => $author->id,
                    'actor_name' => $author->name,
                    'actor_handle' => $author->username ? '@'.$author->username : '@'.Str::slug($author->name, '_'),
                    'actor_avatar' => $author->avatar_path ? route('avatar.show', $author->id) : null,
                    'thread_id' => $post->id,
                    'content_snippet' => Str::limit(strip_tags($data['content'] ?? ''), 80),
                    'url' => route('threads.show', $post->id),
                ]));
            }
        }

        if ($request->wantsJson()) {
            $post->load(['author', 'media', 'repostOf.author', 'repostOf.media']);

            return response()->json([
                'success' => true,
                'message' => 'Đã đăng bài viết thành công.',
                'thread' => $post,
            ]);
        }

        return back()->with('success', $post->parent_id ? 'Đã trả lời bài viết.' : 'Đã đăng lên Threads.');
    }

    public function destroy(Request $request, Post $thread): RedirectResponse|JsonResponse
    {
        if ($thread->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'Bạn không có quyền xóa bài viết này.');
        }

        DB::transaction(function () use ($thread): void {
            if ($thread->parent_id) {
                Post::where('id', $thread->parent_id)->decrement('replies_count');
            }
            if ($thread->repost_of_id) {
                Post::where('id', $thread->repost_of_id)->decrement('reposts_count');
            }
            $thread->delete();
        });

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã xóa bài viết.']);
        }

        return redirect()->route('threads.index')->with('success', 'Đã xóa bài viết.');
    }

    public function uploadMedia(Request $request): JsonResponse
    {
        $files = $request->file('images') ?? ($request->file('image') ? [$request->file('image')] : []);

        if (empty($files)) {
            return response()->json(['message' => 'Không tìm thấy file ảnh hợp lệ.'], 422);
        }

        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp,gif|max:5120',
        ]);

        $uploaded = [];
        foreach ($files as $file) {
            $dimensions = @getimagesize($file->getRealPath()) ?: [null, null];
            $path = app(ImageStorage::class)->store($file, 'media');

            $media = Media::create([
                'user_id' => $request->user()->id,
                'path' => $path,
                'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'width' => $dimensions[0] ?? null,
                'height' => $dimensions[1] ?? null,
                'alt' => 'Thread attachment',
            ]);

            $uploaded[] = [
                'id' => $media->id,
                'url' => route('media.show', $media->id),
            ];
        }

        if (count($uploaded) === 1 && $request->hasFile('image')) {
            return response()->json($uploaded[0]);
        }

        return response()->json([
            'items' => $uploaded,
            'id' => $uploaded[0]['id'],
            'url' => $uploaded[0]['url'],
        ]);
    }

    public function votePoll(Request $request, Post $thread): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Vui lòng đăng nhập để bình chọn.'], 401);
        }

        $validated = $request->validate([
            'option_id' => 'required|integer',
        ]);

        $poll = json_decode($thread->excerpt ?? '', true);
        if (! is_array($poll) || empty($poll['is_poll']) || empty($poll['options'])) {
            return response()->json(['message' => 'Bài viết này không có cuộc thăm dò ý kiến.'], 400);
        }

        $userId = (string) $user->id;
        $voters = $poll['voters'] ?? [];
        if (! is_array($voters)) {
            $voters = (array) $voters;
        }

        $chosenOptionId = (int) $validated['option_id'];

        $validOption = false;
        foreach ($poll['options'] as $opt) {
            if ($opt['id'] === $chosenOptionId) {
                $validOption = true;
                break;
            }
        }
        if (! $validOption) {
            return response()->json(['message' => 'Lựa chọn không hợp lệ.'], 400);
        }

        $previousVote = $voters[$userId] ?? null;
        if ($previousVote === $chosenOptionId) {
            return response()->json(['success' => true, 'poll' => $this->formatPollResponse($poll, $userId)]);
        }

        if ($previousVote !== null) {
            foreach ($poll['options'] as &$opt) {
                if ($opt['id'] === $previousVote && $opt['votes'] > 0) {
                    $opt['votes']--;
                    $poll['total_votes'] = max(0, ($poll['total_votes'] ?? 1) - 1);
                }
            }
            unset($opt);
        }

        foreach ($poll['options'] as &$opt) {
            if ($opt['id'] === $chosenOptionId) {
                $opt['votes']++;
                $poll['total_votes'] = ($poll['total_votes'] ?? 0) + 1;
            }
        }
        unset($opt);

        $voters[$userId] = $chosenOptionId;
        $poll['voters'] = $voters;

        $thread->update(['excerpt' => json_encode($poll, JSON_UNESCAPED_UNICODE)]);

        return response()->json([
            'success' => true,
            'poll' => $this->formatPollResponse($poll, $userId),
        ]);
    }

    private function formatPollResponse(array $poll, string $userId): array
    {
        $total = max(1, $poll['total_votes'] ?? 0);
        $options = [];
        foreach ($poll['options'] as $opt) {
            $votes = $opt['votes'] ?? 0;
            $percent = ($poll['total_votes'] ?? 0) > 0 ? round(($votes / $total) * 100) : 0;
            $options[] = [
                'id' => $opt['id'],
                'text' => $opt['text'],
                'votes' => $votes,
                'percentage' => $percent,
            ];
        }

        $voters = $poll['voters'] ?? [];
        if (! is_array($voters)) {
            $voters = (array) $voters;
        }

        return [
            'options' => $options,
            'total_votes' => $poll['total_votes'] ?? 0,
            'user_voted_option' => $voters[$userId] ?? null,
        ];
    }
}
