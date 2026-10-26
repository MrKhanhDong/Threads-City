<?php

namespace Tests\Feature;

use App\Http\Controllers\ThreadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ThreadPollValidationTest extends TestCase
{
    #[TestWith([['content' => 'Regular post']])]
    #[TestWith([['content' => 'Regular post', 'poll_options' => []]])]
    #[TestWith([['content' => 'Regular post', 'poll_options' => null]])]
    #[TestWith([['content' => 'Question', 'poll_options' => ['Yes', 'No']]])]
    #[TestWith([['poll_options' => ['0', '1']]])]
    public function test_valid_posts_pass_validation_before_persistence(array $payload): void
    {
        DB::shouldReceive('transaction')->once()->andThrow(new LogicException('Reached persistence'));
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Reached persistence');

        app(ThreadController::class)->store(Request::create('/threads', 'POST', $payload));
    }

    #[TestWith([['content' => 'Question', 'poll_options' => ['Only one']]])]
    #[TestWith([['content' => 'Question', 'poll_options' => ['Yes', '']]])]
    #[TestWith([['content' => 'Question', 'poll_options' => ['Yes', null]]])]
    #[TestWith([['content' => 'Question', 'poll_options' => ['A', 'B', 'C', 'D', 'E']]])]
    #[TestWith([['poll_options' => []]])]
    public function test_invalid_polls_and_empty_posts_are_rejected(array $payload): void
    {
        DB::shouldReceive('transaction')->never();
        $this->expectException(ValidationException::class);

        app(ThreadController::class)->store(Request::create('/threads', 'POST', $payload));
    }
}
