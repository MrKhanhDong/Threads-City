<?php

namespace App\Http\Controllers;

use App\Services\PushSubscriptions;
use App\Services\UserNotifications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PushSubscriptionController extends Controller
{
    public function store(Request $request, PushSubscriptions $subscriptions): JsonResponse
    {
        abort_unless($subscriptions->configured(), 503);
        $data = $request->validate(['endpoint' => 'required|url:https|max:2048', 'keys' => 'required|array', 'keys.p256dh' => 'required|string|max:100', 'keys.auth' => 'required|string|max:40', 'account_id' => 'sometimes|integer']);
        abort_if(isset($data['account_id']) && (int) $data['account_id'] !== $request->user()->id, 409);
        if (! $subscriptions->allowedEndpoint($data['endpoint']) || ! $subscriptions->validKey($data['keys']['p256dh'], 65) || ! $subscriptions->validKey($data['keys']['auth'], 16)) {
            throw ValidationException::withMessages(['endpoint' => 'Đăng ký Web Push không hợp lệ hoặc dịch vụ push chưa được hỗ trợ.']);
        }
        $hash = hash('sha256', $data['endpoint']);
        $token = DB::transaction(function () use ($request, $data, $hash): string {
            $existing = DB::table('push_subscriptions')->where('endpoint_hash', $hash)->lockForUpdate()->first();
            $token = $existing && (int) $existing->user_id === $request->user()->id ? $existing->browser_token : bin2hex(random_bytes(32));
            DB::table('push_subscriptions')->upsert([[
                'endpoint_hash' => $hash, 'user_id' => $request->user()->id, 'endpoint' => $data['endpoint'], 'public_key' => $data['keys']['p256dh'],
                'auth_token' => Crypt::encryptString($data['keys']['auth']), 'browser_token' => $token, 'session_id' => $request->session()->getId(), 'created_at' => now(), 'updated_at' => now(),
            ]], ['endpoint_hash'], ['user_id', 'endpoint', 'public_key', 'auth_token', 'browser_token', 'session_id', 'updated_at']);

            return $token;
        });
        $request->session()->put('push_browser_token', $token);

        return response()->json(['success' => true])->header('Cache-Control', 'private, no-store');
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => 'required|string|max:2048']);
        DB::table('push_subscriptions')->where('user_id', $request->user()->id)->where('endpoint_hash', hash('sha256', $data['endpoint']))->delete();
        $request->session()->forget('push_browser_token');

        return response()->json(['success' => true])->header('Cache-Control', 'private, no-store');
    }

    public function context(Request $request, UserNotifications $inbox): JsonResponse
    {
        $data = $request->validate(['notification' => 'required|uuid']);
        $token = $request->header('X-Push-Token', '');
        $allowed = strlen($token) === 64
            && DB::table('push_subscriptions')->where('user_id', $request->user()->id)->where('browser_token', $token)->exists()
            && $inbox->forUser($request->user())->whereKey($data['notification'])->whereNull('read_at')->exists();

        return response()->json(['allowed' => $allowed, 'url' => route('notifications.index')])->header('Cache-Control', 'private, no-store');
    }
}
