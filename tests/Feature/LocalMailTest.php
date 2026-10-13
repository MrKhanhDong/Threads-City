<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('local-mail')]
class LocalMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('RUN_LOCAL_MAIL_TESTS') !== '1') {
            $this->markTestSkipped('Opt-in: RUN_LOCAL_MAIL_TESTS=1, Mailpit localhost SMTP 11025 / API 18025.');
        }
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 11025,
            'mail.mailers.smtp.scheme' => 'smtp',
            'mail.mailers.smtp.url' => null,
            'mail.mailers.smtp.username' => null,
            'mail.mailers.smtp.password' => null,
            'mail.mailers.smtp.timeout' => 3,
            'mail.from.address' => 'moc-journal@example.test',
        ]);
    }

    public function test_registration_sends_real_local_smtp_and_signed_link_unlocks_comments(): void
    {
        $email = 'mail-test-'.Str::uuid().'@example.test';
        $this->post(route('register.store'), ['name' => 'Độc giả test SMTP local', 'email' => $email,
            'password' => 'LocalMail12345', 'password_confirmation' => 'LocalMail12345'])
            ->assertRedirect(route('verification.notice'))->assertSessionMissing('mail_error');
        $reader = User::where('email', $email)->sole();
        $this->assertFalse($reader->hasVerifiedEmail());
        $url = $this->capturedLink($email, '/email/verify/');
        $this->assertContains(parse_url($url, PHP_URL_HOST), ['localhost', 'Doan.test', 'doan.test']);
        $this->get($url)->assertRedirect(route('home'));
        $this->assertTrue($reader->fresh()->hasVerifiedEmail());
        $post = Post::factory()->published()->create();
        $this->post(route('comments.store', $post->id), ['body' => 'Đăng ký, xác minh qua thư local và bình luận thành công'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('comments', ['user_id' => $reader->id, 'status' => 'approved']);
    }

    public function test_password_reset_link_from_real_local_smtp_works_once(): void
    {
        $email = 'mail-test-'.Str::uuid().'@example.test';
        $reader = User::factory()->create(['email' => $email]);
        $this->post(route('password.email'), ['email' => $email])->assertSessionHasNoErrors();
        $url = $this->capturedLink($email, '/dat-lai-mat-khau/');
        $this->get($url)->assertOk();
        $token = basename(parse_url($url, PHP_URL_PATH));
        $data = ['email' => $email, 'token' => $token, 'password' => 'NewLocalMail12345', 'password_confirmation' => 'NewLocalMail12345'];
        $this->post(route('password.update'), $data)->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('NewLocalMail12345', $reader->fresh()->password));
        $this->post(route('password.update'), $data)->assertSessionHasErrors('email');
    }

    public function test_google_reader_sets_password_from_real_local_smtp_without_logging_out_first(): void
    {
        $email = 'mail-test-'.Str::uuid().'@example.test';
        $reader = User::factory()->create([
            'email' => $email, 'google_id' => 'google-local-mail-'.Str::uuid(),
            'password' => Str::random(64),
        ]);
        $googleId = $reader->google_id;
        $this->actingAs($reader)->from(route('account.edit'))->post(route('password.email'))
            ->assertRedirect(route('account.edit'))->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($reader);
        $url = $this->capturedLink($email, '/dat-lai-mat-khau/');
        $this->get($url)->assertOk();
        $token = basename(parse_url($url, PHP_URL_PATH));
        $data = ['email' => $email, 'token' => $token,
            'password' => 'LocalGooglePassword123', 'password_confirmation' => 'LocalGooglePassword123'];
        $this->post(route('password.update'), $data)->assertRedirect(route('login'))->assertSessionHasNoErrors();
        $this->assertGuest();
        $this->assertTrue(Hash::check('LocalGooglePassword123', $reader->fresh()->password));
        $this->assertSame($googleId, $reader->fresh()->google_id);
        $this->post(route('login.store'), ['email' => $email, 'password' => 'LocalGooglePassword123'])
            ->assertRedirect(route('home'))->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($reader);
    }

    private function capturedLink(string $email, string $path): string
    {
        $search = Http::timeout(3)->get('http://127.0.0.1:18025/api/v1/search', ['query' => 'to:'.$email])->throw()->json();
        foreach ($search['messages'] ?? [] as $summary) {
            $message = Http::timeout(3)->get('http://127.0.0.1:18025/api/v1/message/'.$summary['ID'])->throw()->json();
            $previous = libxml_use_internal_errors(true);
            $document = new DOMDocument;
            $document->loadHTML($message['HTML'] ?? '');
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            foreach ($document->getElementsByTagName('a') as $anchor) {
                $url = $anchor->getAttribute('href');
                if (str_contains((string) parse_url($url, PHP_URL_PATH), $path)) {
                    return $url;
                }
            }
        }
        $this->fail('Mailpit did not receive the expected email link for this test recipient.');
    }
}
