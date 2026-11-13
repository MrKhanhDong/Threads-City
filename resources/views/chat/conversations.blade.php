@forelse($conversations as $item)
    @php($other = $item->partner(auth()->user()))
    <a href="{{ route('chat.show', $item) }}" @class(['chat-conversation', 'is-active' => $conversation?->id === $item->id, 'is-unread' => $item->unread_count > 0]) @if($conversation?->id === $item->id) aria-current="page" @endif><x-avatar :user="$other"/><span class="chat-conversation-copy"><strong>{{ $other->name }}</strong><span>{{ $item->latestMessage?->sender_id === auth()->id() ? 'Bạn: ' : '' }}{{ \Illuminate\Support\Str::limit($item->latestMessage?->preview() ?? 'Bắt đầu trò chuyện', 65) }}</span></span><span class="chat-conversation-meta"><time>{{ $item->last_message_at?->diffForHumans(null, true, true) }}</time>@if($item->unread_count)<b aria-label="{{ $item->unread_count }} tin chưa đọc">{{ $item->unread_count > 99 ? '99+' : $item->unread_count }}</b>@endif</span></a>
@empty
    <div class="chat-empty-inbox"><x-journal-icon name="mail" :size="30"/><strong>{{ ($folder ?? 'inbox') === 'requests' ? 'Không có tin nhắn chờ' : 'Hộp thư còn trống' }}</strong><span>{{ ($folder ?? 'inbox') === 'requests' ? 'Tin từ người theo dõi bạn sẽ xuất hiện ở đây khi bạn chưa theo dõi lại.' : 'Nhắn tin với người bạn đang theo dõi.' }}</span></div>
@endforelse
@if($conversations->hasPages())<div class="chat-inbox-pagination">{{ $conversations->links() }}</div>@endif
