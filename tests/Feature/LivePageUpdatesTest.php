<?php

namespace Tests\Feature;

use App\Http\Middleware\LivePageUpdates;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Tests\TestCase;

class LivePageUpdatesTest extends TestCase
{
    private function request(string $name = 'home', bool $live = true): Request
    {
        $request = Request::create('/');
        $request->setRouteResolver(fn () => (new Route('GET', '/', fn () => ''))->name($name));
        if ($live) {
            $request->headers->set('X-Threads-Live', '1');
        }

        return $request;
    }

    public function test_only_marked_regions_are_returned_without_scripts_or_composer(): void
    {
        $html = '<html><body><form><textarea>Draft text</textarea></form><div data-live-region="feed"><article data-live-key="thread-1">Tiếng Việt</article><script>alert(1)</script></div></body></html>';
        $response = app(LivePageUpdates::class)->handle($this->request(), fn () => response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']));
        $data = json_decode($response->getContent(), true);
        $this->assertSame('feed', $data['regions'][0]['name']);
        $this->assertStringContainsString('Tiếng Việt', $data['regions'][0]['html']);
        $this->assertStringNotContainsString('<script', $response->getContent());
        $this->assertStringNotContainsString('Draft text', $response->getContent());
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
    }

    public function test_normal_page_requests_keep_original_response(): void
    {
        $response = response('<div>Normal page</div>');
        $this->assertSame($response, app(LivePageUpdates::class)->handle($this->request(live: false), fn () => $response));
    }

    public function test_login_redirects_are_not_converted_into_live_updates(): void
    {
        $response = redirect('/dang-nhap');
        $this->assertSame($response, app(LivePageUpdates::class)->handle($this->request('saved'), fn () => $response));
    }

    public function test_admin_pages_cannot_be_polled_with_this_protocol(): void
    {
        $response = response('<div data-live-region="admin">Private admin content</div>');
        $this->assertSame($response, app(LivePageUpdates::class)->handle($this->request('admin.index'), fn () => $response));
    }

    public function test_invalid_known_ids_do_not_trigger_database_queries(): void
    {
        $request = $this->request();
        $request->headers->set('X-Threads-Known', 'invalid,-1,3 OR 1=1,123456789012345678901');
        $response = app(LivePageUpdates::class)->handle($request, fn () => response('<div data-live-region="feed"></div>', 200, ['Content-Type' => 'text/html']));
        $this->assertSame([], json_decode($response->getContent(), true)['removed']);
    }

    public function test_repeated_card_styles_are_sent_once(): void
    {
        $response = app(LivePageUpdates::class)->handle($this->request(), fn () => response('<div data-live-region="feed"><style>.card{color:white}</style><article>One</article><style>.card{color:white}</style></div>', 200, ['Content-Type' => 'text/html']));
        $html = json_decode($response->getContent(), true)['regions'][0]['html'];
        $this->assertSame(1, substr_count($html, '<style>'));
    }

    public function test_live_protocol_works_through_the_registered_web_middleware(): void
    {
        config(['app.key' => 'base64:'.base64_encode(str_repeat('t', 32))]);
        Router::middleware('web')->get('/_test-live-page', fn () => response('<div data-live-region="feed">Updated posts</div>'))->name('threads.index');
        $this->withHeaders(['Accept' => 'text/html', 'X-Threads-Live' => '1'])
            ->get('/_test-live-page')
            ->assertOk()
            ->assertJsonPath('regions.0.name', 'feed')
            ->assertJsonPath('removed', []);
    }
}
