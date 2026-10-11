<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TaxonomyController extends Controller
{
    public function categories() { return view('admin.categories', ['items' => Category::withCount('posts')->orderBy('name')->get()]); }
    public function tags() { return view('admin.tags', ['items' => Tag::withCount('posts')->orderBy('name')->get()]); }
    private function categoryData(Request $request, ?Category $category = null): array
    {
        if (!$request->filled('slug') && is_string($request->input('name'))) $request->merge(['slug' => Str::slug($request->input('name'))]);
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories')->ignore($category?->id)],
            'slug' => ['required', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('categories')->ignore($category?->id)],
            'description' => 'nullable|string|max:500', 'icon' => 'required|in:book,code,layers,coffee', 'is_active' => 'required|boolean',
        ]);
    }
    private function tagData(Request $request, ?Tag $tag = null): array
    {
        if (!$request->filled('slug') && is_string($request->input('name'))) $request->merge(['slug' => Str::slug($request->input('name'))]);
        return $request->validate(['name' => ['required', 'string', 'max:50', Rule::unique('tags')->ignore($tag?->id)], 'slug' => ['required', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('tags')->ignore($tag?->id)]]);
    }
    public function storeCategory(Request $request) { Category::create($this->categoryData($request)); return back()->with('success', 'Đã thêm chuyên mục.'); }
    public function updateCategory(Request $request, Category $category) { $category->update($this->categoryData($request, $category)); return back()->with('success', 'Đã cập nhật chuyên mục.'); }
    public function deleteCategory(Category $category)
    {
        if ($category->posts()->withTrashed()->exists()) return back()->withErrors(['category' => 'Chuyên mục còn bài viết, kể cả trong thùng rác. Chuyển bài sang chuyên mục khác hoặc xóa vĩnh viễn bài trước khi xóa.']);
        $category->delete();
        return back()->with('success', 'Đã xóa chuyên mục.');
    }
    public function storeTag(Request $request) { Tag::create($this->tagData($request)); return back()->with('success', 'Đã thêm thẻ.'); }
    public function updateTag(Request $request, Tag $tag) { $tag->update($this->tagData($request, $tag)); return back()->with('success', 'Đã cập nhật thẻ.'); }
    public function deleteTag(Tag $tag) { $tag->delete(); return back()->with('success', 'Đã xóa thẻ khỏi danh sách và các bài viết.'); }
}
