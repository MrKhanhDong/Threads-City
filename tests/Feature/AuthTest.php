<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_registration_hashes_password_and_cannot_grant_admin_role(): void
    {
        $this->post(route('register.store'), ['name'=>'New Reader','email'=>'NEW@BLOG.LOCAL','password'=>'Password12345','password_confirmation'=>'Password12345','role'=>'admin','is_locked'=>true])->assertRedirect(route('home'));
        $user = User::where('email','new@blog.local')->firstOrFail();
        $this->assertSame('reader',$user->role);
        $this->assertFalse($user->is_locked);
        $this->assertTrue(Hash::check('Password12345',$user->password));
        $this->assertAuthenticatedAs($user);
    }
    public function test_registration_validates_password_duplicate_and_malformed_email(): void
    {
        $this->postJson(route('register.store'), ['name'=>'Test','email'=>'READER@BLOG.LOCAL','password'=>'short','password_confirmation'=>'no'])->assertUnprocessable()->assertJsonValidationErrors(['email','password']);
        $this->postJson(route('register.store'), ['name'=>'Test','email'=>['x'],'password'=>'Password12345','password_confirmation'=>'Password12345'])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest();
    }
    public function test_admin_and_reader_can_login_and_logout(): void
    {
        $this->post(route('login.store'), ['email'=>'ADMIN@BLOG.LOCAL','password'=>'Admin@12345'])->assertRedirect(route('admin.index'));
        $this->assertAuthenticatedAs($this->admin());
        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
        $this->post(route('login.store'), ['email'=>'reader@blog.local','password'=>'Reader@12345'])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($this->reader());
    }
    public function test_invalid_and_locked_login_do_not_authenticate(): void
    {
        $this->postJson(route('login.store'), ['email'=>'reader@blog.local','password'=>'wrong'])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->reader()->update(['is_locked'=>true]);
        $this->postJson(route('login.store'), ['email'=>'reader@blog.local','password'=>'Reader@12345'])->assertUnprocessable();
        $this->assertGuest();
    }
    public function test_repeated_wrong_password_is_rate_limited_even_if_password_becomes_correct(): void
    {
        for ($i=0; $i<5; $i++) $this->postJson(route('login.store'), ['email'=>'reader@blog.local','password'=>'wrong'])->assertUnprocessable();
        $this->postJson(route('login.store'), ['email'=>'reader@blog.local','password'=>'Reader@12345'])->assertUnprocessable()->assertJsonPath('errors.email.0', 'Bạn thử quá nhiều lần. Vui lòng đợi một phút.');
        $this->assertGuest();
    }
    public function test_password_reset_link_is_sent_without_disclosing_account_existence(): void
    {
        Notification::fake();
        $this->from(route('password.request'))->post(route('password.email'), ['email'=>'reader@blog.local'])->assertRedirect(route('password.request'))->assertSessionHas('success');
        Notification::assertSentTo($this->reader(), ResetPassword::class);
        $this->post(route('password.email'), ['email'=>'unknown@blog.local'])->assertSessionHas('success');
        $this->assertDatabaseHas('password_reset_tokens', ['email'=>'reader@blog.local']);
    }
    public function test_reset_password_token_is_one_time_and_old_password_no_longer_works(): void
    {
        $reader=$this->reader();
        $token=Password::createToken($reader);
        $data=['token'=>$token,'email'=>$reader->email,'password'=>'NewPassword12345','password_confirmation'=>'NewPassword12345'];
        $this->post(route('password.update'), $data)->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('NewPassword12345',$reader->fresh()->password));
        $this->assertFalse(Hash::check('Reader@12345',$reader->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens',['email'=>$reader->email]);
        $this->postJson(route('password.update'),$data)->assertUnprocessable()->assertJsonValidationErrors('email');
    }
    public function test_account_update_requires_current_password_when_changing_password(): void
    {
        $reader=$this->reader();
        $this->actingAs($reader)->get(route('account.edit'))->assertOk();
        $data=['name'=>'Updated Reader','email'=>$reader->email,'password'=>'ChangedPassword12345','password_confirmation'=>'ChangedPassword12345'];
        $this->patchJson(route('account.update'),$data)->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->patch(route('account.update'),[...$data,'current_password'=>'Reader@12345'])->assertRedirect();
        $this->assertSame('Updated Reader',$reader->fresh()->name);
        $this->assertTrue(Hash::check('ChangedPassword12345',$reader->fresh()->password));
    }
    public function test_account_update_does_not_change_role_and_rejects_duplicate_email(): void
    {
        $reader=$this->reader();
        $this->actingAs($reader)->patch(route('account.update'),['name'=>'Reader Changed','email'=>'CHANGED@BLOG.LOCAL','role'=>'admin'])->assertRedirect();
        $this->assertSame('reader',$reader->fresh()->role);
        $this->assertSame('changed@blog.local',$reader->fresh()->email);
        $this->patchJson(route('account.update'),['name'=>'Reader','email'=>'ADMIN@BLOG.LOCAL'])->assertUnprocessable()->assertJsonValidationErrors('email');
    }
}
