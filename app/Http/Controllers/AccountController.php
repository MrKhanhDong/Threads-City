<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AccountController extends Controller
{
    public function edit(): View
    {
        return view('account.edit');
    }

    public function update(AccountRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $oldAvatar = $user->avatar_path;
        $newAvatar = $request->hasFile('avatar') ? $request->file('avatar')->store('avatars', 'local') : null;
        try {
            $user->fill(collect($data)->only(['name', 'username', 'bio'])->all());
            if ($newAvatar) {
                $user->avatar_path = $newAvatar;
            }
            $user->save();
        } catch (\Throwable $error) {
            if ($newAvatar) {
                Storage::disk('local')->delete($newAvatar);
            }
            throw $error;
        }
        if ($newAvatar && $oldAvatar && str_starts_with($oldAvatar, 'avatars/')) {
            Storage::disk('local')->delete($oldAvatar);
        }

        return back()->with('success', 'Đã cập nhật hồ sơ.');
    }

    public function password(Request $request): RedirectResponse
    {
        $needsCurrentPassword = ! $request->user()->google_id || $request->user()->has_local_password;
        $data = $request->validate(['current_password' => $needsCurrentPassword ? 'required|current_password' : 'exclude', 'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()]]);
        $request->user()->forceFill(['password' => $data['password'], 'has_local_password' => true, 'remember_token' => Str::random(60)])->save();
        $request->session()->put('password_hash_web', $request->user()->password);
        $request->session()->regenerate();

        return back()->with('success', 'Đã đổi mật khẩu. Các phiên cũ sẽ bị thu hồi.');
    }

    public function avatar(User $user): BinaryFileResponse
    {
        abort_if(auth()->user()?->isBlockedWith($user), 404);

        return $this->avatarResponse($user);
    }

    public function managedAvatar(Request $request, User $user): BinaryFileResponse
    {
        abort_unless($request->user()->blockedUsers()->whereKey($user->id)->exists()
            || $request->user()->mutedUsers()->whereKey($user->id)->exists(), 404);

        return $this->avatarResponse($user);
    }

    private function avatarResponse(User $user): BinaryFileResponse
    {
        abort_unless($user->avatar_path && preg_match('~^avatars/[A-Za-z0-9]+\.(jpg|jpeg|png|webp)$~', $user->avatar_path), 404);
        abort_unless(Storage::disk('local')->exists($user->avatar_path), 404);

        return response()->file(Storage::disk('local')->path($user->avatar_path), ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store', 'Vary' => 'Cookie']);
    }
}
