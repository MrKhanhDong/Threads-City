<?php

namespace App\Repositories;

use Illuminate\Support\Collection;

/** Read-only fixtures; replace with Eloquent after the MySQL schema is approved. */
class DemoBlogRepository
{
    public function author(): array
    {
        return ['name' => 'Duy Khang', 'initials' => 'DK', 'role' => 'Lập trình viên & người viết',
            'bio' => 'Mình viết về lập trình, những trải nghiệm nhỏ và hành trình làm mọi thứ tốt hơn mỗi ngày.',
            'email' => 'khang@example.com'];
    }

    public function categories(): Collection
    {
        return collect([
            ['slug' => 'lap-trinh', 'name' => 'Lập trình', 'description' => 'Ghi chép từ những dòng code và các dự án nhỏ.', 'icon' => 'code'],
            ['slug' => 'cong-nghe', 'name' => 'Công nghệ', 'description' => 'Công cụ, mã nguồn mở và những điều đáng khám phá.', 'icon' => 'layers'],
            ['slug' => 'doi-song', 'name' => 'Đời sống', 'description' => 'Chậm lại một chút, để nhìn rõ hơn những điều thường ngày.', 'icon' => 'coffee'],
        ])->map(fn (array $category) => [...$category, 'count' => $this->posts()->where('category_slug', $category['slug'])->count()]);
    }

