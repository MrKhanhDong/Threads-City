<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostRedirect;
use App\Models\Tag;
use App\Services\CommentThreads;
use App\Services\HtmlSanitizer;
use App\Services\ViewRecorder;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BlogController extends Controller
{
    public function home(): View
    {
        $featured = Post::published()->where('is_featured', true)->with(['author', 'cover', 'categories'])->latest('published_at')->first();
        $posts = Post::published()->with(['author', 'cover', 'categories'])->when($featured, fn (Builder $query): Builder => $query->whereKeyNot($featured->id))->latest('published_at')->paginate(6);

        return view('blog.home', compact('featured', 'posts'));
    }

    public function archive(Request $request): View
    {
        $filters = $request->validate(['q' => 'nullable|string|max:150', 'category' => 'nullable|string|max:150', 'tag' => 'nullable|string|max:100']);
        $posts = Post::published()->with(['author', 'cover', 'categories', 'tags'])
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')->orWhere('excerpt', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['category'] ?? null, fn (Builder $query, string $slug): Builder => $query->whereHas('categories', fn (Builder $category): Builder => $category->where('slug', $slug)))
            ->when($filters['tag'] ?? null, fn (Builder $query, string $slug): Builder => $query->whereHas('tags', fn (Builder $tag): Builder => $tag->where('slug', $slug)))
            ->latest('published_at')->paginate(9)->withQueryString();

        return view('blog.archive', ['posts' => $posts, 'filters' => $filters, 'heading' => 'Tất cả bài viết', 'subtitle' => 'Tìm một câu chuyện dành cho bạn.', 'tags' => Tag::orderBy('name')->get()]);
    }

    public function category(Category $category): RedirectResponse
    {
        return redirect()->route('posts.index', ['category' => $category->slug]);
    }

    public function tag(Tag $tag): RedirectResponse
    {
        return redirect()->route('posts.index', ['tag' => $tag->slug]);
    }

    public function show(Request $request, string $slug, HtmlSanitizer $sanitizer, ViewRecorder $recorder): View|RedirectResponse
    {
        $post = Post::published()->where('slug', $slug)->with(['author', 'cover', 'ogImage', 'categories', 'tags'])->first();
        if (! $post) {
            $redirect = PostRedirect::where('slug', $slug)->whereHas('post', fn (Builder $query): Builder => $query->published())->first();
            abort_unless($redirect, 404);

            return redirect()->route('posts.show', $redirect->post->slug, 301);
        }
        $recorder->record($request, $post);

        return $this->article($request, $post, $sanitizer);
    }

    public function preview(Request $request, Post $post, HtmlSanitizer $sanitizer): View
    {
        return $this->article($request, $post->load(['author', 'cover', 'ogImage', 'categories', 'tags']), $sanitizer, true);
    }

    private function article(Request $request, Post $post, HtmlSanitizer $sanitizer, bool $preview = false): View
    {
        $body = $sanitizer->withHeadings($post->content);
        $userId = $request->user()?->id;
        $likesCount = $preview ? 0 : $post->likers()->count();
        $liked = ! $preview && $userId && $post->likers()->whereKey($userId)->exists();
        $visible = function (Builder $query) use ($userId): void {
            $query->where(function (Builder $query) use ($userId): void {
                $query->where('status', 'approved');
                if ($userId) {
                    $query->orWhere(fn (Builder $query): Builder => $query->where('user_id', $userId)->where('status', 'pending'));
                }
            });
        };
        $comments = $post->comments()->whereNull('parent_id')->where($visible)
            ->with('user')->withCount(['replies' => $visible])
            ->orderByDesc('id')->paginate(10, ['*'], 'comments_page');
        $focusedRoot = null;
        $focusedBatch = null;
        $focusId = old('parent_id') ?: $request->query('comment');
        if (! $preview && is_scalar($focusId) && ctype_digit((string) $focusId)) {
            $focusComment = Comment::where('post_id', $post->id)->find($focusId);
            if ($focusComment) {
                $threads = app(CommentThreads::class);
                $focusedRoot = $threads->root($focusComment, $userId);
                $root = $comments->firstWhere('id', $focusedRoot);
                if ($root && $root->id !== $focusComment->id) {
                    $focusedBatch = $threads->batch($root, $userId, focus: $focusComment->id);
                }
            }
        }
        $related = Post::published()->whereKeyNot($post->id)->whereHas('categories', fn (Builder $query): Builder => $query->whereIn('categories.id', $post->categories->modelKeys()))->with(['author', 'cover', 'categories'])->latest('published_at')->limit(3)->get();

        return view('blog.show', compact('post', 'body', 'comments', 'related', 'preview', 'likesCount', 'liked', 'focusedRoot', 'focusedBatch'));
    }

    public function page(string $page): View
    {
        abort_unless(in_array($page, ['about', 'contact', 'privacy', 'terms'], true), 404);

        return view('blog.page', compact('page'));
    }

    public function author(): RedirectResponse
    {
        return redirect()->route('about');
    }

    public function sitemap(): Response
    {
        $posts = Post::published()->select(['slug', 'updated_at'])->latest()->get();

        return response()->view('blog.sitemap', compact('posts'))->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        return response("User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /tai-khoan\nDisallow: /dang-nhap\nDisallow: /dang-ky\nDisallow: /bai-viet-da-luu\nSitemap: ".route('sitemap')."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
