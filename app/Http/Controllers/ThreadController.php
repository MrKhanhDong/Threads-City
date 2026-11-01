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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

    public function index(Request $request): View|JsonResponse|RedirectResponse
    {
        $filters = $request->validate(['tab' => 'nullable|in:for-you,following', 'q' => 'nullable|string|max:150', 'search' => 'nullable|boolean']);
        if ($request->boolean('search')) {
            return redirect()->route('search.index', $request->only(['q', 'type', 'period', 'scope', 'category', 'tag', 'page']));
        }
        $tab = $filters['tab'] ?? 'for-you';
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

        if ($user = $request->user()) {
            $hiddenIds = DB::table('hidden_posts')->where('user_id', $user->id)->pluck('post_id');
            if ($hiddenIds->isNotEmpty()) {
                $query->whereNotIn('id', $hiddenIds);
            }

            $blocks = DB::table('user_blocks')
                ->where('user_id', $user->id)
                ->orWhere('blocked_user_id', $user->id)
                ->get(['user_id', 'blocked_user_id']);

            if ($blocks->isNotEmpty()) {
                $allBlocked = $blocks->flatMap(fn ($b) => [$b->user_id, $b->blocked_user_id])
                    ->reject(fn ($id) => (int) $id === (int) $user->id)
                    ->unique();

                if ($allBlocked->isNotEmpty()) {
                    $query->whereNotIn('user_id', $allBlocked);
                }
            }
        }

        if (! empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('content', 'like', '%'.$search.'%')
                    ->orWhereHas('tags', fn ($tags) => $tags->where('name', 'like', '%'.ltrim($search, '#').'%'));
            });
        }
        $threads = $query->paginate(15)->withQueryString();
        $userInteractions = $this->batchPreloadInteractions($request->user(), $threads->items());

        if ($request->ajax() && $request->wantsJson()) {
            return response()->json([
                'html' => view('threads.partials.thread_list', ['threads' => $threads, 'userInteractions' => $userInteractions])->render(),
                'hasMore' => $threads->hasMorePages(),
                'nextPageUrl' => $threads->nextPageUrl(),
            ]);
        }

        return view('threads.index', compact('threads', 'tab', 'userInteractions'));
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

        if ($request->header('X-Threads-Live') !== '1') {
            $thread->increment('views_count');
        }

        $allCards = collect([$thread])->merge($replies);
        $userInteractions = $this->batchPreloadInteractions($request->user(), $allCards);

        return view('threads.show', compact('thread', 'replies', 'userInteractions'));
    }

    /**
     * Batch preload user interactions to eliminate N+1 database queries.
     *
     * @param  iterable<Post>  $posts
     * @return array<string, array<int, int>>
     */
    public function batchPreloadInteractions(?User $user, iterable $posts): array
    {
        $interactions = [
            'liked' => [],
            'bookmarked' => [],
            'reposted' => [],
            'muted' => [],
            'following' => [],
            'restricted' => [],
            'blocked' => [],
        ];

        if (! $user) {
            return $interactions;
        }

        $postIds = [];
        $authorIds = [];

        foreach ($posts as $post) {
            $postIds[] = $post->id;
            $authorIds[] = $post->user_id;
            if ($post->repost_of_id) {
                $postIds[] = $post->repost_of_id;
            }
            if ($post->repostOf) {
                $authorIds[] = $post->repostOf->user_id;
            }
        }

        $postIds = array_values(array_unique(array_filter($postIds)));
        $authorIds = array_values(array_unique(array_filter($authorIds)));

        if (! empty($postIds)) {
            $interactions['liked'] = DB::table('post_likes')
                ->where('user_id', $user->id)
                ->whereIn('post_id', $postIds)
                ->pluck('post_id')
                ->flip()
                ->toArray();

            $interactions['bookmarked'] = DB::table('bookmarks')
                ->where('user_id', $user->id)
                ->whereIn('post_id', $postIds)
                ->pluck('post_id')
                ->flip()
                ->toArray();

            $interactions['reposted'] = DB::table('posts')
                ->where('user_id', $user->id)
                ->whereIn('repost_of_id', $postIds)
                ->where('type', 'repost')
                ->pluck('repost_of_id')
                ->flip()
                ->toArray();

            $interactions['muted'] = DB::table('muted_posts')
                ->where('user_id', $user->id)
                ->whereIn('post_id', $postIds)
                ->pluck('post_id')
                ->flip()
                ->toArray();
        }

        if (! empty($authorIds)) {
            $interactions['following'] = DB::table('follows')
                ->where('follower_id', $user->id)
                ->whereIn('following_id', $authorIds)
                ->pluck('following_id')
                ->flip()
                ->toArray();

            $interactions['restricted'] = DB::table('user_restrictions')
                ->where('user_id', $user->id)
                ->whereIn('restricted_user_id', $authorIds)
                ->pluck('restricted_user_id')
                ->flip()
                ->toArray();

            $interactions['blocked'] = DB::table('user_blocks')
                ->where('user_id', $user->id)
                ->whereIn('blocked_user_id', $authorIds)
                ->pluck('blocked_user_id')
                ->flip()
                ->toArray();
        }

        return $interactions;
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $mediaLimit = (int) config('blog.thread_media_limit');
        if (is_array($request->input('poll_options'))) {
            $request->merge(['poll_options' => array_map(fn ($option) => is_string($option) ? trim($option) : $option, $request->input('poll_options'))]);
        }
        if ($request->input('poll_options') === []) {
            $request->merge(['poll_options' => null]);
        }

        $data = $request->validate([
            'content' => 'required_without_all:media_ids,images,poll_options,attached_document_html,location,repost_of_id|nullable|string|max:2000',
            'parent_id' => 'nullable|exists:posts,id',
            'repost_of_id' => 'nullable|exists:posts,id',
            'type' => 'nullable|in:post,reply,repost,quote',
            'media_ids' => 'nullable|array|max:'.$mediaLimit,
            'media_ids.*' => ['integer', 'distinct', Rule::exists('media', 'id')->where('user_id', $request->user()?->id)],
            'images' => 'nullable|array|max:'.$mediaLimit,
            'images.*' => 'image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'poll_options' => 'nullable|array|min:2|max:4',
            'poll_options.*' => 'required|string|max:100|distinct:ignore_case',
            'poll_duration_hours' => 'nullable|integer|in:1,6,24,72,168',
            'attached_document_html' => 'nullable|string|max:50000',
            'location' => 'nullable|string|max:150',
        ]);

        if (count($data['media_ids'] ?? []) + count($request->file('images', [])) > $mediaLimit) {
            throw ValidationException::withMessages(['media_ids' => "Mỗi bài viết tối đa {$mediaLimit} tệp đính kèm."]);
        }

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
            $rawPoll = $data['poll_options'] ?? [];
            if (is_array($rawPoll)) {
                $cleanOptions = array_values(array_filter(array_map('trim', $rawPoll), fn (string $option): bool => $option !== ''));
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
                        'duration_hours' => (int) ($data['poll_duration_hours'] ?? 24),
                        'expires_at' => now()->addHours((int) ($data['poll_duration_hours'] ?? 24))->toIso8601String(),
                    ];
                }
            }

            $attachedDoc = null;
            if (! empty($data['attached_document_html'])) {
                $rawDoc = trim($data['attached_document_html']);
                $cleanDoc = strip_tags($rawDoc, '<b><strong><i><em><u><s><strike><code><pre><p><div><br><mark>');
                if (! empty(trim(strip_tags($cleanDoc)))) {
                    $attachedDoc = $cleanDoc;
                }
            }

            $location = ! empty($data['location']) ? trim(strip_tags($data['location'])) : null;

            $metaExcerpt = [];
            if ($pollData) {
                $metaExcerpt = $pollData;
            }
            if ($attachedDoc) {
                $metaExcerpt['attached_doc'] = $attachedDoc;
            }
            if ($location) {
                $metaExcerpt['location'] = $location;
            }

            $fallbackTitle = 'Bài viết Threads';
            if ($content) {
                $fallbackTitle = $content;
            } elseif (! empty($cleanOptions[0])) {
                $fallbackTitle = $cleanOptions[0];
            } elseif ($attachedDoc) {
                $fallbackTitle = strip_tags($attachedDoc);
            } elseif ($location) {
                $fallbackTitle = '📍 '.$location;
            }

            $post = Post::create([
                'user_id' => $request->user()->id,
                'parent_id' => $data['parent_id'] ?? null,
                'repost_of_id' => $data['repost_of_id'] ?? null,
                'type' => $type,
                'title' => Str::limit(strip_tags($fallbackTitle), 60, ''),
                'slug' => 'thread-'.Str::lower(Str::random(10)).'-'.time(),
                'excerpt' => ! empty($metaExcerpt) ? json_encode($metaExcerpt, JSON_UNESCAPED_UNICODE) : null,
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
                $post->media()->sync(array_unique($mediaIds));
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

        if ($post->repost_of_id) {
            $quotedTarget = Post::with('author')->find($post->repost_of_id);
            if ($quotedTarget && $quotedTarget->user_id && ! in_array($quotedTarget->user_id, $notifiedUserIds, true)) {
                $notifiedUserIds[] = $quotedTarget->user_id;
                $quotedTarget->author?->notify(new ThreadInteractionNotification([
                    'action' => $post->type === 'quote' ? 'quote' : 'repost',
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
        if ($request->hasFile('file')) {
            $data = $request->validate([
                'kind' => 'required|in:video,audio',
                'file' => 'required|file|mimetypes:video/mp4,video/webm,audio/webm,audio/mp4,audio/x-m4a,audio/ogg,audio/wav,audio/x-wav|max:25600',
                'duration' => 'required|numeric|gt:0|max:60',
                'waveform' => 'nullable|array|min:1|max:48',
                'waveform.*' => 'required|integer|min:0|max:100',
            ]);
            $file = $request->file('file');
            if ($data['kind'] === 'video' && ! in_array($file->getMimeType(), ['video/mp4', 'video/webm'], true)) {
                throw ValidationException::withMessages(['file' => 'Video phải có định dạng MP4 hoặc WebM.']);
            }
            if ($data['kind'] === 'audio' && $file->getSize() > 5 * 1024 * 1024) {
                throw ValidationException::withMessages(['file' => 'Ghi âm tối đa 5 MB.']);
            }
            $path = app(ImageStorage::class)->store($file, 'media');
            try {
                $mime = $data['kind'] === 'audio' ? str_replace('video/', 'audio/', $file->getMimeType()) : $file->getMimeType();
                $media = Media::create([
                    'user_id' => $request->user()->id, 'path' => $path,
                    'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                    'mime_type' => $mime, 'size' => $file->getSize(),
                    'duration_ms' => (int) round($data['duration'] * 1000),
                    'waveform' => $data['kind'] === 'audio' ? ($data['waveform'] ?? []) : null,
                ]);
            } catch (\Throwable $error) {
                app(ImageStorage::class)->delete($path);
                throw $error;
            }

            return response()->json(['id' => $media->id, 'url' => $media->url(), 'kind' => $data['kind'], 'duration' => $data['duration'], 'waveform' => $media->waveform]);
        }
        $files = $request->file('images') ?? ($request->file('image') ? [$request->file('image')] : []);

        if (empty($files)) {
            return response()->json(['message' => 'Không tìm thấy file ảnh hợp lệ.'], 422);
        }

        $request->validate([
            'images' => 'nullable|array|max:'.config('blog.thread_media_limit'),
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

        return DB::transaction(function () use ($thread, $validated, $user): JsonResponse {
            $thread = Post::published()->whereNull('archived_at')->whereKey($thread->id)->lockForUpdate()->firstOrFail();
            abort_if($thread->author->is_locked, 404);
            $poll = json_decode($thread->excerpt ?? '', true);
            if (! is_array($poll) || empty($poll['is_poll']) || empty($poll['options'])) {
                return response()->json(['message' => 'Bài viết này không có cuộc thăm dò ý kiến.'], 400);
            }

            if (! empty($poll['expires_at']) && strtotime($poll['expires_at']) <= now()->timestamp) {
                return response()->json(['message' => 'Cuộc bình chọn đã kết thúc.', 'poll' => $this->formatPollResponse($poll, (string) $user->id)], 409);
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
        });
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
            'expires_at' => $poll['expires_at'] ?? null,
            'closed' => ! empty($poll['expires_at']) && strtotime($poll['expires_at']) <= now()->timestamp,
        ];
    }

    public function quotePickerThreads(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([], 401);
        }

        $tab = $request->query('tab', 'saved');

        if ($tab === 'liked') {
            $posts = $user->likedPosts()
                ->published()
                ->with(['author', 'media'])
                ->latest('post_likes.created_at')
                ->take(25)
                ->get();
        } elseif ($tab === 'mine') {
            $posts = $user->posts()
                ->published()
                ->with(['author', 'media'])
                ->latest('posts.created_at')
                ->take(25)
                ->get();
        } else {
            // Default: saved
            $posts = $user->bookmarks()
                ->published()
                ->with(['author', 'media'])
                ->latest('bookmarks.created_at')
                ->take(25)
                ->get();
        }

        $items = $posts->map(function (Post $p): array {
            $firstMedia = $p->media->first();

            return [
                'id' => $p->id,
                'author_name' => $p->author?->name ?? 'Người dùng',
                'author_username' => $p->author?->username ?? ($p->author?->name ?? 'user'),
                'author_avatar' => $p->author?->avatar_path ? route('avatar.show', $p->author->id) : null,
                'content' => Str::limit(strip_tags(preg_replace('/<br\s*\/?>/i', ' ', $p->content ?? '')), 140),
                'media_url' => $firstMedia ? route('media.show', $firstMedia->id) : null,
                'time_ago' => $p->published_at ? $p->published_at->diffForHumans(null, true) : 'vừa xong',
            ];
        });

        return response()->json($items);
    }
}
