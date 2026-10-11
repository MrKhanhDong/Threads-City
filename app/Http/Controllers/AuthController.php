<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function loginForm() { return view('auth.login'); }
    public function registerForm() { return view('auth.register'); }
    public function forgotForm() { return view('auth.forgot'); }
    public function resetForm(Request $request, string $token) { return view('auth.reset', ['token' => $token, 'email' => is_string($request->query('email')) ? $request->query('email') : '']); }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255', 'password' => 'required|string|max:255', 'remember' => 'sometimes|boolean']);
        $email = Str::lower($data['email']);
        $key = 'login:'.hash('sha256', $email.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) throw ValidationException::withMessages(['email' => 'Bạn thử quá nhiều lần. Vui lòng đợi một phút.']);
        if (!Auth::attempt(['email' => $email, 'password' => $data['password'], 'is_locked' => false], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Email hoặc mật khẩu không đúng, hoặc tài khoản đã bị khóa.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        return redirect()->intended($request->user()->isAdmin() ? route('admin.index') : route('home'));
    }

    public function register(Request $request)
    {
        if (is_string($request->input('email'))) $request->merge(['email' => Str::lower($request->input('email'))]);
        $data = $request->validate(['name' => 'required|string|max:80', 'email' => 'required|email|max:255|unique:users,email', 'password' => ['required', 'string', 'max:255', 'confirmed', PasswordRule::min(10)->letters()->numbers()]]);
        $user = User::create([...$data, 'role' => 'reader']);
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('home')->with('success', 'Đăng ký thành công. Chào mừng bạn đến với blog!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }

    public function forgot(Request $request)
    {
        $request->validate(['email' => 'required|email|max:255']);
        Password::sendResetLink(['email' => Str::lower($request->input('email'))]);
        return back()->with('success', 'Nếu email tồn tại, hướng dẫn đặt lại mật khẩu sẽ được gửi đến bạn.');
    }

    public function reset(Request $request)
    {
        if (is_string($request->input('email'))) $request->merge(['email' => Str::lower($request->input('email'))]);
        $data = $request->validate(['token' => 'required|string|max:255', 'email' => 'required|email|max:255', 'password' => ['required', 'string', 'max:255', 'confirmed', PasswordRule::min(10)->letters()->numbers()]]);
        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) throw ValidationException::withMessages(['email' => 'Liên kết không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu liên kết mới.']);
        return redirect()->route('login')->with('success', 'Đã đặt lại mật khẩu. Hãy đăng nhập bằng mật khẩu mới.');
    }
}
