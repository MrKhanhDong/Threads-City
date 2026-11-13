@forelse($people as $person)
<form method="post" action="{{ route('chat.start') }}" data-chat-start>@csrf<input type="hidden" name="recipient_id" value="{{ $person->id }}"><button type="submit" class="chat-person"><x-avatar :user="$person"/><span class="chat-person-copy"><strong>{{ $person->name }}</strong><small>{{ $person->handle() }}</small></span><x-journal-icon name="arrow" :size="18"/></button></form>
@empty
<p class="chat-empty-search">Chỉ có thể nhắn tin cho người bạn đang theo dõi. Chưa tìm thấy người phù hợp.</p>
@endforelse
