<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use App\Services\HtmlSanitizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Dữ liệu demo chỉ được tạo ở môi trường local/testing.');
        }

        DB::transaction(function (): void {
            $admin = User::where('email', 'admin@blog.local')->first();
            if (! $admin) {
                $admin = new User(['name' => 'Tác giả Mộc', 'email' => 'admin@blog.local', 'password' => 'Admin@12345', 'bio' => 'Viết về những điều giản dị, học tập và những hành trình nhỏ.']);
                $admin->forceFill(['role' => 'admin', 'email_verified_at' => now()])->save();
            }
            if (! $admin->isAdmin()) {
                throw new \RuntimeException('Email demo admin đã thuộc một độc giả; không tự nâng quyền.');
            }

            $reader = User::where('email', 'reader@blog.local')->first();
            if (! $reader) {
                $reader = new User(['name' => 'Độc giả Mộc', 'email' => 'reader@blog.local', 'password' => 'Reader@12345']);
                $reader->forceFill(['role' => 'reader', 'email_verified_at' => now()])->save();
            }

            $categories = collect([
                ['name' => 'Đời sống', 'slug' => 'doi-song', 'description' => 'Những ghi chép về cuộc sống thường ngày.'],
                ['name' => 'Học tập', 'slug' => 'hoc-tap', 'description' => 'Chia sẻ kinh nghiệm học tập và làm việc.'],
                ['name' => 'Hành trình', 'slug' => 'hanh-trinh', 'description' => 'Chuyện kể từ những chuyến đi.'],
            ])->mapWithKeys(function (array $data): array {
                return [$data['slug'] => Category::firstOrCreate(['slug' => $data['slug']], $data)];
            });
            $tags = collect([
                'viet-lach' => 'Viết lách', 'song-cham' => 'Sống chậm', 'thoi-quen' => 'Thói quen',
                'laravel' => 'Laravel', 'php' => 'PHP', 'du-lich' => 'Du lịch', 'nhat-ky' => 'Nhật ký',
            ])->mapWithKeys(fn (string $name, string $slug): array => [$slug => Tag::firstOrCreate(['slug' => $slug], ['name' => $name])]);

            $samples = [
                ['slug' => 'mot-goc-viet-mot-nhip-song-cham', 'title' => 'Một góc viết, một nhịp sống chậm', 'excerpt' => 'Đôi khi, một buổi sáng yên tĩnh và vài dòng viết tay là đủ để bắt đầu lại.', 'category' => 'doi-song', 'tags' => ['viet-lach', 'song-cham'], 'featured' => true,
                    'content' => '<h2>Bắt đầu từ những điều nhỏ</h2><p>Có những ngày tôi nhận ra mình đã đi quá nhanh. Lịch làm việc kín, những thông báo nối tiếp nhau, còn cuốn sổ trên bàn thì vẫn trắng.</p><p><strong>Viết không cần một lý do lớn.</strong> Tôi bắt đầu bằng việc ghi lại một điều khiến mình biết ơn mỗi ngày.</p><blockquote><p>Chậm lại không có nghĩa là đứng yên. Đó là cho mình thời gian để nhìn rõ hơn.</p></blockquote><h2>Một thói quen dễ giữ</h2><ul><li>Dành mười phút trước khi mở điện thoại.</li><li>Viết một đoạn ngắn, không sửa quá nhiều.</li><li>Đọc lại sau một tuần để nhận ra những thay đổi.</li></ul><h3>Khoảng trống cũng quan trọng</h3><p>Không phải ngày nào cũng có điều đặc biệt. Những khoảng lặng giúp tôi hiểu điều gì thật sự quan trọng.</p>'],
                ['slug' => 'hoc-laravel-tu-mot-do-an-nho', 'title' => 'Học Laravel từ một đồ án nhỏ', 'excerpt' => 'Từ migration đầu tiên đến một trang blog: học bằng cách làm và kiểm thử từng phần.', 'category' => 'hoc-tap', 'tags' => ['laravel', 'php'], 'featured' => false,
                    'content' => '<h2>Chia bài toán thành từng phần</h2><p>Tôi chọn blog cá nhân để thực hành Laravel. Tác giả quản lý bài viết; độc giả đọc, lưu bài và bình luận. Phạm vi rõ ràng giúp tránh thêm chức năng không cần thiết.</p><h2>Một lộ trình thực tế</h2><table><thead><tr><th>Phần</th><th>Kết quả cần kiểm tra</th></tr></thead><tbody><tr><td>Database</td><td>Khóa ngoại và quan hệ hoạt động đúng.</td></tr><tr><td>Editor</td><td>Định dạng được lưu và hiển thị an toàn.</td></tr><tr><td>Phân quyền</td><td>Độc giả không truy cập được trang quản trị.</td></tr></tbody></table><h2>Đừng bỏ qua kiểm thử</h2><p>Một chức năng chỉ đáng tin khi đã kiểm tra cả trường hợp thành công lẫn dữ liệu không hợp lệ.</p><p>Bạn có thể đọc thêm <a href="/bai-viet/mot-goc-viet-mot-nhip-song-cham">câu chuyện bắt đầu viết blog của tôi</a>.</p>'],
                ['slug' => 'di-mot-chut-de-thay-nhieu-hon', 'title' => 'Đi một chút để thấy nhiều hơn', 'excerpt' => 'Một chuyến đi ngắn, vài cuộc trò chuyện và những điều rất đỗi bình thường.', 'category' => 'hanh-trinh', 'tags' => ['du-lich', 'nhat-ky'], 'featured' => false,
                    'content' => '<h2>Một chuyến đi không có lịch dày</h2><p>Tôi thử dành cuối tuần cho một nơi gần nhà. Không cố ghé hết mọi điểm, chỉ chọn một con đường và đi bộ thật chậm.</p><h2>Những câu chuyện trên đường</h2><p>Một quán cà phê nhỏ, người bán sách cũ, tiếng mưa ngoài hiên: những chi tiết ấy làm chuyến đi trở nên đáng nhớ.</p><ol><li>Mang theo ít đồ hơn.</li><li>Chừa thời gian cho những điều bất ngờ.</li><li>Ghi lại cảm xúc trước khi chọn ảnh đẹp.</li></ol>'],
                ['slug' => 'giu-mot-thoi-quen-nho-moi-ngay', 'title' => 'Giữ một thói quen nhỏ mỗi ngày', 'excerpt' => 'Không cần thay đổi tất cả cùng lúc. Một bước nhỏ, làm đều đặn, cũng tạo nên khác biệt.', 'category' => 'doi-song', 'tags' => ['thoi-quen', 'song-cham'], 'featured' => false,
                    'content' => '<h2>Nhỏ đến mức dễ bắt đầu</h2><p>Thay vì đặt mục tiêu viết một trang, tôi bắt đầu bằng ba câu. Thay vì đọc một chương, tôi đọc hai trang.</p><h2>Đo sự đều đặn, không đo sự hoàn hảo</h2><p><em>Thói quen tốt phải có chỗ cho những ngày bận rộn.</em> Nếu bỏ lỡ một ngày, tôi tiếp tục vào ngày hôm sau mà không tự trách.</p><p style="text-align:center;color:#24332d">Một bước nhỏ hôm nay vẫn là một bước tiến.</p>'],
            ];

            foreach ($samples as $index => $sample) {
                if (Post::withTrashed()->where('slug', $sample['slug'])->exists()) {
                    continue;
                }
                $post = new Post([
                    'title' => $sample['title'], 'slug' => $sample['slug'], 'excerpt' => $sample['excerpt'],
                    'content' => app(HtmlSanitizer::class)->clean($sample['content']), 'status' => 'published',
                    'published_at' => now()->subDays(8 - $index), 'is_featured' => $sample['featured'],
                    'seo_title' => $sample['title'], 'meta_description' => $sample['excerpt'],
                ]);
                $post->user_id = $admin->id;
                $post->save();
                $post->categories()->sync([$categories[$sample['category']]->id]);
                $post->tags()->sync(collect($sample['tags'])->map(fn (string $slug): int => $tags[$slug]->id)->all());
            }

            if (! Post::withTrashed()->where('slug', 'ban-nhap-chuyen-chua-ke')->exists()) {
                $draft = new Post(['title' => 'Bản nháp: chuyện chưa kể', 'slug' => 'ban-nhap-chuyen-chua-ke', 'content' => '<p>Đây là bản nháp mẫu, không xuất hiện trên website công khai.</p>', 'status' => 'draft']);
                $draft->user_id = $admin->id;
                $draft->save();
                $draft->categories()->sync([$categories['doi-song']->id]);
            }

            $article = Post::where('slug', 'mot-goc-viet-mot-nhip-song-cham')->first();
            if ($article) {
                $comment = Comment::firstOrCreate(['post_id' => $article->id, 'user_id' => $reader->id, 'body' => 'Mình thích ý tưởng dành mười phút để viết mỗi sáng.'], ['status' => 'approved']);
                Comment::firstOrCreate(['post_id' => $article->id, 'user_id' => $admin->id, 'parent_id' => $comment->id, 'body' => 'Cảm ơn bạn đã đọc. Bắt đầu thật nhỏ sẽ dễ duy trì hơn.'], ['status' => 'approved']);
                Comment::firstOrCreate(['post_id' => $article->id, 'user_id' => $reader->id, 'body' => 'Một câu chuyện nhỏ giúp mình có thêm cảm hứng viết.'], ['status' => 'approved']);
            }

            foreach (['site_name' => config('blog.name'), 'site_tagline' => config('blog.tagline'), 'site_description' => config('blog.description'), 'about' => config('blog.about'), 'contact_email' => 'hello@example.com'] as $key => $value) {
                Setting::firstOrCreate(['key' => $key], ['value' => $value]);
            }
        });
    }
}
