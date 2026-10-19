<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ThreadController extends Controller
{
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
            'content' => 'required_without:media_ids|nullable|string|max:2000',
            'parent_id' => 'nullable|exists:posts,id',
            'repost_of_id' => 'nullable|exists:posts,id',
            'type' => 'nullable|in:post,reply,repost,quote',
            'media_ids' => 'nullable|array|max:4',
            'media_ids.*' => 'exists:media,id',
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
            $cleanContent = e($content);

            $post = Post::create([
                'user_id' => $request->user()->id,
                'parent_id' => $data['parent_id'] ?? null,
                'repost_of_id' => $data['repost_of_id'] ?? null,
                'type' => $type,
                'title' => Str::limit(strip_tags($content), 60, ''),
                'slug' => 'thread-'.Str::lower(Str::random(10)).'-'.time(),
                'content' => nl2br($cleanContent),
                'status' => 'published',
                'published_at' => now(),
            ]);

            if (! empty($data['media_ids'])) {
                $post->media()->sync($data['media_ids']);
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
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
        ]);

        $file = $request->file('image');
        $dimensions = @getimagesize($file->getRealPath()) ?: [null, null];
        $path = $file->store('media', 'local');

        $media = Media::create([
            'user_id' => $request->user()->id,
            'path' => $path,
            'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $dimensions[0],
            'height' => $dimensions[1],
            'alt' => 'Thread attachment',
        ]);

        return response()->json([
            'id' => $media->id,
            'url' => route('media.show', $media->id),
        ]);
    }
}
