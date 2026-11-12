@extends('layouts.public')
@php($pageTitle = 'Quyền riêng tư và an toàn')
@section('content')
<div class="safety-page">
    <header class="safety-heading"><h1>Quyền riêng tư và an toàn</h1><p>Chọn người có thể xem bài viết và quản lý trải nghiệm của bạn.</p></header>
    <section class="safety-panel" aria-labelledby="app-experience-title">
        <h2 id="app-experience-title">Threads trên điện thoại</h2>
        <button type="button" class="btn-threads-outline" data-pwa-install hidden>Cài Threads</button>
        <p>Trên Safari: mở menu Chia sẻ → Thêm vào Màn hình chính. Trên Chrome: mở menu → Cài đặt ứng dụng hoặc Thêm vào màn hình chính.</p>
        <button type="button" class="btn-threads-outline" data-push-toggle hidden disabled>Bật thông báo trên thiết bị này</button>
        <p>Bạn cần duy trì đăng nhập để nhận thông báo đẩy. Khi đăng xuất, thông báo trên thiết bị này sẽ tắt.</p>
        <p data-push-status role="status" aria-live="polite">Thông báo đẩy cần HTTPS và trình duyệt hỗ trợ. Bạn vẫn có thể xem thông báo trong ứng dụng.</p>
    </section>
    <section class="safety-panel" aria-labelledby="mention-policy-title">
        <h2 id="mention-policy-title">Ai được nhắc đến tôi?</h2>
        <form method="post" action="{{ route('safety.mentions') }}">@csrf @method('patch')
            <label for="mention-policy">Cho phép nhắc đến trong bài viết và bình luận</label>
            <select id="mention-policy" name="mention_policy" class="form-select">@foreach(['everyone' => 'Mọi người', 'following' => 'Người tôi đang theo dõi', 'nobody' => 'Không ai'] as $value => $label)<option value="{{ $value }}" @selected($user->mention_policy === $value)>{{ $label }}</option>@endforeach</select>
            <button type="submit" class="btn-threads-outline">Lưu cài đặt nhắc đến</button>
        </form>
    </section>
    <p><a class="btn-threads-outline" href="{{ route('reports.index') }}">Báo cáo của tôi và kết quả xử lý</a></p>
    <section class="safety-panel" aria-labelledby="privacy-title">
        <h2 id="privacy-title">Tài khoản riêng tư</h2>
        <p>Khi bật, chỉ bạn và người theo dõi đã được chấp nhận mới xem được bài viết. Những người đang theo dõi bạn vẫn được giữ lại.</p>
        <form method="post" action="{{ route('safety.privacy') }}">
            @csrf @method('patch')
            <input type="hidden" name="is_private" value="0">
            <label class="safety-checkbox"><input type="checkbox" name="is_private" value="1" @checked($user->is_private)>Bật tài khoản riêng tư</label>
            <small>Khi chuyển sang công khai, các yêu cầu theo dõi đang chờ sẽ được chấp nhận.</small>
            <button type="submit" class="stream-submit">Lưu quyền riêng tư</button>
        </form>
    </section>
    <section class="safety-panel" id="follow-requests" aria-labelledby="requests-title">
        <h2 id="requests-title">Yêu cầu theo dõi <span>{{ $followRequests->total() }}</span></h2>
        @forelse($followRequests as $requester)
        <div class="safety-user-row"><a class="safety-user-identity" href="{{ route('profile.show', $requester->username ?: $requester->id) }}"><x-avatar :user="$requester"/><span><strong>{{ $requester->name }}<x-verified-badge :user="$requester"/></strong><small>{{ $requester->handle() }}</small></span></a><form method="post" action="{{ route('safety.follow-request', $requester) }}" class="safety-actions">@csrf<button type="submit" name="decision" value="accept" class="stream-submit">Chấp nhận</button><button type="submit" name="decision" value="reject" class="btn-threads-outline">Từ chối</button></form></div>
        @empty<p class="safety-empty">Chưa có yêu cầu theo dõi nào đang chờ.</p>@endforelse
        {{ $followRequests->links() }}
    </section>
    <section class="safety-panel" aria-labelledby="blocked-title">
        <h2 id="blocked-title">Tài khoản đã chặn <span>{{ $blockedUsers->total() }}</span></h2>
        @forelse($blockedUsers as $blockedUser)
        <div class="safety-user-row"><div class="safety-user-identity"><x-avatar :user="$blockedUser" :src="route('safety.avatar', $blockedUser)"/><span><strong>{{ $blockedUser->name }}</strong><small>{{ $blockedUser->handle() }}</small></span></div><form method="post" action="{{ route('safety.block', $blockedUser) }}">@csrf @method('delete')<button type="submit" class="btn-threads-outline">Bỏ chặn</button></form></div>
        @empty<p class="safety-empty">Bạn chưa chặn tài khoản nào.</p>@endforelse
        {{ $blockedUsers->links() }}
    </section>
    <section class="safety-panel" aria-labelledby="muted-title">
        <h2 id="muted-title">Tài khoản đã tắt tiếng <span>{{ $mutedUsers->total() }}</span></h2>
        @forelse($mutedUsers as $mutedUser)
        <div class="safety-user-row"><div class="safety-user-identity"><x-avatar :user="$mutedUser" :src="route('safety.avatar', $mutedUser)"/><span><strong>{{ $mutedUser->name }}</strong><small>{{ $mutedUser->handle() }}</small></span></div><form method="post" action="{{ route('safety.mute', $mutedUser) }}">@csrf @method('delete')<button type="submit" class="btn-threads-outline">Bỏ tắt tiếng</button></form></div>
        @empty<p class="safety-empty">Bạn chưa tắt tiếng tài khoản nào.</p>@endforelse
        {{ $mutedUsers->links() }}
    </section>
</div>
@endsection
