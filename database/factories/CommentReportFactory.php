<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommentReport>
 */
class CommentReportFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['comment_id' => Comment::factory(), 'user_id' => User::factory(), 'reason' => 'Nội dung không phù hợp.', 'resolved' => false];
    }
}
