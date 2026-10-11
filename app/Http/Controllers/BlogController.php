<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Repositories\BlogRepository;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function __construct(private readonly BlogRepository $blog) {}

    public function home()
    {
        $query = Post::published()->with(['category', 'tags', 'user'])->latest('published_at');
        $featured = (clone $query)->where('is_featured', true)->first() ?? (clone $query)->first();
        $saved = $this->blog->savedIds();
        return view('blog.home', [
            'featured' => $featured?->toCard($saved),
            'posts' => $query->when($featured, fn ($q) => $q->whereKeyNot($featured->id))->take(3)->get()->map(fn (Post $post) => $post->toCard($saved)),
        ]);
    }

    public function archive(Request $request)
    {
        $filters = $request->validate(['q' => 'nullable|string|max:200', 'category' => 'nullable|string|max:120', 'tag' => 'nullable|string|max:80']);
        $query = trim($filters['q'] ?? '');
        $category = $filters['category'] ?? '';
        $tag = $filters['tag'] ?? '';
        $posts = Post::published()->with(['category', 'tags', 'user'])->latest('published_at');
        if ($query !== '') $posts->where(fn ($q) => $q->where('title', 'like', '%'.$query.'%')->orWhere('excerpt', 'like', '%'.$query.'%')->orWhere('content', 'like', '%'.$query.'%')->orWhereHas('tags', fn ($tags) => $tags->where('name', 'like', '%'.$query.'%')));
        if ($category !== '') $posts->whereHas('category', fn ($q) => $q->where('slug', $category));
        if ($tag !== '') $posts->whereHas('tags', fn ($q) => $q->where('name', $tag));
        $saved = $this->blog->savedIds();
        return view('blog.archive', [
            'posts' => $posts->paginate(6)->withQueryString()->through(fn (Post $post) => $post->toCard($saved)),
            'query' => $query, 'category' => $category, 'tag' => $tag,
        ]);
    }

    public function show(Request $request, string $slug, \App\Services\PostViewRecorder $recorder)
    {
        $article = Post::published()->with(['category', 'tags', 'user'])->where('slug', $slug)->firstOrFail();
        $recorder->record($request, $article);
        return $this->articleView($article, false);
    }

    public function preview(Post $post) { return $this->articleView($post, true); }

    private function articleView(Post $article, bool $preview)
    {
        $article->loadMissing(['category', 'tags', 'user']);
        $saved = $this->blog->savedIds();
        $comments = $article->comments()->with('user')->where(function ($q) {
            $q->where('status', 'approved');
            if (auth()->check()) $q->orWhere('user_id', auth()->id());
        })->latest()->paginate(10);
        return view('blog.post', [
            'post' => $article->toCard($saved), 'preview' => $preview, 'comments' => $comments,
            'related' => Post::published()->with(['category', 'tags', 'user'])->whereKeyNot($article->id)->latest('published_at')->take(2)->get()->map(fn (Post $post) => $post->toCard($saved)),
        ]);
    }

    public function categories() { return view('blog.categories'); }
    public function about() { return view('blog.about'); }
    public function saved()
    {
        $savedIds = $this->blog->savedIds();
        $posts = auth()->user()->bookmarks()->published()->with(['category', 'tags', 'user'])->orderByPivot('created_at', 'desc')->paginate(6)
            ->through(fn (Post $post) => $post->toCard($savedIds));
        return view('blog.saved', ['posts' => $posts]);
    }
}
