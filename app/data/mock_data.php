<?php

declare(strict_types=1);

$currentUser = [
    'name' => 'Duy Khang',
    'handle' => '@duykhang',
    'bio' => 'Backend developer, yêu PHP và thích chia sẻ những điều học được mỗi ngày.',
    'website' => 'duykhang.dev',
    'avatar_class' => 'avatar-blue',
    'posts_count' => 18,
    'followers_count' => 1240,
    'following_count' => 186,
];

$categories = [
    ['name' => 'Technology', 'slug' => 'technology', 'count' => 24],
    ['name' => 'Programming', 'slug' => 'programming', 'count' => 42],
    ['name' => 'Lifestyle', 'slug' => 'lifestyle', 'count' => 16],
    ['name' => 'Travel', 'slug' => 'travel', 'count' => 12],
    ['name' => 'Education', 'slug' => 'education', 'count' => 30],
];

$tags = ['#PHP', '#Laravel', '#JavaScript', '#WebDevelopment', '#Life', '#React'];

$suggestedAuthors = [
    ['name' => 'Minh Anh', 'handle' => '@minhanh_tech', 'avatar_class' => 'avatar-gold'],
    ['name' => 'Thanh Hà', 'handle' => '@thanhha_design', 'avatar_class' => 'avatar-pink'],
    ['name' => 'Hoàng Nam', 'handle' => '@hoangnam_dev', 'avatar_class' => 'avatar-purple'],
];

