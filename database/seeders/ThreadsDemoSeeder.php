<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class ThreadsDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Users from Screenshot & Core Users
        $usersData = [
            [
                'name' => 'badat3927',
                'username' => 'badat3927',
                'email' => 'badat@threads.local',
                'password' => 'Password@12345',
                'bio' => 'Cập nhật tin tức đời sống và thời sự quốc tế.',
                'role' => 'reader',
            ],
            [
                'name' => 'hi_dungchuytoi',
                'username' => 'hi_dungchuytoi',
                'email' => 'dungchuytoi@threads.local',
                'password' => 'Password@12345',
                'bio' => 'Đừng chọc tôi 🤦',
                'role' => 'reader',
            ],
            [
                'name' => 'trngotanh',
                'username' => 'trngotanh',
                'email' => 'trngotanh@threads.local',
                'password' => 'Password@12345',
                'bio' => 'Slv dog',
                'role' => 'reader',
            ],
            [
                'name' => 'minhanna2025',
                'username' => 'minhanna2025',
                'email' => 'minhanna@threads.local',
                'password' => 'Password@12345',
                'bio' => 'du học sinh',
                'role' => 'reader',
            ],
            [
                'name' => 'Liên Bỉnh Phát',
                'username' => 'binhphatlien',
                'email' => 'binhphat@threads.local',
                'password' => 'Password@12345',
                'bio' => "Some pieces of my life ...\n127K người theo dõi",
                'role' => 'reader',
                'is_verified' => true,
            ],
            [
                'name' => 'Diệu Nhi',
                'username' => 'dieu_nhiii',
                'email' => 'dieunhi@threads.local',
                'password' => 'Password@12345',
                'bio' => "🎭 Actress in Vietnam 🎬\nfanpage: ...",
                'role' => 'reader',
                'is_verified' => true,
            ],
            [
                'name' => 'Khánh Đông',
                'username' => 'mrkhanhdong',
                'email' => 'dong@threads.local',
                'password' => 'Password@12345',
                'bio' => 'Creator & Developer. Xây dựng không gian kết nối cộng đồng Threads City.',
                'role' => 'admin',
            ],
            [
                'name' => 'Minh Phương',
                'username' => 'minhphuong',
                'email' => 'minhphuong@threads.local',
                'password' => 'Password@12345',
                'bio' => 'UI/UX Designer & Tech Enthusiast.',
                'role' => 'reader',
            ],
            [
                'name' => 'Hoàng Nam',
                'username' => 'hoangnam',
                'email' => 'hoangnam@threads.local',
                'password' => 'Password@12345',
                'bio' => 'Nhiếp ảnh đường phố và cà phê sáng 📸☕',
                'role' => 'reader',
            ],
        ];

        $users = [];
        foreach ($usersData as $uData) {
            $user = User::firstOrCreate(
                ['username' => $uData['username']],
                [
                    'name' => $uData['name'],
                    'email' => $uData['email'],
                    'password' => $uData['password'],
                    'bio' => $uData['bio'],
                    'email_verified_at' => now(),
                ]
            );
            $user->forceFill(['role' => $uData['role'], 'is_verified' => $uData['is_verified'] ?? false, 'name' => $uData['name'], 'bio' => $uData['bio']])->save();
            $users[$uData['username']] = $user;
        }

        // 2. Tags
        $tagBieutinh = Tag::firstOrCreate(['slug' => 'bieu-tinh'], ['name' => 'Biểu tình']);
        $tagThreads = Tag::firstOrCreate(['slug' => 'threads'], ['name' => 'threads']);
        $tagDuhoc = Tag::firstOrCreate(['slug' => 'du-hoc-sinh'], ['name' => 'du học sinh']);

        // 3. Thread: trngotanh (The original thread to be quoted)
        $threadTrngotanh = Post::firstOrCreate(
            ['slug' => 'thread-trngotanh-demo'],
            [
                'user_id' => $users['trngotanh']->id,
                'type' => 'post',
                'title' => 'Tâm sự đi học',
                'content' => 'Ước j t bị hiepdam xong battrong cho t có bầu chứ t chán đi học qua',
                'status' => 'published',
                'published_at' => now()->subDays(2),
                'likes_count' => 424,
                'replies_count' => 311,
                'reposts_count' => 10,
            ]
        );

        // 4. Thread: minhanna2025 (Du học Hàn)
        $threadMinhanna = Post::firstOrCreate(
            ['slug' => 'thread-minhanna-demo'],
            [
                'user_id' => $users['minhanna2025']->id,
                'type' => 'post',
                'title' => 'Ai đi du học Hàn confirm hộ t được ko:)',
                'content' => 'Ai đi du học Hàn confirm hộ t được ko:)',
                'status' => 'published',
                'published_at' => now()->subHours(12),
                'likes_count' => 152,
                'replies_count' => 48,
                'reposts_count' => 8,
            ]
        );
        $threadMinhanna->tags()->syncWithoutDetaching([$tagDuhoc->id]);

        // 5. Thread: hi_dungchuytoi (Quoting trngotanh)
        $threadQuote = Post::firstOrCreate(
            ['slug' => 'thread-hidungchuytoi-quote'],
            [
                'user_id' => $users['hi_dungchuytoi']->id,
                'repost_of_id' => $threadTrngotanh->id,
                'type' => 'quote',
                'title' => 'Thật sự cạn lời',
                'content' => 't cũng đ hiểu mày suy nghĩ gì trong đầu mà có thể phát ngôn ra từ ngáo l vậy 🤦',
                'status' => 'published',
                'published_at' => now()->subHours(12)->subMinutes(40),
                'likes_count' => 3100,
                'replies_count' => 202,
                'reposts_count' => 186,
            ]
        );

        // 6. Thread: badat3927 (Top thread with trend Biểu tình)
        $threadBadat = Post::firstOrCreate(
            ['slug' => 'thread-badat3927-bieu-tinh'],
            [
                'user_id' => $users['badat3927']->id,
                'type' => 'post',
                'title' => 'Biểu tình ở Pháp',
                'content' => "Biểu tình ở Pháp hiện là một cái gì đó rất 3 ...\n- Học sinh éo thấy đâu , toàn người nhập cư và dân Palestine, Banglades xuống biểu tình cướp bóc :)).\n- Người đã tốt nghiệp cấp 3 ,đại học lại kêu ca vì trợ cấp thất nghiệp quá ít , dù cho họ không thích đi làm .\n- Một bộ học sinh muốn có giường ngủ ,hút thuốc và thêm nhiều hoạt động giải trí hơn là học",
                'status' => 'published',
                'published_at' => now()->subHours(12),
                'likes_count' => 544,
                'replies_count' => 32,
                'reposts_count' => 14,
            ]
        );
        $threadBadat->tags()->syncWithoutDetaching([$tagBieutinh->id]);
    }
}
