<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SearchController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:150',
            'type' => 'nullable|in:top,latest,people',
            'period' => 'nullable|in:any,week,month',
            'scope' => 'nullable|in:all,following',
            'category' => 'nullable|string|max:150',
            'tag' => 'nullable|string|max:100',
        ]);
        $filters = array_replace(['q' => '', 'type' => 'top', 'period' => 'any', 'scope' => 'all', 'category' => '', 'tag' => ''], array_filter($filters, fn (mixed $value): bool => $value !== null));
        if ($filters['scope'] === 'following' && ! $request->user()) {
            return redirect()->guest(route('login'));
        }
        $hasQuery = $filters['q'] !== '' || $filters['category'] !== '' || $filters['tag'] !== '' || $filters['type'] !== 'top' || $filters['period'] !== 'any' || $filters['scope'] !== 'all';
        $categories = $this->taxonomies(Category::query());
        $tags = $this->taxonomies(Tag::query());
        $topics = $this->topics($categories, 'category')->concat($this->topics($tags, 'tag'))
            ->sortByDesc('count')->unique(fn (array $topic): string => mb_strtolower($topic['name']))->take(10)->values();
        $threads = null;
        $people = null;
        $recent = collect();
        if ($hasQuery) {
            if ($filters['type'] === 'people') {
                $people = $this->people($request, $filters)->paginate(12)->withQueryString();
            } else {
                $query = $this->publicPosts(Post::query())->withThreadContext($request->user());
                $this->filterPosts($query, $request, $filters);
                if ($filters['type'] === 'top') {
                    $query->orderByDesc('likes_count')->orderByDesc('replies_count');
                }
                $threads = $query->latest('published_at')->latest('id')->paginate(15)->withQueryString();
            }
        } else {
            $recent = $this->publicPosts(Post::query())->with(['author', 'cover', 'media'])->latest('published_at')->latest('id')->take(4)->get();
        }

        return view('threads.search', compact('filters', 'hasQuery', 'topics', 'categories', 'tags', 'threads', 'people', 'recent'));
    }

    private function publicPosts(Builder $query): Builder
    {
        return $query->published()->visibleTo(auth()->user())->whereNull('parent_id')->where('type', '!=', 'repost')
            ->where(fn (Builder $posts): Builder => $posts->whereNull('repost_of_id')->orWhereHas('repostOf', fn (Builder $original): Builder => $original->published()));
    }

    private function taxonomies(Builder $query): Collection
    {
        return $query->whereHas('posts', fn (Builder $posts): Builder => $this->publicPosts($posts))
            ->withCount(['posts' => fn (Builder $posts): Builder => $this->publicPosts($posts)])
            ->with(['posts' => function (BelongsToMany $posts): void {
                $this->publicPosts($posts->getQuery())->with(['cover', 'media'])->latest('published_at')->latest('posts.id');
                $posts->limit(1);
            }])
            ->orderByDesc('posts_count')->orderBy('name')->take(12)->get();
    }

    /** @return Collection<int, array{name: string, kind: string, slug: string, count: int, post: Post|null}> */
    private function topics(Collection $taxonomies, string $kind): Collection
    {
        return $taxonomies->map(fn (Category|Tag $taxonomy): array => ['name' => $taxonomy->name, 'kind' => $kind, 'slug' => $taxonomy->slug, 'count' => $taxonomy->posts_count, 'post' => $taxonomy->posts->first()]);
    }

    /** @param array{q: string, type: string, period: string, scope: string, category: string, tag: string} $filters */
    private function filterPosts(Builder $query, Request $request, array $filters): void
    {
        if ($filters['q'] !== '') {
            $term = $this->searchPattern($filters['q']);
            $query->where(fn (Builder $posts): Builder => $posts->where('title', 'like', $term)->orWhere('excerpt', 'like', $term)->orWhere('content', 'like', $term)
                ->orWhereHas('tags', fn (Builder $tags): Builder => $tags->where('name', 'like', $term))
                ->orWhereHas('author', fn (Builder $authors): Builder => $authors->where('name', 'like', $term)->orWhere('username', 'like', $term)));
        }
        $this->filterTopics($query, $filters);
        if ($filters['period'] !== 'any') {
            $query->where('published_at', '>=', $filters['period'] === 'week' ? now()->subWeek() : now()->subMonth());
        }
        if ($filters['scope'] === 'following') {
            $query->whereIn('user_id', $request->user()->following()->select('users.id'));
        }
    }

    /** @param array{q: string, type: string, period: string, scope: string, category: string, tag: string} $filters */
    private function filterTopics(Builder $query, array $filters): void
    {
        foreach (['category' => 'categories', 'tag' => 'tags'] as $key => $relation) {
            if ($filters[$key] !== '') {
                $query->whereHas($relation, fn (Builder $topics): Builder => $topics->where('slug', $filters[$key]));
            }
        }
    }

    /** @param array{q: string, type: string, period: string, scope: string, category: string, tag: string} $filters */
    private function people(Request $request, array $filters): Builder
    {
        $query = User::discoverableTo($request->user())->where('is_locked', false)->withCount('followers')
            ->withCount(['posts as public_posts_count' => fn (Builder $posts): Builder => $this->publicPosts($posts)])
            ->withExists(['followers as viewer_following' => fn (Builder $followers): Builder => $followers->whereKey($request->user()?->id ?? 0)]);
        if ($filters['q'] !== '') {
            $term = $this->searchPattern($filters['q']);
            $query->where(fn (Builder $users): Builder => $users->where('name', 'like', $term)->orWhere('username', 'like', $term)->orWhere('bio', 'like', $term));
        }
        if ($filters['category'] !== '' || $filters['tag'] !== '' || $filters['period'] !== 'any') {
            $query->whereHas('posts', function (Builder $posts) use ($filters): void {
                $this->publicPosts($posts);
                $this->filterTopics($posts, $filters);
                if ($filters['period'] !== 'any') {
                    $posts->where('published_at', '>=', $filters['period'] === 'week' ? now()->subWeek() : now()->subMonth());
                }
            });
        }
        if ($filters['scope'] === 'following') {
            $query->whereIn('id', $request->user()->following()->select('users.id'));
        }

        return $query->orderByDesc('followers_count')->orderBy('name')->orderBy('id');
    }

    private function searchPattern(string $query): string
    {
        $needle = preg_replace('/^[#@](?=\S)/u', '', $query);

        return '%'.addcslashes($needle, '\\%_').'%';
    }
}
