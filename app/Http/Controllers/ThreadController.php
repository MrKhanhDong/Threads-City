<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Notifications\ThreadInteractionNotification;
use App\Services\ImageStorage;
use App\Services\Mentions;
use App\Services\MusicCatalog;
use App\Services\ThreadText;
use Illuminate\Contracts\Pagination\Paginator;
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
    public function mentionSuggestions(Request $request, Mentions $mentions): JsonResponse
    {
        $data = $request->validate(['q' => 'nullable|string|max:60']);
        $term = $data['q'] ?? '';
        $users = $mentions->candidates($request->user())->where(function ($users) use ($term): void {
            $users->where('username', 'like', addcslashes($term, '%_\\').'%')->orWhere('name', 'like', addcslashes($term, '%_\\').'%');
        })->orderBy('username')->limit(8)->get();

        return response()->json($users->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name, 'username' => $user->username, 'handle' => $user->handle(), 'url' => route('mentions.profile', $user), 'avatar_url' => $user->avatar_path ? route('avatar.show', $user) : null]))->header('Cache-Control', 'private, no-store');
    }

    public function index(Request $request): View|JsonResponse|RedirectResponse
    {
        $filters = $request->validate(['tab' => 'nullable|in:for-you,following', 'q' => 'nullable|string|max:150', 'search' => 'nullable|boolean']);
        if ($request->boolean('search')) {
            return redirect()->route('search.index', $request->only(['q', 'type', 'period', 'scope', 'category', 'tag', 'page']));
        }
        $tab = $filters['tab'] ?? 'for-you';
        $query = Post::published()->visibleTo($request->user(), true)
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
        abort_unless(Post::published()->visibleTo($request->user())->whereKey($thread->id)->exists(), 404);

        $thread->load(['author', 'media', 'repostOf.author', 'repostOf.media', 'tags', 'parent.author']);

        $replies = Post::published()->visibleTo($request->user())
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
        $posts = collect($posts instanceof Paginator ? $posts->items() : $posts);
        app(Mentions::class)->preloadPosts($posts);
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
        $mediaLimit = (int) config('blog.thread_media_limit', 20);
        if (is_array($request->input('poll_options'))) {
            $request->merge(['poll_options' => array_map(fn ($option) => is_string($option) ? trim($option) : $option, $request->input('poll_options'))]);
        }
        if ($request->input('poll_options') === []) {
            $request->merge(['poll_options' => null]);
        }

        $data = $request->validate([
            'content_format' => 'sometimes|in:plain,html',
            'mention_ids' => 'nullable|array|max:6',
            'mention_ids.*' => 'integer|distinct|exists:users,id',
            'continuations' => 'nullable|array|max:9',
            'continuations.*' => 'required|string|max:2000',
            'content' => 'required_without_all:media_ids,images,giphy_ids,music_id,poll_options,attached_document_html,location,repost_of_id|nullable|string|max:2000',
            'parent_id' => 'nullable|exists:posts,id',
            'repost_of_id' => 'nullable|exists:posts,id',
            'type' => 'nullable|in:post,reply,repost,quote',
            'media_ids' => 'nullable|array|max:'.$mediaLimit,
            'media_ids.*' => ['integer', 'distinct', Rule::exists('media', 'id')->where('user_id', $request->user()?->id)],
            'images' => 'nullable|array|max:'.$mediaLimit,
            'images.*' => 'image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'giphy_ids' => 'nullable|array|max:'.$mediaLimit,
            'giphy_ids.*' => ['required', 'string', 'regex:/\A[a-zA-Z0-9]{1,100}\z/', 'distinct'],
            'music_id' => ['nullable', 'string', 'regex:/\A[a-zA-Z0-9]{1,64}\z/'],
            'poll_options' => 'nullable|array|min:2|max:4',
            'poll_options.*' => 'required|string|max:100|distinct:ignore_case',
            'poll_duration_hours' => 'nullable|integer|in:1,6,24,72,168',
            'attached_document_html' => 'nullable|string|max:50000',
            'location' => 'nullable|string|max:150',
        ]);

        if (count($data['media_ids'] ?? []) + count($request->file('images', [])) + count($data['giphy_ids'] ?? []) > $mediaLimit) {
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

        $music = ! empty($data['music_id']) ? app(MusicCatalog::class)->track($data['music_id']) : null;
        $post = DB::transaction(function () use ($request, $data, $type, $music): Post {
            $content = trim($data['content'] ?? '');
            if (($data['content_format'] ?? 'plain') === 'html') {
                $content = app(ThreadText::class)->clean($content);
            }
            foreach (['parent_id', 'repost_of_id'] as $targetField) {
                if (! empty($data[$targetField])) {
                    $target = Post::published()->visibleTo($request->user())->visibleTo($request->user())->lockForUpdate()->findOrFail($data[$targetField]);
                    abort_if($targetField === 'repost_of_id' && $target->author->is_private, 403);
                }
            }

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
            if (! empty($data['giphy_ids'])) {
                $metaExcerpt['giphy_ids'] = $data['giphy_ids'];
            }
            if ($music) {
                $metaExcerpt['music'] = $music;
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
            } elseif ($music) {
                $fallbackTitle = $music['title'];
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
                'content_format' => $data['content_format'] ?? 'plain',
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

            $mentions = app(Mentions::class);
            $mentions->sync($post, $request->user(), $post->content_format === 'html' ? $mentions->fromHtml($post->content) : $mentions->fromPlain($post, $data['mention_ids'] ?? []));
            $previous = $post;
            foreach ($data['continuations'] ?? [] as $continuation) {
                $previous->increment('replies_count');
                $previous = Post::create(['user_id' => $request->user()->id, 'parent_id' => $previous->id, 'type' => 'reply', 'title' => Str::limit(strip_tags($continuation), 60), 'slug' => 'thread-'.Str::uuid(), 'content' => ($data['content_format'] ?? 'plain') === 'html' ? app(ThreadText::class)->clean($continuation) : $continuation, 'content_format' => $data['content_format'] ?? 'plain', 'status' => 'published', 'published_at' => now()]);
                $mentions->sync($previous, $request->user(), $previous->content_format === 'html' ? $mentions->fromHtml($previous->content) : []);
            }

            return $post;
        });

        $author = $request->user();
        $notifiedUserIds = [$author->id, ...DB::table('content_mentions')->where('post_id', $post->id)->where('active', true)->pluck('user_id')->all()];

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

        if ($request->wantsJson()) {
            $post->load(['author', 'media', 'repostOf.author', 'repostOf.media']);

            return response()->json([
                'success' => true,
                'message' => 'Đã đăng bài viết thành công.',
                'thread' => $post,
                'id' => $post->id,
                'url' => route('threads.show', $post),
                'html' => view('threads.partials.thread_card', ['thread' => $post, 'userInteractions' => $this->batchPreloadInteractions($request->user(), [$post])])->render(),
            ]);
        }

        return back()->with('success', $post->parent_id ? 'Đã trả lời bài viết.' : 'Đã đăng lên Threads.');
    }

    public function update(Request $request, Post $thread): JsonResponse
    {
        abort_unless($thread->user_id === $request->user()->id, 403);
        $mediaLimit = (int) config('blog.thread_media_limit');
        $data = $request->validate(['content' => 'nullable|string|max:20000', 'content_format' => 'sometimes|in:plain,html', 'media_ids' => 'sometimes|array|max:'.$mediaLimit, 'media_ids.*' => ['integer', 'distinct', Rule::exists('media', 'id')->where('user_id', $request->user()?->id)], 'mention_ids' => 'nullable|array|max:6', 'mention_ids.*' => 'integer|distinct|exists:users,id']);
        $post = DB::transaction(function () use ($request, $thread, $data): Post {
            $post = Post::lockForUpdate()->findOrFail($thread->id);
            $format = $data['content_format'] ?? $post->content_format ?? 'plain';
            $value = array_key_exists('content', $data) ? ($data['content'] ?? '') : $post->content;
            $content = $format === 'html' ? app(ThreadText::class)->clean($value) : $value;
            $plain = $format === 'html' ? app(ThreadText::class)->plain($content) : trim($content);
            $hasMedia = array_key_exists('media_ids', $data) ? count($data['media_ids']) > 0 : $post->media()->exists();
            $metadata = json_decode($post->excerpt ?? '', true);
            $hasAttachment = is_array($metadata) && (! empty($metadata['is_poll']) || ! empty($metadata['location']) || ! empty($metadata['attached_doc']) || ! empty($metadata['giphy_ids']) || ! empty($metadata['music']));
            if (($plain === '' && ! $hasMedia && ! $hasAttachment) || mb_strlen($plain) > 2000) {
                throw ValidationException::withMessages(['content' => 'Nhập nội dung hoặc đính kèm ảnh, bình chọn, vị trí hay tài liệu. Nội dung tối đa 2000 ký tự.']);
            }
            $post->update(['content' => $content, 'content_format' => $format, 'title' => Str::limit($plain, 60, '')]);
            if (array_key_exists('media_ids', $data)) {
                $post->media()->sync($data['media_ids']);
            }
            $mentions = app(Mentions::class);
            $ids = $data['mention_ids'] ?? DB::table('content_mentions')->where('post_id', $post->id)->where('active', true)->pluck('user_id')->all();
            $mentions->sync($post, $request->user(), $post->content_format === 'html' ? $mentions->fromHtml($content) : $mentions->fromPlain($post, $ids));

            return $post;
        });
        $post->load(['author', 'media', 'tags', 'repostOf.author', 'repostOf.media']);

        return response()->json(['success' => true, 'thread' => $post, 'html' => view('threads.partials.thread_card', ['thread' => $post, 'userInteractions' => $this->batchPreloadInteractions($request->user(), [$post])])->render()]);
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
            $thread = Post::published()->visibleTo($user)->whereNull('archived_at')->whereKey($thread->id)->lockForUpdate()->firstOrFail();
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
                ->published()->visibleTo($request->user())
                ->with(['author', 'media'])
                ->latest('post_likes.created_at')
                ->take(25)
                ->get();
        } elseif ($tab === 'mine') {
            $posts = $user->posts()
                ->published()->visibleTo($request->user())
                ->with(['author', 'media'])
                ->latest('posts.created_at')
                ->take(25)
                ->get();
        } else {
            // Default: saved
            $posts = $user->bookmarks()
                ->published()->visibleTo($request->user())
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
