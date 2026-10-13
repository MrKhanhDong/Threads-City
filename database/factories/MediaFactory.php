<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'path' => 'media/'.fake()->uuid().'.jpg', 'original_name' => 'test-image.jpg', 'mime_type' => 'image/jpeg', 'size' => 1024, 'width' => 640, 'height' => 480, 'alt' => 'Ảnh dùng trong kiểm thử'];
    }
}