$posts = [
    [
        'id' => 1,
        'author' => 'Duy Khang',
        'handle' => '@duykhang',
        'avatar_class' => 'avatar-blue',
        'time' => '2 giờ trước',
        'category' => 'Programming',
        'title' => 'Học PHP có còn đáng học vào năm 2026?',
        'excerpt' => 'PHP vẫn là một trong những công nghệ backend phổ biến nhất trong phát triển website thế giới với hệ sinh thái Laravel và WordPress rất lớn.',
        'image' => 'public/images/code-night.jpg',
        'likes' => 128,
        'comments_count' => 24,
        'liked' => false,
        'saved' => false,
        'tags' => ['PHP', 'Backend', 'WebDevelopment'],
        'content' => [
            'Mỗi năm đều có người hỏi PHP đã lỗi thời chưa. Câu trả lời ngắn gọn là chưa. PHP vẫn vận hành một phần rất lớn của web và tiếp tục được cải tiến về hiệu năng, kiểu dữ liệu và trải nghiệm phát triển.',
            'Điểm mạnh của PHP nằm ở cộng đồng lâu đời, tài liệu phong phú và hệ sinh thái thực chiến. Với Laravel, chúng ta có thể xây dựng ứng dụng theo kiến trúc rõ ràng, kiểm thử được và dễ mở rộng thay vì chỉ viết các tệp PHP rời rạc.',
            'Nếu mục tiêu của bạn là làm web, học PHP cùng nền tảng HTTP, SQL, bảo mật và Git vẫn là một lựa chọn thực tế. Công nghệ chỉ là công cụ; khả năng thiết kế hệ thống và giải quyết vấn đề mới là thứ tạo ra khác biệt.',
        ],
    ],
    [
        'id' => 2,
        'author' => 'Minh Anh',
        'handle' => '@minhanh_tech',
        'avatar_class' => 'avatar-gold',
        'time' => '5 giờ trước',
        'category' => 'Programming',
        'title' => 'Xây dựng Clean Architecture trong Laravel từ cơ bản đến nâng cao',
        'excerpt' => 'Tách biệt Business Logic ra khỏi Controller giúp hệ thống dễ dàng bảo trì, viết Unit Test và mở rộng quy mô dự án lớn.',
        'image' => 'public/images/code-desk.jpg',
        'likes' => 95,
        'comments_count' => 12,
        'liked' => true,
        'saved' => true,
        'tags' => ['Laravel', 'Architecture', 'CleanCode'],
        'content' => [
            'Clean Architecture không bắt đầu từ việc tạo thật nhiều thư mục. Nó bắt đầu từ việc xác định ranh giới giữa nghiệp vụ, dữ liệu và giao diện.',
            'Trong Laravel, Controller chỉ nên nhận request và trả response. Use Case điều phối nghiệp vụ, Repository che giấu nguồn dữ liệu, còn Entity giữ các quy tắc cốt lõi của hệ thống.',
            'Cách chia này đặc biệt hữu ích khi dự án cần đổi MySQL sang API ngoài hoặc bổ sung hàng đợi mà không làm ảnh hưởng toàn bộ ứng dụng.',
        ],
    ],
    [
        'id' => 3,
        'author' => 'Thanh Hà',
        'handle' => '@thanhha_design',
        'avatar_class' => 'avatar-pink',
        'time' => '1 ngày trước',
        'category' => 'Lifestyle',
        'title' => 'Tối giản không gian làm việc để tăng 200% khả năng tập trung',
        'excerpt' => 'Một góc làm việc sạch sẽ, gọn gàng với ánh sáng tự nhiên giúp mình tập trung lâu hơn và ít bị phân tâm.',
        'image' => null,
        'likes' => 76,
        'comments_count' => 18,
        'liked' => false,
        'saved' => false,
        'tags' => ['Life', 'Productivity', 'Workspace'],
        'content' => [
            'Mình bắt đầu bằng cách bỏ tất cả vật dụng không dùng hằng ngày khỏi mặt bàn. Chỉ riêng thay đổi nhỏ đó đã làm không gian dễ chịu hơn rất nhiều.',
            'Tiếp theo là tắt thông báo trong các khung giờ tập trung. Một chiếc bàn đẹp không có nhiều ý nghĩa nếu màn hình liên tục kéo sự chú ý của bạn sang việc khác.',
        ],
    ],
    [
        'id' => 4,
        'author' => 'Hoàng Nam',
        'handle' => '@hoangnam_dev',
        'avatar_class' => 'avatar-purple',
        'time' => '2 ngày trước',
        'category' => 'Technology',
        'title' => '5 công cụ mã nguồn mở mình dùng mỗi ngày',
        'excerpt' => 'Từ quản lý source code đến kiểm thử API, đây là những công cụ miễn phí đã giúp quy trình làm việc của mình tốt hơn.',
        'image' => null,
        'likes' => 64,
        'comments_count' => 9,
        'liked' => false,
        'saved' => true,
        'tags' => ['OpenSource', 'Tools', 'Developer'],
        'content' => [
            'Mã nguồn mở không chỉ giúp tiết kiệm chi phí. Nó cho phép chúng ta hiểu công cụ đang dùng, đóng góp bản vá và học trực tiếp từ những dự án thực tế.',
            'Danh sách của mình gồm Git, VS Code, Bruno, Docker và Penpot. Mỗi công cụ giải quyết một phần nhỏ nhưng quan trọng trong quy trình phát triển phần mềm.',
        ],
    ],
];

$comments = [
    1 => [
        [
            'id' => 1,
            'author' => 'Minh Anh',
            'handle' => '@minhanh_tech',
            'avatar_class' => 'avatar-gold',
            'time' => '1 giờ trước',
            'body' => 'Đồng ý. PHP hiện đại khác rất xa PHP của nhiều năm trước, nhất là khi dùng Composer và framework đúng cách.',
            'likes' => 12,
            'replies' => [
                [
                    'id' => 2,
                    'author' => 'Duy Khang',
                    'handle' => '@duykhang',
                    'avatar_class' => 'avatar-blue',
                    'time' => '45 phút trước',
                    'body' => 'Chuẩn luôn, Composer thay đổi hoàn toàn cách tổ chức một dự án PHP.',
                    'likes' => 4,
                ],
            ],
        ],
        [
            'id' => 3,
            'author' => 'Hoàng Nam',
            'handle' => '@hoangnam_dev',
            'avatar_class' => 'avatar-purple',
            'time' => '30 phút trước',
            'body' => 'Bài viết rất thực tế. Quan trọng nhất vẫn là nắm chắc nền tảng web và database.',
            'likes' => 7,
            'replies' => [],
        ],
    ],
];

