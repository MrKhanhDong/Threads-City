<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            if (($google->user['email_verified'] ?? false) !== true || ! filter_var($google->getEmail(), FILTER_VALIDATE_EMAIL) || ! $google->getId()) {
                throw new \RuntimeException;
            }
            $user = DB::transaction(function () use ($google): User {
                $email = Str::lower($google->getEmail());
                $existing = User::where('google_id', $google->getId())->lockForUpdate()->first();
                if (! $existing) {
                    $existing = User::where('email', $email)->lockForUpdate()->first();
                    if ($existing && ($existing->isAdmin() || $existing->is_locked || ($existing->google_id && $existing->google_id !== $google->getId()))) {
                        throw new \RuntimeException;
                    }
                    if (! $existing || ! $existing->hasVerifiedEmail()) {
                        $existing ??= new User(['email' => $email]);
                        $existing->forceFill([
                            'name' => Str::limit(trim($google->getName() ?? '') ?: 'Độc giả', 100, ''),
                            'username' => $existing->username ?: $this->availableUsername($google->getName() ?? '', $email),
                            'password' => Str::random(64),
                            'remember_token' => Str::random(60),
                            'has_local_password' => false,
                            'google_profile_pending' => true,
                        ]);
                        DB::table('password_reset_tokens')->where('email', $email)->delete();
                        if ($existing->exists) {
                            DB::table('sessions')->where('user_id', $existing->id)->delete();
                        }
                    }
                    $existing->forceFill(['google_id' => $google->getId(), 'email_verified_at' => now()])->save();
                }
                if (! $existing->username) {
                    $existing->username = $this->availableUsername($google->getName() ?? '', $email);
                    $existing->save();
                }
                if ($existing->is_locked || $existing->isAdmin()) {
                    throw new \RuntimeException;
                }

                return $existing;
            });
            Auth::login($user);
            $request->session()->regenerate();

            if ($user->google_profile_pending) {
                return redirect()->route('google.profile');
            }

            return redirect()->route('home')->with('success', 'Đã đăng nhập bằng Google.');
        } catch (\Throwable $error) {
            $cause = $error->getPrevious();
            $transportError = $cause ?? $error;
            $response = method_exists($transportError, 'getResponse') ? $transportError->getResponse() : null;
            $responseBody = $response ? json_decode((string) $response->getBody(), true) : null;
            Log::error('Google OAuth callback failed.', [
                'exception' => $error::class,
                'cause' => $cause ? $cause::class : null,
                'code' => $error->getCode(),
                'provider_error' => $request->query('error'),
                'has_auth_code' => $request->filled('code'),
                'provider_http_status' => $response?->getStatusCode(),
                'provider_response_error' => is_array($responseBody) ? ($responseBody['error'] ?? null) : null,
            ]);

            return redirect()->route('login')->withErrors(['google' => 'Không thể đăng nhập bằng Google lúc này. Vui lòng thử lại. Bạn không cần đăng ký tài khoản trước.']);
        }
    }

    public function profile(Request $request): View|RedirectResponse
    {
        if (! $request->user()->google_profile_pending) {
            return redirect()->route('home');
        }

        return view('auth.google-profile');
    }

    public function completeProfile(Request $request): RedirectResponse
    {
        if (! $request->user()->google_profile_pending) {
            return redirect()->route('home');
        }

        $data = $request->validate(['name' => 'required|string|max:100']);
        $request->user()->forceFill(['name' => $data['name'], 'google_profile_pending' => false])->save();

        return redirect()->route('home')->with('success', 'Chào mừng bạn! Bạn có thể thêm mật khẩu trong Hồ sơ của tôi khi cần.');
    }

    private function availableUsername(string $name, string $email): string
    {
        $base = Str::slug($name, '_') ?: Str::slug(Str::before($email, '@'), '_') ?: 'reader';
        $base = Str::limit($base, 40, '');
        $username = $base;
        $suffix = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base.$suffix++;
        }

        return $username;
    }
}
