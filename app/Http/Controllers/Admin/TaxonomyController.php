<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TaxonomyController extends Controller
{
    private function query(string $kind): Builder
    {
        abort_unless(in_array($kind, ['categories', 'tags'], true), 404);

        return $kind === 'categories' ? Category::query() : Tag::query();
    }

    public function index(Request $request, string $kind): View
    {
        $query = $this->query($kind);
        $filters = $request->validate(['q' => 'nullable|string|max:150', 'edit' => 'nullable|integer']);
        $editing = ! empty($filters['edit']) ? $this->query($kind)->findOrFail($filters['edit']) : null;
        $items = $query->when($filters['q'] ?? null, fn (Builder $query, string $search): Builder => $query->where('name', 'like', '%'.$search.'%'))->withCount('posts')->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.taxonomies', compact('kind', 'items', 'editing'));
    }

    public function store(Request $request, string $kind): RedirectResponse
    {
        $data = $this->validated($request, $kind);
        $this->query($kind)->create($data);

        return redirect()->route('admin.taxonomies.index', $kind)->with('success', 'Đã tạo mục mới.');
    }

    public function update(Request $request, string $kind, int $id): RedirectResponse
    {
        $item = $this->query($kind)->findOrFail($id);
        $item->update($this->validated($request, $kind, $id));

        return redirect()->route('admin.taxonomies.index', $kind)->with('success', 'Đã lưu thay đổi.');
    }

    private function validated(Request $request, string $kind, ?int $id = null): array
    {
        $this->query($kind);
        $data = $request->validate(['name' => ['required', 'string', 'max:'.($kind === 'categories' ? 100 : 80), Rule::unique($kind)->ignore($id)], 'slug' => ['nullable', 'string', 'max:'.($kind === 'categories' ? 150 : 100), 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique($kind)->ignore($id)], 'description' => 'nullable|string|max:2000']);
        $data['slug'] = ($data['slug'] ?? '') ?: Str::slug($data['name']);
        if (! $data['slug'] || $this->query($kind)->where('slug', $data['slug'])->when($id, fn (Builder $query): Builder => $query->whereKeyNot($id))->exists()) {
            throw ValidationException::withMessages(['slug' => 'Đường dẫn không hợp lệ hoặc đã được dùng.']);
        }
        if ($kind === 'tags') {
            unset($data['description']);
        }

        return $data;
    }

    public function destroy(string $kind, int $id): RedirectResponse
    {
        DB::transaction(function () use ($kind, $id): void {
            $item = $this->query($kind)->lockForUpdate()->findOrFail($id);
            if ($kind === 'categories' && $item->posts()->withTrashed()->exists()) {
                throw ValidationException::withMessages(['category' => 'Danh mục còn bài viết, kể cả trong thùng rác. Chuyển bài sang danh mục khác trước khi xóa.']);
            }
            if ($kind === 'tags') {
                $item->posts()->detach();
            }
            $item->delete();
        });

        return back()->with('success', 'Đã xóa và xử lý quan hệ liên quan.');
    }
}
