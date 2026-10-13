<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return redirect()->route('login')->withErrors(['google' => 'Đăng nhập Google chưa được cấu hình. Bạn vẫn có thể đăng nhập bằng email.']);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            if ($request->has('error') || ! config('services.google.client_id')) {
                throw new \RuntimeException;
            }
            $google = Socialite::driver('google')->user();
            if (! ($google->user['email_verified'] ?? false) || ! filter_var($google->getEmail(), FILTER_VALIDATE_EMAIL) || ! $google->getId()) {
                throw new \RuntimeException;
            }
            $user = DB::transaction(function () use ($google): User {
                $email = Str::lower($google->getEmail());
                $existing = User::where('google_id', $google->getId())->lockForUpdate()->first();
                if (! $existing) {
                    $existing = User::where('email', $email)->lockForUpdate()->first();
                    if ($existing && ($existing->isAdmin() || ! $existing->hasVerifiedEmail() || ($existing->google_id && $existing->google_id !== $google->getId()))) {
                        throw new \RuntimeException;
                    }
                    $existing ??= User::create(['name' => Str::limit($google->getName() ?: 'Độc giả', 100, ''), 'email' => $email, 'password' => Str::random(64)]);
                    $existing->forceFill(['google_id' => $google->getId(), 'email_verified_at' => now()])->save();
                }
                if ($existing->is_locked || $existing->isAdmin()) {
                    throw new \RuntimeException;
                }

                return $existing;
            });
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('home')->with('success', 'Đã đăng nhập bằng Google.');
        } catch (\Throwable) {
            return redirect()->route('login')->withErrors(['google' => 'Không thể xác nhận đăng nhập Google. Thử lại hoặc đăng nhập bằng email để xác minh tài khoản trước.']);
        }
    }
}
