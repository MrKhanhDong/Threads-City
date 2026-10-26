<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\TrendingTopic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TrendingTopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $topics = [
            [
                'tag' => 'GMMTV',
                'title' => 'JoongDunk và AouBoom dừng diễn cặp',
                'desc' => 'GMMTV thông báo JoongDunk và AouBoom thống nhất dừng hoạt động với...',
                'count' => '5K',
                'posts_count' => 5000,
                'order' => 1,
                'is_active' => true,
            ],
            [
                'tag' => 'KOSMIK',
                'title' => 'KOSMIK Concert 2026 mở bán vé',
                'desc' => 'Sự kiện KOSMIK Live Concert 2026 mở bán vé cho đêm nhạc ngày 12/12/2026 tại Hà...',
                'count' => '1K',
                'posts_count' => 1000,
                'order' => 2,
                'is_active' => true,
            ],
            [
                'tag' => 'Biểu tình',
                'title' => 'Biểu tình ở Pháp thành bạo loạn',
                'desc' => 'Nhiều đối tượng quá khích đập phá, phóng hỏa cơ sở kinh doanh ở thủ đô Paris...',
                'count' => '3K',
                'posts_count' => 3000,
                'order' => 3,
                'is_active' => true,
            ],
            [
                'tag' => 'SayHi',
                'title' => 'IVAN biểu diễn tại Tinh Hà Say Hi',
                'desc' => 'Người hâm mộ bênh vực IVAN sau màn trình diễn tại hai đêm concert Tinh Hà Say...',
                'count' => '6K',
                'posts_count' => 6000,
                'order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($topics as $item) {
            TrendingTopic::updateOrCreate(
                ['title' => $item['title']],
                $item
            );

            if (! empty($item['tag'])) {
                Tag::firstOrCreate(
                    ['slug' => Str::slug($item['tag'])],
                    ['name' => $item['tag']]
                );
            }
        }
    }
}
