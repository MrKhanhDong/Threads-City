<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MediaRequest;
use App\Models\Media;
use App\Models\Post;
use App\Services\ImageStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['q' => 'nullable|string|max:150']);
        $media = Media::when($data['q'] ?? null, fn (Builder $query, string $search): Builder => $query->where('original_name', 'like', '%'.$search.'%'))->latest()->paginate(18)->withQueryString();

        return view('admin.media', compact('media'));
    }

    public function store(MediaRequest $request): JsonResponse|RedirectResponse
    {
        $file = $request->file('file');
        $dimensions = getimagesize($file->getRealPath());
        $path = app(ImageStorage::class)->store($file, 'media');
        try {
            $media = Media::create(['user_id' => $request->user()->id, 'path' => $path, 'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize(), 'width' => $dimensions[0], 'height' => $dimensions[1], 'alt' => $request->validated('alt')]);
        } catch (\Throwable $error) {
            app(ImageStorage::class)->delete($path);
            throw $error;
        }
        if ($request->expectsJson()) {
            return response()->json(['location' => $media->url(), 'id' => $media->id, 'alt' => $media->alt], 201);
        }

        return back()->with('success', 'Đã tải ảnh lên thư viện.');
    }

    public function show(Request $request, Media $media): BinaryFileResponse|RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            $isAuthor = $request->user() && $media->user_id === $request->user()->id;
            abort_unless($isAuthor || $this->references($media)->published()->exists(), 404);
        }
        if ($url = app(ImageStorage::class)->remoteUrl($media->path)) {
            return redirect()->away($url)->header('Cache-Control', 'private, no-store');
        }

        abort_unless(preg_match('~^media/[A-Za-z0-9_\-]+\.(jpg|jpeg|png|webp|gif)$~i', $media->path) && Storage::disk('local')->exists($media->path), 404);

        return response()->file(Storage::disk('local')->path($media->path), ['Content-Type' => $media->mime_type, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store', 'Vary' => 'Cookie']);
    }

    public function update(Request $request, Media $media): RedirectResponse
    {
        $media->update($request->validate(['alt' => 'nullable|string|max:255']));

        return back()->with('success', 'Đã cập nhật ALT. Ảnh trong bài chưa có ALT riêng sẽ dùng mô tả từ thư viện.');
    }

    public function destroy(Media $media): RedirectResponse
    {
        $path = DB::transaction(function () use ($media): string {
            $locked = Media::lockForUpdate()->findOrFail($media->id);
            if ($this->references($locked)->withTrashed()->exists()) {
                throw ValidationException::withMessages(['media' => 'Ảnh đang được dùng trong bài viết hoặc thùng rác. Gỡ tham chiếu trước khi xóa.']);
            }
            $path = $locked->path;
            $locked->delete();

            return $path;
        });
        if (! app(ImageStorage::class)->delete($path)) {
            return back()->withErrors(['media' => 'Đã xóa metadata nhưng chưa xóa được file. Kiểm tra quyền thư mục storage.']);
        }

        return back()->with('success', 'Đã xóa ảnh không còn được sử dụng.');
    }

    private function references(Media $media): Builder
    {
        return Post::where(function (Builder $query) use ($media): void {
            $query->where('cover_media_id', $media->id)->orWhere('og_media_id', $media->id)->orWhereHas('media', fn (Builder $query): Builder => $query->where('media.id', $media->id));
        });
    }
}
