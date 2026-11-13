<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_and_multitasking_point_to_the_full_search_page_and_old_links_redirect(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('href="'.route('search.index').'" aria-label="Tìm kiếm"', false)
            ->assertSee('data-column-choice="search" data-column-url="'.route('search.index').'"', false);
        $this->get(route('home', ['search' => 1, 'q' => 'Laravel', 'type' => 'people']))
            ->assertRedirect(route('search.index', ['q' => 'Laravel', 'type' => 'people']));
        $this->get(route('search.index'))->assertOk()->assertSee('journal-shell', false)->assertDontSee('journal-restored-search', false);
    }

    public function test_restored_search_respects_hidden_archived_and_blocked_content(): void
    {
        $reader = User::factory()->create();
        $blocked = User::factory()->create(['name' => 'Needle blocked']);
        $blocking = User::factory()->create(['name' => 'Needle blocking']);
        $reader->blockedUsers()->attach($blocked);
        $blocking->blockedUsers()->attach($reader);
        $visible = Post::factory()->published()->create(['title' => 'Needle public']);
        $hidden = Post::factory()->published()->create(['title' => 'Needle hidden']);
        DB::table('hidden_posts')->insert(['user_id' => $reader->id, 'post_id' => $hidden->id, 'created_at' => now()]);
        Post::factory()->published()->create(['title' => 'Needle archived', 'archived_at' => now()]);
        $blockedPost = Post::factory()->published()->create(['user_id' => $blocked->id, 'title' => 'Needle blocked']);
        Post::factory()->published()->create(['user_id' => $blocking->id, 'title' => 'Needle blocking']);
        Post::factory()->published()->create(['type' => 'quote', 'repost_of_id' => $blockedPost->id, 'title' => 'Needle blocked quote']);
        $this->actingAs($reader)->get(route('search.index', ['q' => 'Needle']))->assertOk()
            ->assertViewHas('threads', fn ($threads): bool => $threads->pluck('id')->all() === [$visible->id]);
        $this->get(route('search.index', ['q' => 'Needle', 'type' => 'people']))->assertOk()
            ->assertViewHas('people', fn ($people): bool => $people->isEmpty());
    }

    public function test_discovery_uses_real_topics_counts_and_a_preview_for_each_topic(): void
    {
        $first = Category::factory()->create(['name' => 'Đời sống', 'slug' => 'doi-song']);
        $second = Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);
        $post = Post::factory()->published()->create(['excerpt' => 'Một thói quen nhỏ']);
        $post->categories()->attach($first);
        $other = Post::factory()->published()->create(['excerpt' => 'Một dự án Laravel']);
        $other->tags()->attach($second);
        $hidden = Category::factory()->create(['name' => 'Chủ đề riêng tư']);
        $draft = Post::factory()->create();
        $draft->categories()->attach($hidden);
        $response = $this->get(route('search.index'))->assertOk()->assertSee('Được quan tâm')->assertSee('Đời sống')->assertSee('Laravel')->assertSee('Một thói quen nhỏ')->assertSee('Một dự án Laravel')->assertDontSee('Chủ đề riêng tư')->assertDontSee('AI tóm tắt');
        $response->assertViewHas('topics', fn ($topics): bool => $topics->count() === 2 && $topics->every(fn (array $topic): bool => $topic['count'] === 1 && $topic['post'] !== null));
    }

    public function test_search_matches_title_body_and_tags_but_excludes_private_content(): void
    {
        $post = Post::factory()->published()->create(['title' => 'Một góc học tập', 'content' => '<p>Học Laravel từ đồ án nhỏ.</p>']);
        $tag = Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);
        $post->tags()->attach($tag);
        $draft = Post::factory()->create(['title' => 'Laravel trong bản nháp']);
        $future = Post::factory()->scheduled()->create(['title' => 'Laravel trong tương lai']);
        foreach (['góc học tập', 'Laravel', 'PHP', '#PHP'] as $query) {
            $this->get(route('search.index', ['q' => $query]))->assertOk()->assertViewHas('threads', fn ($threads): bool => $threads->total() === 1 && $threads->first()->id === $post->id)->assertDontSee($draft->title)->assertDontSee($future->title);
        }
    }

    public function test_replies_reposts_and_quotes_with_hidden_originals_are_not_discovery_results(): void
    {
        $root = Post::factory()->published()->create(['title' => 'Original']);
        Post::factory()->published()->create(['parent_id' => $root->id, 'type' => 'reply', 'title' => 'Needle reply']);
        Post::factory()->published()->create(['type' => 'repost', 'repost_of_id' => $root->id, 'title' => 'Needle repost']);
        $hidden = Post::factory()->create();
        Post::factory()->published()->create(['type' => 'quote', 'repost_of_id' => $hidden->id, 'title' => 'Needle private quote']);
        $this->get(route('search.index', ['q' => 'Needle']))->assertOk()->assertViewHas('threads', fn ($threads): bool => $threads->total() === 0);
    }

    public function test_topic_chips_filter_using_real_taxonomies(): void
    {
        $category = Category::factory()->create(['name' => 'Học tập', 'slug' => 'hoc-tap']);
        $tag = Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);
        $matched = Post::factory()->published()->create();
        $matched->categories()->attach($category);
        $matched->tags()->attach($tag);
        Post::factory()->published()->create();
        foreach ([['category' => $category->slug], ['tag' => $tag->slug], ['category' => $category->slug, 'tag' => $tag->slug]] as $filters) {
            $this->get(route('search.index', $filters))->assertOk()->assertViewHas('threads', fn ($threads): bool => $threads->total() === 1 && $threads->first()->id === $matched->id);
        }
    }

    public function test_popular_and_latest_tabs_use_actual_engagement_and_publication_time(): void
    {
        $popular = Post::factory()->published()->create(['title' => 'Needle older', 'published_at' => now()->subDays(3)]);
        $popular->likers()->attach(User::factory()->create(), ['created_at' => now()]);
        $latest = Post::factory()->published()->create(['title' => 'Needle latest', 'published_at' => now()->subHour()]);
        $this->get(route('search.index', ['q' => 'Needle', 'type' => 'top']))->assertOk()->assertViewHas('threads', fn ($threads): bool => $threads->first()->id === $popular->id);
        $this->get(route('search.index', ['q' => 'Needle', 'type' => 'latest']))->assertOk()->assertViewHas('threads', fn ($threads): bool => $threads->first()->id === $latest->id);
    }

    public function test_people_search_uses_public_name_username_and_bio_not_email(): void
    {
        $person = User::factory()->create(['name' => 'Phương Mộc', 'username' => 'phuong_moc', 'bio' => 'Viết về công nghệ và Laravel', 'email' => 'private-needle@example.com']);
        $locked = User::factory()->create(['name' => 'Laravel Locked', 'is_locked' => true]);
        foreach (['Phương', 'phuong_moc', '@phuong_moc', 'công nghệ'] as $query) {
            $this->get(route('search.index', ['q' => $query, 'type' => 'people']))->assertOk()->assertSee('Theo dõi')->assertDontSee($person->email)->assertDontSee($locked->name)->assertViewHas('people', fn ($people): bool => $people->total() === 1 && $people->first()->id === $person->id);
        }
        $this->get(route('search.index', ['q' => 'private-needle', 'type' => 'people']))->assertOk()->assertViewHas('people', fn ($people): bool => $people->isEmpty());
    }

    public function test_following_filter_requires_login_and_only_includes_followed_authors(): void
    {
        $this->get(route('search.index', ['scope' => 'following']))->assertRedirect(route('login'));
        $reader = User::factory()->create();
        $author = User::factory()->create();
        $reader->following()->attach($author->id, ['created_at' => now()]);
        $post = Post::factory()->published()->create(['user_id' => $author->id]);
        Post::factory()->published()->create();
        $this->actingAs($reader)->get(route('search.index', ['scope' => 'following']))->assertOk()->assertViewHas('threads', fn ($threads): bool => $threads->total() === 1 && $threads->first()->id === $post->id);
        $this->get(route('search.index', ['scope' => 'following', 'type' => 'people']))->assertOk()->assertViewHas('people', fn ($people): bool => $people->total() === 1 && $people->first()->id === $author->id && $people->first()->viewer_following);
    }

    public function test_time_filters_apply_to_results(): void
    {
        $recent = Post::factory()->published()->create(['published_at' => now()->subDays(2)]);
        Post::factory()->published()->create(['published_at' => now()->subDays(18)]);
        Post::factory()->published()->create(['published_at' => now()->subDays(45)]);
        $this->get(route('search.index', ['period' => 'week']))->assertOk()->assertViewHas('threads', fn ($threads): bool => $threads->total() === 1 && $threads->first()->id === $recent->id);
        $this->get(route('search.index', ['period' => 'month']))->assertOk()->assertViewHas('threads', fn ($threads): bool => $threads->total() === 2);
    }

    public function test_wildcards_are_literal_and_zero_is_a_real_search_term(): void
    {
        Post::factory()->published()->create(['title' => 'Bước 0', 'content' => 'A normal post', 'excerpt' => 'A normal excerpt']);
        $this->get(route('search.index', ['q' => '%']))->assertOk()->assertViewHas('threads', fn ($threads): bool => $threads->isEmpty());
        $this->get(route('search.index', ['q' => '0']))->assertOk()->assertViewHas('threads', fn ($threads): bool => $threads->total() === 1);
    }

    public function test_malformed_filters_are_rejected_and_empty_states_are_useful(): void
    {
        foreach ([['q' => ['bad']], ['q' => str_repeat('a', 151)], ['type' => 'invalid'], ['period' => 'invalid'], ['scope' => 'invalid']] as $filters) {
            $this->getJson(route('search.index', $filters))->assertUnprocessable();
        }
        $this->get(route('search.index'))->assertOk()->assertSee('Chưa có chủ đề để khám phá');
        $this->get(route('search.index', ['q' => '<script>alert(1)</script>']))->assertOk()->assertSee('Chưa tìm thấy bài viết')->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_pagination_preserves_search_and_filter_state(): void
    {
        Post::factory()->published()->count(16)->create(['title' => 'Needle', 'published_at' => now()->subDay()]);
        $this->get(route('search.index', ['q' => 'Needle', 'type' => 'latest', 'period' => 'week']))->assertOk()->assertViewHas('threads', fn ($threads): bool => $threads->count() === 15 && str_contains($threads->nextPageUrl(), 'q=Needle') && str_contains($threads->nextPageUrl(), 'period=week'));
    }
}
