@extends('layouts.blog')
@section('title', $article->exists ? 'Chỉnh sửa bài viết' : 'Soạn bài viết')
@section('robots', 'noindex,nofollow')
@section('content')
<a class="back-link" href="{{ route('admin.posts.index') }}"><x-icon name="back" size="17" /> Quản lý bài viết</a>
<header class="admin-heading"><div><span class="overline">MỘT TRANG TRẮNG, MỘT Ý TƯỞNG MỚI</span><h1>{{ $article->exists ? 'Chỉnh sửa bài viết' : 'Viết một điều đáng nhớ' }}<span class="heading-dot">.</span></h1></div></header><x-admin-nav />
<form class="editor-form form-stack" data-editor method="post" enctype="multipart/form-data" action="{{ $article->exists ? route('admin.posts.update', $article) : route('admin.posts.store') }}">@csrf @if($article->exists) @method('PATCH') @endif
<div class="form-grid">
<label>Chuyên mục<select name="category_id" required><option value="">Chọn chuyên mục</option>@foreach($editorCategories as $item)<option value="{{ $item->id }}" @selected((string)old('category_id', $article->category_id) === (string)$item->id)>{{ $item->name }}</option>@endforeach</select></label>
<label>Trạng thái<select name="status">@foreach(['draft'=>'Bản nháp — chưa công khai','published'=>'Xuất bản — công khai','archived'=>'Lưu trữ — ẩn khỏi blog'] as $key=>$label)<option value="{{ $key }}" @selected(old('status', $article->status) === $key)>{{ $label }}</option>@endforeach</select></label>
</div>
@if($editorCategories->isEmpty())<p class="flash">Cần <a href="{{ route('admin.categories.index') }}">thêm chuyên mục đang hoạt động</a> trước khi lưu bài.</p>@endif
<label for="draft-title">Tiêu đề</label><textarea id="draft-title" class="editor-title" name="title" rows="2" maxlength="200" required placeholder="Tiêu đề bài viết của bạn…">{{ old('title', $article->title) }}</textarea>
<label for="slug">Đường dẫn (slug)</label><input id="slug" name="slug" value="{{ old('slug', $article->slug) }}" maxlength="240" placeholder="Để trống để tự tạo từ tiêu đề"><span class="field-hint">Chữ thường không dấu, số và dấu gạch nối. Slug phải duy nhất.</span>
<label for="draft-excerpt">Lời mở đầu</label><textarea id="draft-excerpt" name="excerpt" rows="3" maxlength="500">{{ old('excerpt', $article->excerpt) }}</textarea>
<label for="draft-body">Nội dung bài viết</label><span class="field-hint">Văn bản thuần · Dòng trống tách đoạn · Viết “## Tiêu đề mục” để tạo mục lục. HTML không được thực thi.</span><textarea id="draft-body" class="editor-body" name="content" rows="16" minlength="10" maxlength="100000" required placeholder="Bắt đầu từ một ý tưởng nhỏ…">{{ old('content', $article->content) }}</textarea>
<fieldset><legend>Thẻ bài viết</legend><div class="tag-options">@forelse($editorTags as $tag)<label class="check-label"><input type="checkbox" name="tags[]" value="{{ $tag->id }}" @checked(in_array($tag->id, old('tags', $article->tags->pluck('id')->all())))>{{ $tag->name }}</label>@empty<p class="muted">Chưa có thẻ. Bạn có thể thêm ở mục Thẻ.</p>@endforelse</div></fieldset>
<label class="check-label"><input name="is_featured" type="checkbox" value="1" @checked(old('is_featured', $article->is_featured))> Ưu tiên làm bài nổi bật ở trang chủ</label>
<fieldset><legend>Ảnh bìa</legend><label for="cover">Tải ảnh JPG, PNG hoặc WebP (tối đa 5 MB)</label><input id="cover" type="file" name="cover" accept="image/jpeg,image/png,image/webp" data-cover-input>
@if($article->cover_path)<img class="editor-cover" src="{{ str_starts_with($article->cover_path, 'images/') ? asset($article->cover_path) : route('media', ['path'=>$article->cover_path]) }}" alt="Ảnh bìa hiện tại"><label class="check-label"><input name="remove_cover" type="checkbox" value="1"> Xóa ảnh bìa hiện tại</label>@endif
<img class="editor-cover" data-cover-preview alt="Xem trước ảnh mới" hidden><label for="cover_alt">Mô tả ảnh</label><input id="cover_alt" name="cover_alt" value="{{ old('cover_alt', $article->cover_alt) }}" maxlength="200">
</fieldset>
<div class="editor-footer"><span data-word-count>0 từ</span><div><button class="button" type="button" data-preview>Xem trước nội dung</button><button class="button button--black" type="submit"><x-icon name="check" size="17" /> Lưu bài viết</button></div></div>
@if($article->exists)<a class="text-link" href="{{ route('admin.posts.preview', $article) }}">Xem phiên bản đã lưu</a>@endif
</form>
<dialog class="preview-dialog" data-preview-dialog aria-labelledby="preview-label"><div class="preview-top"><span class="overline" id="preview-label">XEM TRƯỚC · NỘI DUNG CHƯA LƯU</span><button class="button" type="button" data-close-preview>Đóng</button></div><article><span class="pill" data-preview-category></span><h1 data-preview-title></h1><p class="article-deck" data-preview-excerpt></p><div class="prose" data-preview-body></div></article></dialog>
<noscript><p class="muted">Bạn vẫn có thể lưu bài khi tắt JavaScript; chỉ chức năng xem trước nội dung cần JavaScript.</p></noscript>
@endsection