    public function posts(): Collection
    {
        return collect([
            [
                'id' => 1, 'slug' => 'tu-php-thuan-den-laravel', 'title' => 'Đi từ PHP thuần đến Laravel: những điều mình học được',
                'excerpt' => 'Không chỉ là đổi framework. Đó là lúc mình học cách tổ chức code, tách trách nhiệm và nghĩ xa hơn một trang web chạy được.',
                'category' => 'Lập trình', 'category_slug' => 'lap-trinh', 'date' => '2026-10-08', 'date_label' => '08 tháng 10, 2026', 'read_time' => 6,
                'image' => 'images/code-night.jpg', 'image_alt' => 'Màn hình code trong một góc làm việc yên tĩnh', 'tags' => ['PHP', 'Laravel', 'Ghi chép'],
                'sections' => [
                    ['heading' => 'Bắt đầu từ những tệp PHP nhỏ', 'paragraphs' => ['Dự án đầu tiên của mình chỉ có vài tệp PHP. Một tệp nhận request, một tệp kết nối dữ liệu, phần còn lại dựng HTML. Cách đó giúp mình hiểu rất rõ một trang web được tạo ra như thế nào.', 'Nhưng khi số trang tăng lên, mình bắt đầu thấy những đoạn code lặp lại: kiểm tra dữ liệu, xử lý đường dẫn, rồi truyền biến vào giao diện. Mình cần một cách tổ chức nhất quán hơn.']],
                    ['heading' => 'Học cách phân chia trách nhiệm', 'paragraphs' => ['Điều mình thích ở Laravel là ranh giới rõ ràng giữa route, controller và view. Route mô tả địa chỉ, controller điều phối request, còn Blade tập trung vào việc hiển thị.', 'Mình không cố tạo thật nhiều lớp ngay từ đầu. Một repository nhỏ cho nguồn dữ liệu và một controller dễ đọc đã đủ để dự án tiến thêm một bước. Khi có MySQL, nguồn dữ liệu có thể thay đổi mà phần giao diện vẫn giữ nguyên.']],
                    ['heading' => 'Một bài học về việc làm chậm lại', 'paragraphs' => ['Chuyển framework không có nghĩa là bỏ qua nền tảng. HTTP, SQL, kiểm tra đầu vào và cách quản lý phiên vẫn là những điều mình cần hiểu.', 'Mình dành thời gian kiểm tra từng trang, thử đường dẫn không tồn tại và đọc lại các thông báo lỗi. Những việc nhỏ đó khiến mình tự tin hơn vào code mình đang viết.']],
                    ['heading' => 'Điều mình muốn làm tiếp', 'paragraphs' => ['Blog này là một nơi để ghi lại hành trình đó. Không cần bảng tin, không cần chạy theo những con số tương tác; chỉ cần một bài viết đủ rõ ràng để người đọc mang về điều gì đó.', 'Bước tiếp theo sẽ là hoàn thiện mô hình dữ liệu, quản lý bản nháp và xuất bản bài viết. Mỗi phần đều sẽ được xây dựng dựa trên nhu cầu thật của một blog cá nhân.']],
                ],
            ],
            [
                'id' => 2, 'slug' => 'mot-controller-de-doc', 'title' => 'Một controller dễ đọc bắt đầu từ đâu?',
                'excerpt' => 'Một vài thay đổi nhỏ giúp mình bớt lạc trong chính code của mình: tên rõ ràng, hàm ngắn và trách nhiệm vừa đủ.',
                'category' => 'Lập trình', 'category_slug' => 'lap-trinh', 'date' => '2026-10-05', 'date_label' => '05 tháng 10, 2026', 'read_time' => 4,
                'image' => 'images/code-desk.jpg', 'image_alt' => 'Bàn làm việc với máy tính và dụng cụ ghi chép', 'tags' => ['Laravel', 'Clean code'],
                'sections' => [
                    ['heading' => 'Đặt tên để người khác hiểu', 'paragraphs' => ['Mình thường đọc code nhiều hơn viết code. Vì vậy, mình chọn tên biến mô tả dữ liệu và tên hàm mô tả hành động, thay vì cố rút ngắn mọi thứ.', 'Một controller tốt không cần kể toàn bộ câu chuyện của hệ thống. Nó chỉ cần cho thấy request đi vào đâu, dữ liệu đến từ đâu và response được trả ra như thế nào.']],
                    ['heading' => 'Tách nguồn dữ liệu khỏi giao diện', 'paragraphs' => ['Ở giai đoạn làm giao diện, dữ liệu mẫu là đủ. Mình đặt nó trong repository để không trộn nội dung vào Blade.', 'Khi cấu trúc MySQL được thống nhất, mình có thể thay nguồn này bằng Eloquent. Cách chia nhỏ đó giúp mỗi lần thay đổi dễ kiểm tra hơn.']],
                ],
            ],
            [
                'id' => 3, 'slug' => 'goc-lam-viec-it-hon', 'title' => 'Một góc làm việc ít đồ hơn, một ngày nhẹ đầu hơn',
                'excerpt' => 'Mình dọn lại chiếc bàn, tắt vài thông báo và nhận ra sự tập trung đôi khi đến từ những điều rất đơn giản.',
                'category' => 'Đời sống', 'category_slug' => 'doi-song', 'date' => '2026-10-01', 'date_label' => '01 tháng 10, 2026', 'read_time' => 3,
                'image' => null, 'image_alt' => '', 'tags' => ['Đời sống', 'Không gian làm việc'],
                'sections' => [
                    ['heading' => 'Bắt đầu bằng việc bỏ bớt', 'paragraphs' => ['Mình cất những vật dụng không dùng hằng ngày khỏi mặt bàn. Không gian trống hơn một chút, và mình cũng thấy dễ bắt đầu công việc hơn.', 'Đây không phải một công thức cho tất cả mọi người. Chỉ là một thay đổi nhỏ đã phù hợp với cách mình làm việc.']],
                    ['heading' => 'Để lại chỗ cho một khoảng nghỉ', 'paragraphs' => ['Mình đặt cuốn sổ bên cạnh máy tính để ghi lại ý tưởng, thay vì mở thêm một tab. Một tách cà phê và vài phút nhìn ra cửa sổ cũng là một phần của buổi sáng.', 'Có những ngày không làm được nhiều. Nhưng mình đang học cách tạo ra một nhịp làm việc có thể duy trì, thay vì cố gắng hết sức rồi kiệt sức.']],
                ],
            ],
            [
                'id' => 4, 'slug' => 'ghi-chep-ve-ma-nguon-mo', 'title' => 'Mã nguồn mở và thói quen đọc code của người khác',
                'excerpt' => 'Có những bài học mình không tìm thấy trong một tutorial, nhưng lại tìm thấy trong một commit rất nhỏ.',
                'category' => 'Công nghệ', 'category_slug' => 'cong-nghe', 'date' => '2026-09-26', 'date_label' => '26 tháng 09, 2026', 'read_time' => 5,
                'image' => null, 'image_alt' => '', 'tags' => ['Mã nguồn mở', 'Git'],
                'sections' => [
                    ['heading' => 'Không cần hiểu cả dự án ngay', 'paragraphs' => ['Mình bắt đầu từ một issue nhỏ hoặc một hàm mình đang sử dụng. Đọc test đi kèm giúp mình hiểu tác giả mong đợi điều gì từ đoạn code đó.', 'Thay vì tải về rồi bỏ quên, mình dành một khoảng thời gian ngắn để theo dõi một thay đổi cụ thể trong dự án.']],
                    ['heading' => 'Đóng góp cũng là một cách học', 'paragraphs' => ['Một bản sửa tài liệu hoặc một báo cáo lỗi rõ ràng đều có ích. Mình học cách mô tả vấn đề, cung cấp bước tái hiện và lắng nghe phản hồi.', 'Mã nguồn mở với mình không chỉ là công cụ miễn phí, mà còn là cơ hội để nhìn thấy cách nhiều người cùng chăm sóc một sản phẩm.']],
                ],
            ],
        ]);
    }

    public function find(string $slug): ?array { return $this->posts()->firstWhere('slug', $slug); }
    public function shared(): array { return ['author' => $this->author(), 'categories' => $this->categories(), 'allPosts' => $this->posts()]; }
}
