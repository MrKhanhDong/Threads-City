<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(): View
    {
        return view('auth.login');
    }

    public function register(): View
    {
        return view('auth.register');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => 'required|email|max:255', 'password' => 'required|string', 'remember' => 'sometimes|boolean']);
        if (! Auth::attempt(['email' => Str::lower(trim($data['email'])), 'password' => $data['password']], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Email hoặc mật khẩu không đúng.']);
        }
        if (Auth::user()->is_locked) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Tài khoản đang bị khóa.']);
        }
        $request->session()->regenerate();

        return redirect()->intended(Auth::user()->isAdmin() ? route('admin.index') : route('home'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:255', 'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()]]);
        $data['email'] = Str::lower(trim($data['email']));
        if (User::where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages(['email' => 'Email đã được đăng ký.']);
        }
        $user = User::create($data);
        if (! in_array(config('mail.default'), ['array', 'log'], true)) {
            try {
                event(new Registered($user));
            } catch (\Throwable) {
                session()->flash('mail_error', 'Tài khoản đã tạo nhưng chưa gửi được thư xác minh. Kiểm tra SMTP hoặc thử gửi lại.');
            }
        }
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice')->with('success', 'Đã tạo tài khoản độc giả. Hãy xác minh email trước khi bình luận.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Đã đăng xuất.');
    }

    public function forgot(): View
    {
        return view('auth.forgot');
    }

    public function sendReset(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            $email = $user->email;
        } else {
            $data = $request->validate(['email' => 'required|email|max:255']);
            $email = Str::lower(trim($data['email']));
        }
        if (in_array(config('mail.default'), ['array', 'log'], true)) {
            return back()->withErrors(['email' => 'Chưa cấu hình dịch vụ SMTP; chưa thể gửi liên kết đặt lại mật khẩu.']);
        }
        try {
            $status = Password::sendResetLink(['email' => $email]);
        } catch (\Throwable) {
            return back()->withErrors(['email' => 'Dịch vụ gửi thư đang không khả dụng. Vui lòng thử lại sau.']);
        }

        if ($user) {
            if ($status === Password::ResetThrottled) {
                return back()->withErrors(['email' => 'Bạn vừa yêu cầu liên kết. Vui lòng chờ một phút trước khi gửi lại.']);
            }
            if ($status !== Password::ResetLinkSent) {
                return back()->withErrors(['email' => 'Chưa gửi được liên kết thiết lập mật khẩu. Vui lòng thử lại sau.']);
            }

            return back()->with('success', 'Đã gửi liên kết thiết lập hoặc đặt lại mật khẩu tới email của bạn. Mở thư để đặt mật khẩu riêng cho website; bạn vẫn có thể đăng nhập bằng Google.');
        }

        return back()->with('success', 'Nếu email đã được đăng ký, yêu cầu đặt lại mật khẩu đã được tiếp nhận. Email thực tế cần dịch vụ gửi thư được cấu hình.');
    }

    public function reset(Request $request, string $token): View
    {
        return view('auth.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => 'required|string', 'email' => 'required|email', 'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()]]);
        $status = Password::reset($data, function (User $user, string $password): void {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => 'Liên kết không hợp lệ hoặc đã hết hạn. Hãy yêu cầu liên kết mới.']);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Đã đổi mật khẩu. Hãy đăng nhập lại.');
    }
}
