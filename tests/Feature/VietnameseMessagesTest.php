<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use PDOException;
use Tests\TestCase;

class VietnameseMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_framework_validation_rules_have_vietnamese_translations(): void
    {
        $this->assertSame('vi', app()->getLocale());
        $english = Arr::dot(require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php'));
        $vietnamese = Arr::dot(require lang_path('vi/validation.php'));
        foreach ($english as $key => $message) {
            if ($key === 'attributes' || str_starts_with($key, 'custom.') || str_starts_with($key, 'attributes.')) {
                continue;
            }
            $this->assertArrayHasKey($key, $vietnamese);
            $this->assertDoesNotMatchRegularExpression('/^(The |Please |This )/', $vietnamese[$key]);
        }
    }

    public function test_registration_lists_password_confirmation_and_letter_errors_in_vietnamese(): void
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Độc giả', 'email' => 'reader@example.test',
            'password' => '12345678', 'password_confirmation' => '12345679',
        ])->assertRedirect(route('register'))->assertSessionHasErrors('password');
        $errorBag = $response->baseResponse->getSession()->get('errors');
        $errors = $errorBag->get('password');
        $this->assertContains('Mật khẩu xác nhận không khớp với mật khẩu đã nhập. Hãy nhập lại hai ô giống nhau.', $errors);
        $this->assertContains('Mật khẩu phải có ít nhất một chữ cái (a–z hoặc A–Z).', $errors);
        $this->view('components.flash', ['errors' => $errorBag])->assertSee('Vui lòng sửa các lỗi sau:')
            ->assertSee('Mật khẩu xác nhận không khớp')->assertSee('Mật khẩu phải có ít nhất một chữ cái')
            ->assertDontSee('The password field');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_json_validation_preserves_field_errors_and_translates_summary(): void
    {
        $response = $this->postJson(route('register.store'), [
            'name' => 'Độc giả', 'email' => 'invalid-email',
            'password' => 'abc', 'password_confirmation' => 'different',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->assertStringContainsString('không đúng định dạng', $response->json('errors.email.0'));
        $this->assertStringContainsString('và', $response->json('message'));
        $this->assertStringNotContainsString('more error', $response->json('message'));
        $this->assertStringNotContainsString('The password', $response->getContent());
        $this->assertStringContainsString('ít nhất 8 ký tự', implode(' ', $response->json('errors.password')));
        $this->assertStringContainsString('ít nhất một chữ số', implode(' ', $response->json('errors.password')));
    }

    public function test_profile_and_password_reset_use_the_same_vietnamese_rules(): void
    {
        $reader = User::factory()->create();
        $hash = $reader->password;
        $this->actingAs($reader)->putJson(route('account.password'), [
            'current_password' => 'wrong', 'password' => '12345678', 'password_confirmation' => '12345679',
        ])->assertUnprocessable()->assertJsonValidationErrors(['current_password', 'password'])
            ->assertJsonPath('errors.current_password.0', 'Mật khẩu hiện tại không đúng. Hãy nhập mật khẩu đang dùng cho website.');
        $this->postJson(route('password.update'), [
            'email' => $reader->email, 'token' => 'not-a-real-token',
            'password' => '12345678', 'password_confirmation' => '12345679',
        ])->assertUnprocessable()->assertJsonValidationErrors('password')
            ->assertJsonPath('errors.password.0', 'Mật khẩu xác nhận không khớp với mật khẩu đã nhập. Hãy nhập lại hai ô giống nhau.');
        $this->assertSame($hash, $reader->fresh()->password);
        $this->assertTrue(Hash::check('password', $reader->fresh()->password));
    }

    public function test_comment_and_admin_validation_identify_the_field_and_limit(): void
    {
        $reader = User::factory()->create();
        $post = Post::factory()->published()->create();
        $this->actingAs($reader)->postJson(route('comments.store', $post->id), ['body' => 'a'])
            ->assertUnprocessable()->assertJsonPath('errors.body.0', 'Nội dung bình luận phải có ít nhất 2 ký tự.');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->postJson(route('admin.posts.store'), [
            'title' => str_repeat('a', 201), 'content' => '<p>Nội dung bài viết</p>', 'status' => 'draft',
        ])->assertUnprocessable()->assertJsonPath('errors.title.0', 'Tiêu đề bài viết không được dài quá 200 ký tự.');
        $this->getJson(route('admin.index', ['days' => 8]))->assertUnprocessable()
            ->assertJsonPath('errors.days.0', 'Khoảng thống kê chỉ hỗ trợ 7, 30 hoặc 90 ngày.');
    }

    public function test_upload_errors_include_allowed_formats_and_exact_limits(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->postJson(route('admin.media.store'), [
            'file' => UploadedFile::fake()->image('large.jpg')->size(5121),
        ])->assertUnprocessable()->assertJsonPath('errors.file.0', 'Ảnh tải lên không được vượt quá 5 MB.');
        $invalidFile = $this->postJson(route('admin.media.store'), [
            'file' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->assertStringContainsString('JPEG, PNG hoặc WebP', implode(' ', $invalidFile->json('errors.file')));
        $this->patchJson(route('account.update'), [
            'name' => $admin->name, 'avatar' => UploadedFile::fake()->image('avatar.png')->size(2049),
        ])->assertUnprocessable()->assertJsonPath('errors.avatar.0', 'Ảnh đại diện không được vượt quá 2 MB.');
    }

    public function test_permissions_and_signed_link_errors_are_vietnamese(): void
    {
        $reader = User::factory()->unverified()->create();
        $this->actingAs($reader)->get(route('admin.index'))->assertForbidden()
            ->assertSee('Bạn không có quyền')->assertDontSee('Forbidden');
        $this->getJson(route('admin.index'))->assertForbidden()->assertJsonPath('message', 'Bạn không có quyền truy cập hoặc thực hiện thao tác này.');
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), [
            'id' => $reader->id, 'hash' => sha1($reader->email),
        ]);
        $this->get($url.'&tampered=1')->assertForbidden()->assertSee('Liên kết xác minh không hợp lệ hoặc đã hết hạn');
        $post = Post::factory()->published()->create();
        $this->postJson(route('comments.store', $post->id), ['body' => 'Bình luận thử'])
            ->assertForbidden()->assertJsonPath('message', 'Bạn cần xác minh email trước khi bình luận, thích hoặc lưu bài viết.');
    }

    public function test_throttling_is_vietnamese_and_retains_retry_after_headers(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson(route('login.store'), ['email' => 'throttle-vi@example.test', 'password' => 'wrong'])
                ->assertUnprocessable();
        }
        $response = $this->postJson(route('login.store'), ['email' => 'throttle-vi@example.test', 'password' => 'wrong'])
            ->assertStatus(429)->assertHeader('Retry-After')->assertHeader('X-RateLimit-Limit', '5');
        $this->assertStringContainsString('Vui lòng chờ', $response->json('message'));
        $this->assertStringContainsString('giây', $response->json('message'));
        $this->post(route('login.store'), ['email' => 'throttle-vi@example.test', 'password' => 'wrong'])
            ->assertStatus(429)->assertSee('Vui lòng chờ')->assertDontSee('Too Many Requests');
    }

    public function test_expired_session_is_vietnamese_without_disabling_csrf(): void
    {
        $this->app['env'] = 'production';
        $this->post(route('login.store'), ['email' => 'csrf@example.test', 'password' => 'password'])
            ->assertStatus(419)->assertSee('Phiên làm việc đã hết hạn')->assertDontSee('Page Expired');
        $this->postJson(route('login.store'), ['email' => 'csrf@example.test', 'password' => 'password'])
            ->assertStatus(419)->assertJsonPath('message', 'Phiên làm việc đã hết hạn hoặc mã bảo vệ không hợp lệ. Hãy tải lại trang, đăng nhập lại nếu cần rồi gửi lại.');
        $this->assertGuest();
    }

    public function test_json_not_found_and_server_errors_never_expose_internal_details(): void
    {
        $this->getJson('/route-khong-ton-tai')->assertNotFound()
            ->assertJsonPath('message', 'Không tìm thấy nội dung được yêu cầu. Nội dung có thể đã bị xóa hoặc chưa được công khai.');
        config(['app.debug' => true]);
        Route::get('/test-error-vi', function (): void {
            throw new \RuntimeException('private-password-and-stacktrace');
        });
        $this->get('/test-error-vi')->assertStatus(500)->assertSee('Máy chủ gặp lỗi khi xử lý yêu cầu')
            ->assertDontSee('private-password-and-stacktrace');
        $this->getJson('/test-error-vi')->assertStatus(500)
            ->assertJsonMissingPath('exception')->assertJsonMissingPath('trace')
            ->assertDontSee('private-password-and-stacktrace');
    }

    public function test_database_errors_are_specific_but_hide_credentials(): void
    {
        foreach ([2002 => 'Không kết nối được MySQL', 1049 => 'Database được cấu hình chưa tồn tại', 1045 => 'MySQL từ chối tài khoản kết nối'] as $code => $message) {
            Route::get('/test-database-error-'.$code, function () use ($code): void {
                $previous = new PDOException('private-database-password');
                $previous->errorInfo = ['HY000', $code, 'private-database-password'];
                throw new QueryException('mysql', 'select secret', [], $previous);
            });
            $this->get('/test-database-error-'.$code)->assertStatus(503)->assertSee($message)
                ->assertDontSee('private-database-password');
            $json = $this->getJson('/test-database-error-'.$code)->assertStatus(503)
                ->assertDontSee('private-database-password')->assertJsonMissingPath('trace');
            $this->assertStringContainsString($message, $json->json('message'));
        }
    }

    public function test_email_notifications_and_pagination_are_vietnamese_without_sending_mail(): void
    {
        $reader = User::factory()->unverified()->create();
        $verify = (new VerifyEmail)->toMail($reader);
        $this->assertSame('Xác minh địa chỉ email của bạn', $verify->subject);
        $this->assertSame('Xác minh email', $verify->actionText);
        $reset = (new ResetPassword('test-only-token'))->toMail($reader);
        $this->assertSame('Thiết lập hoặc đặt lại mật khẩu của bạn', $reset->subject);
        $this->assertSame('Thiết lập mật khẩu', $reset->actionText);
        $this->assertStringContainsString('60 phút', implode(' ', $reset->outroLines));
        $html = (string) $reset->render();
        $this->assertStringContainsString('Xin chào!', $html);
        $this->assertStringContainsString('Trân trọng,', $html);
        $this->assertStringNotContainsString('If you&#039;re having trouble', $html);
        $this->assertStringNotContainsString('All rights reserved', $html);
        Post::factory()->published()->count(10)->create();
        $this->get(route('posts.index'))->assertOk()->assertSee('Hiển thị')->assertSee('kết quả')->assertDontSee('Showing');
    }
}
