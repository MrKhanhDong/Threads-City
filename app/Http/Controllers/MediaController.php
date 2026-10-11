<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function show(string $path)
    {
        // Only generated filenames in the two upload directories can be requested.
        abort_unless(preg_match('~^(covers|avatars)/[A-Za-z0-9_-]+\.(jpg|jpeg|png|webp)$~', $path), 404);
        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);
        if (str_starts_with($path, 'covers/')) {
            abort_unless(auth()->user()?->isAdmin() || \App\Models\Post::published()->where('cover_path', $path)->exists(), 404);
        }
        // Do not let a cached public cover bypass a later draft/archive transition.
        return $disk->response($path, null, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
