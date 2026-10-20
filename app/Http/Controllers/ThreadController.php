<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreThreadRequest;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\ThreadText;
use App\Services\ViewRecorder;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ThreadController extends Controller
{
    public function __construct(private ThreadText $text) {}

    public function index(Request $request): View|JsonResponse|RedirectResponse
    {
        $filters = $request->validate(['tab' => 'nullable|in:for-you,following', 'q' => 'nullable|string|max:150']);
        $tab = $filters['tab'] ?? 'for-you';
        if ($tab === 'following' && ! $request->user()) {
            return redirect()->route('login');
        }
        $query = Post::published()->withThreadContext($request->user())->whereNull('parent_id')
            ->where(fn (Builder $query): Builder => $query->whereNull('repost_of_id')->orWhereHas('repostOf', fn (Builder $original): Builder => $original->published()));
        if ($tab === 'following') {
            $followingIds = $request->user()->following()->pluck('users.id');
            $query->whereIn('user_id', $followingIds)->where('user_id', '!=', $request->user()->id);
        }
        if (! empty($filters['q'])) {
            $query->where(fn (Builder $query): Builder => $query->where('content', 'like', '%'.$filters['q'].'%')->orWhere('title', 'like', '%'.$filters['q'].'%'));
        }
        $threads = $query->latest('published_at')->latest('id')->paginate(15)->withQueryString();
        if ($request->expectsJson()) {
            return response()->json(['html' => view('threads.partials.thread_list', compact('threads'))->render(), 'nextPageUrl' => $threads->nextPageUrl()]);
        }

        return view('threads.index', compact('threads', 'tab'));
    }

    public function show(Request $request, Post $thread, ViewRecorder $recorder): View
    {
        abort_unless(Post::published()->whereKey($thread->id)->exists(), 404);
        $thread = Post::withThreadContext($request->user())->with('parent.author')->findOrFail($thread->id);
        $replies = $thread->replies()->withThreadContext($request->user())->reorder()->oldest('published_at')->oldest('id')->paginate(20, ['*'], 'replies_page');
        $recorder->record($request, $thread);

        return view('threads.show', compact('thread', 'replies'));
    }

    public function store(StoreThreadRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $post = DB::transaction(function () use ($request, $data): Post {
            $parent = ! empty($data['parent_id']) ? Post::published()->lockForUpdate()->findOrFail($data['parent_id']) : null;
            $original = ! empty($data['repost_of_id']) ? Post::published()->lockForUpdate()->findOrFail($data['repost_of_id']) : null;
            $post = $this->publish($request->user(), $data['content'] ?? '', $parent, $original, $data['content_format'] ?? 'plain');
            $post->media()->sync($data['media_ids'] ?? []);
            $previous = $post;
            foreach ($data['continuations'] ?? [] as $content) {
                $previous = $this->publish($request->user(), $content, $previous, null, $data['content_format'] ?? 'plain');
            }

            return $post;
        });
        $post = Post::withThreadContext($request->user())->findOrFail($post->id);
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'id' => $post->id, 'parent_id' => $post->parent_id, 'parent_replies_count' => $post->parent?->replies()->count(), 'repost_of_id' => $post->repost_of_id, 'original_reposts_count' => $post->repostOf?->reposts_count, 'url' => route('threads.show', $post), 'html' => view('threads.partials.thread_card', ['thread' => $post])->render()], 201);
        }

        return redirect()->route('threads.show', $post)->with('success', 'Đã đăng bài viết.');
    }

    public function update(StoreThreadRequest $request, Post $thread): JsonResponse|RedirectResponse
    {
        abort_unless($thread->user_id === $request->user()->id, 403);
        abort_unless(Str::startsWith($thread->slug ?? '', 'thread-'), 403);
        $data = $request->validated();
        DB::transaction(function () use ($thread, $data): void {
            $storedContent = $this->text->store($data['content'] ?? '', $data['content_format'] ?? 'plain');
            $plain = $this->text->plain($storedContent);
            $thread->update(['content' => $storedContent, 'title' => Str::limit($plain, 60, ''), 'excerpt' => Str::limit($plain, 200)]);
            $thread->media()->sync($data['media_ids'] ?? []);
            $this->syncTags($thread, $plain);
        });
        $thread = Post::withThreadContext($request->user())->findOrFail($thread->id);
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'html' => view('threads.partials.thread_card', compact('thread'))->render()]);
        }

        return back()->with('success', 'Đã cập nhật bài viết.');
    }

    public function destroy(Request $request, Post $thread): RedirectResponse|JsonResponse
    {
        abort_unless($thread->user_id === $request->user()->id || $request->user()->isAdmin(), 403);
        DB::transaction(function () use ($thread): void {
            $thread->delete();
            if ($thread->parent_id) {
                $parent = Post::lockForUpdate()->find($thread->parent_id);
                if ($parent) {
                    $parent->update(['replies_count' => $parent->replies()->count()]);
                }
            }
            if ($thread->repost_of_id) {
                $original = Post::lockForUpdate()->find($thread->repost_of_id);
                if ($original) {
                    $original->update(['reposts_count' => $original->reposts()->published()->count()]);
                }
            }
        });
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect' => route('home')]);
        }

        return redirect()->route('home')->with('success', 'Đã xóa bài viết.');
    }

    public function uploadMedia(Request $request): JsonResponse
    {
        $request->validate(['image' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120|dimensions:max_width=6000,max_height=6000']);
        $file = $request->file('image');
        $dimensions = getimagesize($file->getRealPath());
        $path = $file->store('media', 'local');
        try {
            $media = Media::create(['user_id' => $request->user()->id, 'path' => $path, 'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize(), 'width' => $dimensions[0], 'height' => $dimensions[1], 'alt' => 'Ảnh đính kèm bài viết']);
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }

        return response()->json(['id' => $media->id, 'url' => $media->url(), 'alt' => $media->alt], 201);
    }

    private function publish(User $author, string $content, ?Post $parent = null, ?Post $original = null, string $format = 'plain'): Post
    {
        $storedContent = $this->text->store($content, $format);
        $plain = $this->text->plain($storedContent);
        $post = Post::create(['user_id' => $author->id, 'parent_id' => $parent?->id, 'repost_of_id' => $original?->id, 'type' => $parent ? 'reply' : ($original ? 'quote' : 'post'), 'title' => Str::limit($plain, 60, ''), 'slug' => 'thread-'.Str::uuid(), 'excerpt' => Str::limit($plain, 200), 'content' => $storedContent, 'status' => 'published', 'published_at' => now()]);
        $this->syncTags($post, $plain);
        $parent?->increment('replies_count');
        $original?->increment('reposts_count');

        return $post;
    }

    private function syncTags(Post $post, string $content): void
    {
        preg_match_all('/#([\p{L}\p{N}_]{1,60})/u', $content, $matches);
        $tagIds = [];
        foreach (array_slice(array_unique($matches[1]), 0, 10) as $name) {
            $tagIds[] = Tag::firstOrCreate(['slug' => Str::slug($name) ?: 'tag-'.hash('sha256', $name)], ['name' => $name])->id;
        }
        $post->tags()->sync($tagIds);
    }
}
