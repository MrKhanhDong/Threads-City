<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostRedirect;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class BlogController extends Controller
{
    public function show(string $slug): RedirectResponse
    {
        $post = Post::published()->visibleTo(auth()->user())->where('slug', $slug)->first();
        if (! $post) {
            $post = PostRedirect::where('slug', $slug)->first()?->post;
            abort_unless($post && $post->status === 'published' && $post->published_at <= now(), 404);
        }

        return redirect()->route('threads.show', $post->id, 301);
    }

    public function preview(Post $post): View
    {
        $thread = $post->load(['author', 'media', 'repostOf.author', 'repostOf.media', 'tags', 'parent.author']);
        $replies = Post::published()->where('parent_id', $post->id)
            ->with(['author', 'media', 'repostOf.author', 'repostOf.media', 'tags'])
            ->oldest('published_at')->get();

        return view('threads.show', compact('thread', 'replies'));
    }

    public function sitemap(): Response
    {
        $posts = Post::published()->visibleTo(null)->select(['id', 'updated_at'])->latest()->get();

        return response()->view('blog.sitemap', compact('posts'))->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        return response("User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /tai-khoan\nDisallow: /dang-nhap\nDisallow: /dang-ky\nDisallow: /bai-viet-da-luu\nSitemap: ".route('sitemap')."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
