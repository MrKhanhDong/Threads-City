<?php

namespace App\Http\Middleware;

use App\Models\Post;
use Closure;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LivePageUpdates
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($request->header('X-Threads-Live') !== '1' || ! $request->isMethod('GET')
            || ! $request->routeIs('home', 'threads.index', 'threads.show', 'profile.show', 'saved', 'notifications.index')
            || $response->getStatusCode() !== 200 || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return $response;
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $regions = [];
        $styles = [];
        foreach ((new DOMXPath($document))->query('//*[@data-live-region]') as $element) {
            foreach (iterator_to_array($element->getElementsByTagName('script')) as $script) {
                $script->parentNode->removeChild($script);
            }
            foreach (iterator_to_array($element->getElementsByTagName('style')) as $style) {
                $key = sha1($style->textContent);
                if (isset($styles[$key])) {
                    $style->parentNode->removeChild($style);
                } else {
                    $styles[$key] = true;
                }
            }
            $regions[] = [
                'name' => $element->getAttribute('data-live-region'),
                'html' => $document->saveHTML($element),
            ];
        }

        $knownIds = array_values(array_unique(array_filter(explode(',', (string) $request->header('X-Threads-Known', '')), fn (string $id): bool => ctype_digit($id) && strlen($id) <= 18)));
        $knownIds = array_slice($knownIds, 0, 100);
        $removed = [];
        if ($knownIds !== []) {
            $visible = Post::published()->visibleTo($request->user(), true)->whereIn('id', $knownIds);
            if ($request->user() && $request->routeIs('home', 'threads.index')) {
                $visible->whereNotIn('id', DB::table('hidden_posts')->where('user_id', $request->user()->id)->select('post_id'));
                if ($request->query('tab') === 'following') {
                    $visible->fromFollowedAuthors($request->user());
                }
            }
            $existing = $visible->pluck('id')->map(fn ($id): string => (string) $id)->all();
            $removed = array_values(array_diff($knownIds, $existing));
        }

        return response()->json(['regions' => $regions, 'removed' => $removed])
            ->header('Cache-Control', 'private, no-store')->header('Vary', 'Cookie, X-Threads-Live');
    }
}
