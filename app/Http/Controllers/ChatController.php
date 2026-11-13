<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\DirectMessages;
use App\Services\ImageStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ChatController extends Controller
{
    public function __construct(private DirectMessages $chat, private ImageStorage $images) {}

    public function index(Request $request, ?ChatConversation $conversation = null): View|JsonResponse
    {
        $partner = $conversation ? $this->chat->authorize($conversation, $request->user()) : null;
        $filters = $request->validate(['folder' => 'nullable|in:inbox,requests']);
        $canSend = $partner ? $this->chat->canSend($request->user(), $partner) : false;
        $mutual = $partner ? $this->chat->mutual($request->user(), $partner) : false;
        $folder = $partner ? ($canSend ? 'inbox' : 'requests') : ($filters['folder'] ?? 'inbox');
        $conversations = $this->chat->inbox($request->user(), $folder)->paginate(30)->withQueryString();
        $requestCount = $this->chat->requestCount($request->user());
        $messages = $conversation ? $conversation->messages()->with(['sender', 'replyTo'])->latest('id')->take(40)->get()->reverse()->values() : collect();
        $stickers = DirectMessages::STICKERS;

        if ($request->expectsJson()) {
            return response()->json(['html' => view('chat.conversations', compact('conversations', 'conversation', 'folder'))->render(), 'unread_count' => $this->chat->unreadCount($request->user()), 'request_count' => $requestCount])->header('Cache-Control', 'private, no-store');
        }

        return view('chat.index', compact('conversations', 'conversation', 'partner', 'messages', 'stickers', 'canSend', 'mutual', 'folder', 'requestCount'));
    }

    public function people(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => 'nullable|string|max:80']);
        $term = addcslashes(trim($data['q'] ?? ''), '\\%_');
        $people = User::discoverableTo($request->user())->where('is_locked', false)->whereKeyNot($request->user()->id)
            ->whereIn('id', $request->user()->following()->select('users.id'))
            ->when($term !== '', fn (Builder $users): Builder => $users->where(fn (Builder $names): Builder => $names->where('name', 'like', '%'.$term.'%')->orWhere('username', 'like', '%'.$term.'%')))
            ->orderBy('name')->limit(15)->get();

        return response()->json(['html' => view('chat.people', compact('people'))->render()])->header('Cache-Control', 'private, no-store');
    }

    public function unread(Request $request): JsonResponse
    {
        return response()->json(['unread_count' => $this->chat->unreadCount($request->user()), 'request_count' => $this->chat->requestCount($request->user())])->header('Cache-Control', 'private, no-store');
    }

    public function start(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['recipient_id' => 'required|integer|exists:users,id']);
        $recipient = User::findOrFail($data['recipient_id']);
        abort_if($recipient->id === $request->user()->id, 422, 'Hãy chọn một người khác để nhắn tin.');
        $conversation = DB::transaction(function () use ($request, $recipient): ChatConversation {
            $this->chat->lockUsers($request->user(), $recipient);
            $this->chat->authorizeSend($request->user(), $recipient);

            return ChatConversation::firstOrCreate(['first_user_id' => min($request->user()->id, $recipient->id), 'second_user_id' => max($request->user()->id, $recipient->id)]);
        });

        return $request->expectsJson() ? response()->json(['url' => route('chat.show', $conversation)]) : redirect()->route('chat.show', $conversation);
    }

    public function messages(Request $request, ChatConversation $conversation): JsonResponse
    {
        $partner = $this->chat->authorize($conversation, $request->user());
        $data = $request->validate(['after' => 'nullable|integer|min:0', 'before' => 'nullable|integer|min:1', 'refresh' => 'nullable|array|max:80', 'refresh.*' => 'integer|min:1', 'q' => 'nullable|string|max:100']);
        $incremental = array_key_exists('after', $data);
        $query = $conversation->messages()->with(['sender', 'replyTo']);
        $searching = isset($data['q']) && trim($data['q']) !== '';
        if ($searching) {
            $query->whereNull('recalled_at')->where('body', 'like', '%'.addcslashes($data['q'], '\\%_').'%');
        }
        $query->when($incremental, fn (Builder $messages): Builder => $messages->where('id', '>', $data['after']))
            ->when(! empty($data['before']), fn (Builder $messages): Builder => $messages->where('id', '<', $data['before']));
        $messages = ($incremental ? $query->oldest('id') : $query->latest('id'))->take(40)->get()->sortBy('id')->values();
        $cursor = $messages->last()?->id ?? (int) ($data['after'] ?? 0);
        $hasNewer = $incremental && $conversation->messages()->where('id', '>', $cursor)->exists();
        $hasOlder = $messages->isNotEmpty() && $conversation->messages()->where('id', '<', $messages->first()->id)->exists();
        if (! $searching && empty($data['before']) && ! empty($data['refresh'])) {
            $messages = $messages->merge($conversation->messages()->with(['sender', 'replyTo'])->whereKey($data['refresh'])->get())->unique('id')->sortBy('id')->values();
        }
        $otherSide = $conversation->side($request->user()) === 'first' ? 'second' : 'first';
        $canSend = $this->chat->canSend($request->user(), $partner);
        $mutual = $this->chat->mutual($request->user(), $partner);

        return response()->json([
            'html' => view('chat.messages', compact('messages', 'conversation', 'canSend', 'mutual'))->render(),
            'cursor' => $cursor, 'has_older' => $hasOlder, 'has_newer' => $hasNewer,
            'can_send' => $this->chat->canSend($request->user(), $partner),
            'mutual' => $this->chat->mutual($request->user(), $partner),
            'typing' => $this->chat->mutual($request->user(), $partner) && ($conversation->{$otherSide.'_typing_at'}?->gt(now()->subSeconds(6)) ?? false),
            'read_id' => (int) $conversation->{$otherSide.'_read_id'},
        ])->header('Cache-Control', 'private, no-store');
    }

    public function send(Request $request, ChatConversation $conversation): JsonResponse|RedirectResponse
    {
        $partner = $this->chat->authorize($conversation, $request->user());
        $this->chat->authorizeSend($request->user(), $partner);
        $data = $request->validate([
            'client_token' => 'required|uuid', 'body' => 'nullable|string|max:4000',
            'kind' => ['required', Rule::in(['text', 'image', 'voice', 'sticker'])],
            'image' => 'required_if:kind,image|prohibited_unless:kind,image|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'voice' => 'required_if:kind,voice|prohibited_unless:kind,voice|file|mimetypes:audio/webm,video/webm,audio/ogg,audio/mp4,audio/x-m4a,audio/wav,audio/x-wav|max:5120',
            'duration_ms' => 'required_if:kind,voice|nullable|integer|min:1|max:120000',
            'sticker' => ['required_if:kind,sticker', 'prohibited_unless:kind,sticker', Rule::in(array_keys(DirectMessages::STICKERS))],
            'reply_to_id' => ['nullable', 'integer', Rule::exists('chat_messages', 'id')->where('chat_conversation_id', $conversation->id)],
        ]);
        $body = trim($data['body'] ?? '');
        if ($data['kind'] === 'text' && $body === '') {
            throw ValidationException::withMessages(['body' => 'Nhập tin nhắn trước khi gửi.']);
        }
        $existing = $conversation->messages()->where('sender_id', $request->user()->id)->where('client_token', $data['client_token'])->first();
        if ($existing) {
            return $this->messageResponse($existing, $conversation);
        }
        $file = $request->file('image') ?? $request->file('voice');
        $path = $file ? $this->images->store($file, 'media') : null;
        try {
            $message = DB::transaction(function () use ($request, $conversation, $partner, $data, $body, $file, $path): ChatMessage {
                $this->chat->lockUsers($request->user(), $partner);
                $this->chat->authorizeSend($request->user(), $partner);
                $locked = ChatConversation::lockForUpdate()->findOrFail($conversation->id);
                $message = $locked->messages()->firstOrCreate(['sender_id' => $request->user()->id, 'client_token' => $data['client_token']], [
                    'kind' => $data['kind'], 'body' => $body === '' ? null : $body, 'attachment_path' => $path, 'attachment_mime' => $file?->getMimeType(),
                    'duration_ms' => $data['kind'] === 'voice' ? $data['duration_ms'] : null,
                    'sticker' => $data['sticker'] ?? null, 'reply_to_id' => $data['reply_to_id'] ?? null,
                ]);
                if ($message->wasRecentlyCreated) {
                    $locked->last_message_at = now();
                    $locked->{$locked->side($request->user()).'_typing_at'} = null;
                    $locked->save();
                }

                return $message;
            });
        } catch (\Throwable $error) {
            if ($path) {
                $this->images->delete($path);
            }
            throw $error;
        }
        if ($path && $message->attachment_path !== $path) {
            $this->images->delete($path);
        }

        return $request->expectsJson() ? $this->messageResponse($message, $conversation) : redirect()->route('chat.show', $conversation);
    }

    public function update(Request $request, ChatConversation $conversation, ChatMessage $message): JsonResponse
    {
        $this->chat->authorize($conversation, $request->user());
        abort_unless($message->chat_conversation_id === $conversation->id && $message->sender_id === $request->user()->id, 404);
        $data = $request->validate(['body' => 'required|string|max:4000']);
        $body = trim($data['body']);
        if ($body === '') {
            throw ValidationException::withMessages(['body' => 'Tin nhắn không được để trống.']);
        }
        DB::transaction(function () use ($conversation, $message, $body): void {
            ChatConversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            $locked = ChatMessage::lockForUpdate()->findOrFail($message->id);
            abort_if($locked->recalled_at || $locked->kind !== 'text', 422);
            $locked->body = $body;
            $locked->edited_at = now();
            $locked->save();
        });

        return $this->messageResponse($message->fresh(), $conversation);
    }

    public function recall(Request $request, ChatConversation $conversation, ChatMessage $message): JsonResponse
    {
        $this->chat->authorize($conversation, $request->user());
        abort_unless($message->chat_conversation_id === $conversation->id && $message->sender_id === $request->user()->id, 404);
        $path = DB::transaction(function () use ($conversation, $message): ?string {
            ChatConversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            $locked = ChatMessage::lockForUpdate()->findOrFail($message->id);
            $path = $locked->attachment_path;
            $locked->body = null;
            $locked->attachment_path = null;
            $locked->attachment_mime = null;
            $locked->reactions = [];
            $locked->sticker = null;
            $locked->recalled_at ??= now();
            $locked->save();

            return $path;
        });
        if ($path) {
            rescue(fn () => $this->images->delete($path));
        }

        return $this->messageResponse($message->fresh(), $conversation);
    }

    public function react(Request $request, ChatConversation $conversation, ChatMessage $message): JsonResponse
    {
        $partner = $this->chat->authorize($conversation, $request->user());
        $this->chat->authorizeSend($request->user(), $partner);
        abort_unless($message->chat_conversation_id === $conversation->id, 404);
        $data = $request->validate(['reaction' => ['nullable', Rule::in(DirectMessages::REACTIONS)]]);
        DB::transaction(function () use ($request, $conversation, $message, $data): void {
            ChatConversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            $locked = ChatMessage::lockForUpdate()->findOrFail($message->id);
            abort_if($locked->recalled_at, 422);
            $reactions = $locked->reactions ?? [];
            unset($reactions[(string) $request->user()->id]);
            if ($data['reaction'] ?? null) {
                $reactions[(string) $request->user()->id] = $data['reaction'];
            }
            $locked->reactions = $reactions;
            $locked->save();
        });

        return $this->messageResponse($message->fresh(), $conversation);
    }

    public function read(Request $request, ChatConversation $conversation): JsonResponse
    {
        $partner = $this->chat->authorize($conversation, $request->user());
        if (! $this->chat->mutual($request->user(), $partner)) {
            return response()->json(['unread_count' => $this->chat->unreadCount($request->user()), 'request_count' => $this->chat->requestCount($request->user())]);
        }
        $data = $request->validate(['message_id' => 'required|integer|min:1']);
        $conversation->messages()->whereKey($data['message_id'])->firstOrFail();
        $column = $conversation->side($request->user()).'_read_id';
        ChatConversation::whereKey($conversation->id)->where($column, '<', $data['message_id'])->update([$column => $data['message_id']]);

        return response()->json(['unread_count' => $this->chat->unreadCount($request->user()), 'request_count' => $this->chat->requestCount($request->user())]);
    }

    public function typing(Request $request, ChatConversation $conversation): JsonResponse
    {
        $partner = $this->chat->authorize($conversation, $request->user());
        abort_unless($this->chat->mutual($request->user(), $partner), 403);
        $data = $request->validate(['typing' => 'required|boolean']);
        ChatConversation::whereKey($conversation->id)->update([$conversation->side($request->user()).'_typing_at' => $data['typing'] ? now() : null]);

        return response()->json(['ok' => true]);
    }

    public function attachment(Request $request, ChatConversation $conversation, ChatMessage $message): BinaryFileResponse|Response
    {
        $this->chat->authorize($conversation, $request->user());
        abort_unless($message->chat_conversation_id === $conversation->id && ! $message->recalled_at && $message->attachment_path, 404);
        if ($url = $this->images->remoteUrl($message->attachment_path)) {
            $headers = [];
            if (preg_match('/^bytes=\d*-\d*$/D', (string) $request->header('Range'))) {
                $headers['Range'] = $request->header('Range');
            }
            try {
                $remote = Http::connectTimeout(10)->timeout(20)->withHeaders($headers)->get($url);
            } catch (ConnectionException) {
                abort(503, 'Chưa tải được tệp. Vui lòng thử lại.');
            }
            abort_unless(in_array($remote->status(), [200, 206], true), $remote->status() === 404 ? 404 : 503);
            $response = response($remote->body(), $remote->status())->withHeaders(['Content-Type' => $message->attachment_mime, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'Vary' => 'Cookie']);
            foreach (['Content-Range', 'Accept-Ranges'] as $header) {
                if ($remote->header($header)) {
                    $response->headers->set($header, $remote->header($header));
                }
            }

            return $response;
        }
        abort_unless(preg_match('~^media/[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|gif|webp|webm|m4a|mp4|ogg|wav)$~iD', $message->attachment_path) && Storage::disk('local')->exists($message->attachment_path), 404);

        return response()->file(Storage::disk('local')->path($message->attachment_path), ['Content-Type' => $message->attachment_mime, 'Cache-Control' => 'no-store', 'X-Content-Type-Options' => 'nosniff', 'Vary' => 'Cookie'])->setPrivate();
    }

    private function messageResponse(ChatMessage $message, ChatConversation $conversation): JsonResponse
    {
        $message->load(['sender', 'replyTo']);
        $partner = $conversation->partner(request()->user());

        return response()->json(['id' => $message->id, 'html' => view('chat.messages', ['messages' => collect([$message]), 'conversation' => $conversation->fresh(), 'canSend' => $this->chat->canSend(request()->user(), $partner), 'mutual' => $this->chat->mutual(request()->user(), $partner)])->render()])->header('Cache-Control', 'private, no-store');
    }
}
