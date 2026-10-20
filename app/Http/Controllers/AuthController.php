<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
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
        $loginInput = trim($request->input('login', $request->input('email', '')));
        $password = $request->input('password', '');
        $remember = $request->boolean('remember');

        if (empty($loginInput) || empty($password)) {
            throw ValidationException::withMessages(['login' => 'Vui lòng nhập email/tên người dùng và mật khẩu.']);
        }

        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);
        $credentials = $isEmail
            ? ['email' => Str::lower($loginInput), 'password' => $password]
            : ['username' => Str::lower(ltrim($loginInput, '@')), 'password' => $password];

        if (! Auth::attempt($credentials, $remember)) {
            if ($isEmail || ! Auth::attempt(['name' => $loginInput, 'password' => $password], $remember)) {
                throw ValidationException::withMessages(['login' => 'Email/tên người dùng hoặc mật khẩu không chính xác.']);
            }
        }

        if (Auth::user()->is_locked) {
            Auth::logout();
            throw ValidationException::withMessages(['login' => 'Tài khoản đang bị khóa.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'nullable|string|min:3|max:30|alpha_dash|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $data['email'] = Str::lower(trim($data['email']));
        if (empty($data['username'])) {
            $baseUsername = Str::slug($data['name'], '_') ?: explode('@', $data['email'])[0];
            $username = $baseUsername;
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = $baseUsername.$counter++;
            }
            $data['username'] = $username;
        } else {
            $data['username'] = Str::lower($data['username']);
        }

        $user = User::create($data);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'Chào mừng bạn đến với Threads! Hãy tạo bài viết đầu tiên của bạn.');
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
            $user->forceFill(['password' => Hash::make($password), 'has_local_password' => true, 'remember_token' => Str::random(60)])->save();
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
