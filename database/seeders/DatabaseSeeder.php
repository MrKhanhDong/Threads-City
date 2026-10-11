<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Repositories\DemoBlogRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) throw new \RuntimeException('Không seed tài khoản demo trong production.');
        $samples = new DemoBlogRepository;
        $owner = User::firstOrCreate(['email' => 'admin@blog.local'], [
            'name' => 'Duy Khang', 'password' => 'Admin@12345', 'role' => 'admin',
            'headline' => 'Lập trình viên & người viết', 'bio' => $samples->author()['bio'], 'blog_title' => "Khang's Journal",
            'about' => "Mình tạo blog này như một cuốn sổ mở. Có những bài là ghi chép sau khi giải quyết một vấn đề trong code. Có những bài chỉ là trải nghiệm nhỏ khiến mình muốn ngồi xuống và viết.\n\nMình thích sự đơn giản: một trang trắng, vài dòng chữ rõ ràng và đủ khoảng trống để suy nghĩ. Blog này lưu lại hành trình học lập trình, tìm hiểu mã nguồn mở và cuộc sống thường ngày.",
        ]);
        $reader = User::firstOrCreate(['email' => 'reader@blog.local'], ['name' => 'Người đọc demo', 'password' => 'Reader@12345', 'role' => 'reader']);
        foreach ($samples->categories() as $category) {
            Category::firstOrCreate(['slug' => $category['slug']], collect($category)->only(['name', 'description', 'icon'])->all());
        }
        foreach ($samples->posts() as $index => $sample) {
            $content = collect($sample['sections'])->map(fn ($section) => '## '.$section['heading']."\n\n".implode("\n\n", $section['paragraphs']))->implode("\n\n");
            $post = Post::withTrashed()->firstOrCreate(['slug' => $sample['slug']], [
                'user_id' => $owner->id, 'category_id' => Category::where('slug', $sample['category_slug'])->firstOrFail()->id,
                'title' => $sample['title'], 'excerpt' => $sample['excerpt'], 'content' => $content,
                'cover_path' => $sample['image'], 'cover_alt' => $sample['image_alt'],
                'status' => 'published', 'published_at' => now()->subDays($index + 1), 'is_featured' => $index === 0,
            ]);
            $ids = collect($sample['tags'])->map(fn ($name) => Tag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id);
            $post->tags()->syncWithoutDetaching($ids);
        }
        Post::withTrashed()->firstOrCreate(['slug' => 'mot-ban-nhap-chua-xuat-ban'], [
            'user_id' => $owner->id, 'category_id' => Category::first()->id, 'title' => 'Một bản nháp chưa xuất bản',
            'excerpt' => 'Bài này chỉ chủ blog nhìn thấy.', 'content' => "## Ý tưởng ban đầu\n\nĐây là nội dung đang được hoàn thiện.", 'status' => 'draft',
        ]);
        $first = Post::withTrashed()->where('slug', 'tu-php-thuan-den-laravel')->firstOrFail();
        Comment::firstOrCreate(['post_id' => $first->id, 'user_id' => $reader->id, 'body' => 'Cảm ơn bạn đã chia sẻ hành trình học Laravel!'], ['status' => 'approved']);
        Comment::firstOrCreate(['post_id' => $first->id, 'user_id' => $reader->id, 'body' => 'Mình đang chờ bài viết tiếp theo.'], ['status' => 'pending']);
        $reader->bookmarks()->syncWithoutDetaching([$first->id]);
    }
}
