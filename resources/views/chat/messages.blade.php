@foreach($messages as $message)
    @php
        $own = $message->sender_id === auth()->id();
        $partnerSide = $conversation->side(auth()->user()) === 'first' ? 'second' : 'first';
        $seen = $own && $message->id <= $conversation->{$partnerSide.'_read_id'};
        $revision = hash('sha256', json_encode([$message->body, $message->kind, $message->sticker, $message->reactions, $message->recalled_at?->toIso8601String(), $message->edited_at?->toIso8601String(), $message->replyTo?->preview(), $message->attachment_path, $message->duration_ms, $canSend ?? false, $mutual ?? false]));
    @endphp
    <article @class(['chat-message', 'is-own' => $own, 'is-recalled' => (bool) $message->recalled_at]) data-chat-message="{{ $message->id }}" data-chat-revision="{{ $revision }}" data-own="{{ $own ? '1' : '0' }}" data-kind="{{ $message->kind }}" data-author="{{ $message->sender->name }}" data-message-url="{{ route('chat.update', [$conversation, $message]) }}" data-reaction-url="{{ route('chat.react', [$conversation, $message]) }}">
        @unless($own)<x-avatar :user="$message->sender"/>@endunless
        <div class="chat-message-content">
            @if($message->replyTo && !$message->recalled_at)<button type="button" class="chat-quoted" data-chat-jump="{{ $message->reply_to_id }}" title="Xem tin nhắn gốc"><x-journal-icon name="repost" :size="13"/><span>{{ \Illuminate\Support\Str::limit($message->replyTo->preview(), 90) }}</span></button>@endif
            <div class="chat-bubble-row"><div class="chat-bubble">
                @if($message->recalled_at)<span>Tin nhắn đã được thu hồi</span>
                @else
                    @if($message->kind === 'image')<a href="{{ route('chat.attachment', [$conversation, $message]) }}" data-chat-image aria-label="Xem ảnh đính kèm"><img class="chat-message-image" src="{{ route('chat.attachment', [$conversation, $message]) }}" alt="Ảnh trong hội thoại" loading="lazy"></a>@endif
                    @if($message->kind === 'voice')<div class="voice-player chat-voice-player" data-player><audio class="chat-message-voice" controls preload="none" data-voice data-duration="{{ ($message->duration_ms ?? 0) / 1000 }}" src="{{ route('chat.attachment', [$conversation, $message]) }}" aria-label="Tin nhắn thoại"></audio></div>@endif
                    @if($message->kind === 'sticker')<img class="chat-message-sticker" src="{{ asset('chat-stickers/'.$message->sticker.'.svg') }}" alt="{{ \App\Services\DirectMessages::STICKERS[$message->sticker] ?? 'Sticker' }}">@endif
                    @if($message->body !== null && $message->body !== '')<p data-chat-body>{{ $message->body }}</p>@endif
                @endif
            </div>
            @if(!$message->recalled_at)<div class="chat-message-tools">@if($canSend ?? false)<button type="button" data-chat-reply title="Trả lời" aria-label="Trả lời"><x-journal-icon name="arrow-left" :size="16"/></button>@endif @if($canSend ?? false)<details class="chat-reaction-picker" data-chat-actions><summary title="Thả cảm xúc" aria-label="Thả cảm xúc"><x-journal-icon name="smile" :size="18"/></summary><div>@foreach(\App\Services\DirectMessages::REACTIONS as $reaction)<button type="button" data-chat-react="{{ $reaction }}" aria-label="Cảm xúc {{ $reaction }}">{{ $reaction }}</button>@endforeach<button type="button" data-chat-react="" aria-label="Gỡ cảm xúc">✕</button></div></details>@endif @if($own)<details class="chat-message-actions" data-chat-actions><summary title="Tùy chọn tin nhắn" aria-label="Tùy chọn tin nhắn"><x-journal-icon name="write" :size="16"/></summary><div class="chat-message-menu">@if($message->kind === 'text')<button type="button" data-chat-edit>Sửa</button>@endif<button type="button" data-chat-recall>Thu hồi</button></div></details>@endif</div>@endif</div>
            @if($message->reactions && !$message->recalled_at)<div class="chat-reactions">@foreach(array_count_values($message->reactions) as $emoji => $count)<span>{{ $emoji }} {{ $count }}</span>@endforeach</div>@endif
            <footer><time datetime="{{ $message->created_at->toIso8601String() }}" title="{{ $message->created_at->format('d/m/Y H:i') }}">{{ $message->created_at->format('H:i') }}</time>@if($message->edited_at && !$message->recalled_at)<span>· Đã chỉnh sửa</span>@endif @if($own)<span data-chat-delivery>· {{ $seen ? 'Đã xem' : 'Đã gửi' }}</span>@endif</footer>
        </div>
    </article>
@endforeach
