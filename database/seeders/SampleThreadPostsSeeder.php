<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SampleThreadPostsSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, array{title: string, content: string}> $samples */
        $samples = [
            'ca-phe-sang' => [
                'title' => 'Một buổi sáng thật chậm',
                'content' => 'Sáng nay thử dậy sớm hơn 20 phút, pha một ly cà phê rồi ngồi ngắm phố. Chưa làm được gì lớn nhưng thấy ngày mới dễ chịu hơn hẳn. Mọi người thường bắt đầu buổi sáng thế nào? ☕ #SongCham',
            ],
            'goc-hoc-tap' => [
                'title' => 'Dọn bàn rồi mới học',
                'content' => 'Vừa dọn lại góc học tập, cất điện thoại ra xa và bật một playlist không lời. Hóa ra đôi khi thứ mình cần không phải thêm động lực mà là bớt thứ gây xao nhãng. 📚 #HocTap',
            ],
            'hoan-thanh-do-an' => [
                'title' => 'Niềm vui sau một lỗi khó',
                'content' => 'Cảm giác sửa được một lỗi đã ngồi tìm cả buổi thật khó tả. Ghi lại nguyên nhân và cách xử lý ngay, để tuần sau gặp lại không phải bắt đầu từ số không. 💻 #LapTrinh',
            ],
            'bua-com-nha' => [
                'title' => 'Bữa cơm đơn giản',
                'content' => 'Hôm nay tự nấu được một bữa cơm có canh, rau và món mặn. Chẳng cần cầu kỳ, chỉ cần ăn lúc còn nóng là thấy vui rồi. Món tủ của mọi người là gì? 🍲 #AmThuc',
            ],
            'di-bo-chieu' => [
                'title' => 'Đi bộ thay vì lướt điện thoại',
                'content' => 'Chiều nay đi bộ quanh khu phố thay vì nằm lướt điện thoại. Phát hiện một tiệm bánh nhỏ trước giờ đi ngang mà chưa từng để ý. Những điều thú vị đôi khi ở rất gần. 🌿 #DoiSong',
            ],
            'playlist-ngay-mua' => [
                'title' => 'Nhạc cho ngày mưa',
                'content' => 'Trời mưa, đeo tai nghe và nghe lại một album cũ. Có những bài hát chỉ cần câu đầu vang lên là cả một khoảng ký ức quay về. Xin một bài mọi người đang nghe nhiều nhất tuần này. 🎧 #AmNhac',
            ],
            'doc-sach-moi-ngay' => [
                'title' => 'Mười trang sách mỗi tối',
                'content' => 'Đặt mục tiêu đọc mười trang mỗi tối thay vì cố đọc hết một cuốn thật nhanh. Không cần nhớ mọi câu chữ, chỉ cần mang theo được một ý hay là đủ. 📖 #DocSach',
            ],
            'chuyen-di-cuoi-tuan' => [
                'title' => 'Một chuyến đi ngắn',
                'content' => 'Cuối tuần muốn đi đâu đó gần thành phố, có cây xanh và chỗ ngồi yên tĩnh. Không cần lịch trình kín, chỉ muốn đổi không khí một chút. Ai có gợi ý địa điểm không? 🧳 #DuLich',
            ],
            'hoc-git-cung-nhau' => [
                'title' => 'Làm việc nhóm với Git',
                'content' => 'Một thói quen đang tập khi làm đồ án nhóm: chia thay đổi thành từng commit nhỏ và viết rõ mình sửa gì. Đến lúc review hoặc tìm lỗi, cả nhóm đều đỡ mất thời gian hơn. 🌱 #LapTrinh',
            ],
            'quan-an-quen' => [
                'title' => 'Quán quen và món quen',
                'content' => 'Có ai giống mình không: đến quán quen thì mở menu rất lâu, cuối cùng vẫn gọi đúng món cũ. Chắc cảm giác biết trước mình sẽ ăn ngon cũng là một kiểu hạnh phúc. 😄 #AmThuc',
            ],
            'buoi-hoc-nhom' => [
                'title' => 'Một buổi học nhóm hiệu quả',
                'content' => 'Học nhóm hôm nay thử cách mỗi người giải thích một phần cho cả nhóm. Chỗ nào nói chưa rõ thì cùng tìm lại tài liệu. Vừa học thêm vừa biết mình còn hổng ở đâu. 🤝 #HocTap',
            ],
            'anh-hoang-hon' => [
                'title' => 'Bầu trời lúc tan học',
                'content' => 'Tan học muộn một chút lại gặp bầu trời màu hồng cam rất đẹp. Đứng nhìn vài phút rồi mới về. Có những khoảnh khắc không chụp được ảnh nhưng vẫn nhớ rất lâu. 🌇 #DoiSong',
            ],
            'cham-soc-cay' => [
                'title' => 'Chiếc lá mới bên cửa sổ',
                'content' => 'Chậu cây trên bàn vừa ra thêm một chiếc lá mới. Việc nhỏ xíu thôi mà khiến mình vui cả buổi. Hôm nay có điều gì nhỏ bé làm mọi người mỉm cười không? 🪴 #SongCham',
            ],
            'thu-nau-mon-moi' => [
                'title' => 'Lần đầu thử làm bánh',
                'content' => 'Lần đầu làm bánh tại nhà: hình dáng chưa đẹp nhưng mùi thơm thì rất ổn. Ghi lại công thức đã chỉnh để lần sau thử tiếp. Chấp nhận chưa giỏi cũng là một phần của việc học. 🥐 #AmThuc',
            ],
            'thoi-quen-ghi-chu' => [
                'title' => 'Ghi lại điều đã học',
                'content' => 'Cuối ngày dành năm phút ghi lại ba điều: hôm nay học được gì, còn vướng chỗ nào và ngày mai sẽ bắt đầu từ đâu. Một trang ghi chú ngắn giúp mình bớt rối hơn nhiều. ✍️ #HocTap',
            ],
            'choi-the-thao' => [
                'title' => 'Một buổi cầu lông sau giờ học',
                'content' => 'Lâu rồi mới có một buổi cầu lông với bạn bè. Đánh chưa hay nhưng cười rất nhiều. Tuần tới nhất định sắp xếp thêm một buổi nữa. 🏸 #TheThao',
            ],
            'phim-cuoi-tuan' => [
                'title' => 'Tối nay xem phim gì?',
                'content' => 'Đang tìm một bộ phim nhẹ nhàng để xem cuối tuần, có thể là hoạt hình hoặc một câu chuyện đời thường. Mọi người có bộ nào xem xong thấy lòng dễ chịu hơn không? 🎬 #DienAnh',
            ],
            'thiet-ke-giao-dien' => [
                'title' => 'Một thay đổi nhỏ trên giao diện',
                'content' => 'Thử tăng khoảng cách giữa các đoạn và làm nhãn nút rõ hơn, trang nhìn dễ đọc hẳn. Đôi khi cải thiện trải nghiệm bắt đầu từ những chi tiết rất nhỏ. 🎨 #ThietKe',
            ],
            'loi-cam-on' => [
                'title' => 'Cảm ơn người bạn cùng nhóm',
                'content' => 'Dành một lời cảm ơn cho người bạn vẫn kiên nhẫn nghe mình giải thích lỗi và cùng tìm cách sửa. Làm đồ án mệt thật, nhưng có đồng đội biết hỗ trợ nhau thì mọi thứ nhẹ hơn nhiều. 💛 #CongDong',
            ],
            'ke-hoach-ngay-mai' => [
                'title' => 'Ba việc cho ngày mai',
                'content' => 'Kế hoạch ngày mai chỉ có ba việc: hoàn thành một phần đồ án, gọi điện về nhà và ngủ sớm hơn hôm nay. Không cần một danh sách quá dài để có một ngày ý nghĩa. 🌙 #DoiSong',
            ],
        ];

        $created = DB::transaction(function () use ($samples): int {
            $authors = User::query()->where('is_locked', false)->where('is_private', false)->orderBy('id')->get();
            if ($authors->isEmpty()) {
                throw new RuntimeException('Cần ít nhất một tài khoản công khai đang hoạt động để tạo bài mẫu.');
            }

            $created = 0;
            foreach (array_values(array_keys($samples)) as $index => $slug) {
                $sampleSlug = 'sample-thread-'.$slug;
                if (Post::withTrashed()->where('slug', $sampleSlug)->exists()) {
                    continue;
                }

                $sample = $samples[$slug];
                $publishedAt = now()->subMinutes(5 + $index * 23);
                $post = new Post([
                    'user_id' => $authors[$index % $authors->count()]->id,
                    'type' => 'post',
                    'title' => $sample['title'],
                    'slug' => $sampleSlug,
                    'content' => '[Bài mẫu] '.$sample['content'],
                    'content_format' => 'plain',
                    'status' => 'published',
                    'published_at' => $publishedAt,
                ]);
                $post->created_at = $publishedAt;
                $post->updated_at = $publishedAt;
                $post->save();
                $created++;
            }

            return $created;
        });

        $this->command?->info('Đã tạo '.$created.' bài post mẫu. Bài đã tồn tại hoặc đã xóa được giữ nguyên.');
    }
}
