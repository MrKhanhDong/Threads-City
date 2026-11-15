@extends('layouts.admin')
@php($pageTitle = 'Quản lý người dùng')
@section('content')
<div class="admin-page-heading admin-users-heading">
    <div>
        <p class="eyebrow">CỘNG ĐỒNG THREADS CITY</p>
        <h1>Người dùng</h1>
        <p class="muted">Quản lý thông tin, trạng thái hoạt động và xác minh tài khoản.</p>
    </div>
    <span class="admin-users-count"><x-journal-icon name="people" :size="17"/><strong>{{ number_format($users->total()) }}</strong> tài khoản</span>
</div>

<section class="panel admin-users-filters" aria-label="Tìm kiếm và lọc người dùng">
    <details class="admin-filter-disclosure" data-admin-filter-disclosure data-has-filters="{{ request()->filled('q') || request()->filled('status') ? 'true' : 'false' }}" open>
    <summary><x-journal-icon name="filter" :size="18"/><span>Tìm kiếm và lọc người dùng</span><x-journal-icon name="chevron" :size="15"/></summary>
    <form method="get" class="admin-users-filter-form">
        <x-field name="q" label="Tên, email hoặc username" :value="request('q')" maxlength="150" placeholder="Tìm theo tên, email hoặc username…"/>
        <div class="field">
            <label for="status">Trạng thái</label>
            <select name="status" id="status" class="form-select">
                <option value="">Tất cả trạng thái</option>
                @foreach(['active' => 'Hoạt động', 'locked' => 'Đang khóa', 'verified' => 'Có tích xanh'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-forest"><x-journal-icon name="search" :size="17"/>Tìm người dùng</button>
    </form>
    @if(request()->filled('q') || request()->filled('status'))
        <div class="admin-users-filter-summary"><span>{{ number_format($users->total()) }} kết quả phù hợp</span><a href="{{ route('admin.users.index') }}">Xóa bộ lọc</a></div>
    @endif
    </details>
</section>

<div class="admin-user-list">
    @forelse($users as $user)
        <article class="panel admin-user-card" aria-labelledby="user-name-{{ $user->id }}">
            <header class="admin-user-header">
                <div class="admin-user-identity">
                    <span class="avatar admin-user-avatar" aria-hidden="true">{{ mb_substr($user->name, 0, 1) }}</span>
                    <div class="admin-user-title">
                        <h2 id="user-name-{{ $user->id }}" class="admin-user-name"><span>{{ $user->name }}</span><x-verified-badge :user="$user" :size="16"/></h2>
                        <span class="admin-user-handle">{{ $user->handle() }}</span>
                    </div>
                </div>
                <span @class(['admin-user-status', 'is-admin' => $user->isAdmin(), 'is-locked' => !$user->isAdmin() && $user->is_locked, 'is-active' => !$user->isAdmin() && !$user->is_locked])><span aria-hidden="true"></span>{{ $user->isAdmin() ? 'Quản trị viên' : ($user->is_locked ? 'Đang khóa' : 'Hoạt động') }}</span>
            </header>

            <div class="admin-user-meta">
                <span class="admin-user-email">{{ $user->email }}</span>
                <span><strong>{{ $user->posts_count }}</strong> bài đăng / trả lời</span>
                <span><strong>{{ $user->comments_count }}</strong> bình luận</span>
            </div>
            @if($user->bio)<p class="admin-user-bio preserve-lines">{{ $user->bio }}</p>@endif
            @if($user->is_locked)
                <div class="admin-user-lock-notice"><strong>Lý do khóa:</strong> {{ $user->lock_reason ?? 'Không có lý do từ dữ liệu cũ' }}<span>{{ $user->lock_expires_at ? 'Đến '.$user->lock_expires_at->format('d/m/Y H:i') : 'Vô thời hạn' }}</span></div>
            @endif

            <nav class="admin-user-links" aria-label="Thông tin tài khoản {{ $user->name }}">
                <a href="{{ route('profile.show', $user->username ?: $user->id) }}" target="_blank" rel="noopener"><x-journal-icon name="arrow" :size="15"/>Xem hồ sơ</a>
                <a href="{{ route('admin.community.index', ['author' => $user->id, 'status' => 'all']) }}"><x-journal-icon name="book" :size="15"/>Bài đăng</a>
                <a href="{{ route('admin.replies.index', ['author' => $user->id, 'status' => 'all']) }}"><x-journal-icon name="message" :size="15"/>Trả lời</a>
                <a href="{{ route('admin.history.index', ['target_type' => 'user', 'target_id' => $user->id]) }}"><x-journal-icon name="repost" :size="15"/>Lịch sử xử lý</a>
            </nav>

            <div class="admin-user-controls">
                @unless($user->isAdmin())
                    <details class="admin-action-disclosure" @if($user->is_locked) open @endif>
                    <summary><x-journal-icon name="archive" :size="17"/><span>{{ $user->is_locked ? 'Mở khóa tài khoản' : 'Quản lý khóa tài khoản' }}</span><x-journal-icon name="chevron" :size="15"/></summary>
                    <form method="post" action="{{ route('admin.users.update', $user) }}" @class(['admin-user-lock-form', 'is-unlocking' => $user->is_locked]) data-confirm="Xác nhận thay đổi trạng thái tài khoản?">
                        @csrf
                        @method('patch')
                        <input type="hidden" name="is_locked" value="{{ $user->is_locked ? 0 : 1 }}">
                        <div class="field">
                            <label for="lock-reason-{{ $user->id }}">Lý do {{ $user->is_locked ? 'mở khóa' : 'khóa' }}</label>
                            <input id="lock-reason-{{ $user->id }}" name="reason" class="form-control" placeholder="Nhập lý do {{ $user->is_locked ? 'mở khóa' : 'khóa tài khoản' }}…" required maxlength="1000">
                        </div>
                        @unless($user->is_locked)
                            <div class="field">
                                <label for="lock-expiry-{{ $user->id }}">Thời hạn khóa</label>
                                <input type="datetime-local" name="lock_expires_at" id="lock-expiry-{{ $user->id }}" class="form-control" aria-describedby="lock-help-{{ $user->id }}">
                            </div>
                        @endunless
                        <button type="submit" @class(['btn', 'admin-user-lock-button' => !$user->is_locked, 'btn-outline-forest' => $user->is_locked])>{{ $user->is_locked ? 'Mở khóa' : 'Khóa tài khoản' }}</button>
                        @unless($user->is_locked)
                            <p class="admin-user-action-hint" id="lock-help-{{ $user->id }}">Để trống thời hạn nếu khóa vô thời hạn. Múi giờ: {{ config('app.timezone') }}.</p>
                        @endunless
                    </form>
                    </details>
                @endunless

                <details class="admin-user-verification">
                    <summary><span class="admin-user-verification-icon" aria-hidden="true">✓</span><span>{{ $user->is_verified ? 'Gỡ tích xanh' : 'Cấp tích xanh' }}</span></summary>
                    <form method="post" action="{{ route('admin.users.verification', $user) }}" class="admin-user-verification-form">
                        @csrf
                        @method('patch')
                        <input type="hidden" name="is_verified" value="{{ $user->is_verified ? 0 : 1 }}">
                        <div class="field">
                            <label for="verification-reason-{{ $user->id }}">Lý do xác minh</label>
                            <input name="reason" id="verification-reason-{{ $user->id }}" class="form-control" placeholder="Nhập lý do {{ $user->is_verified ? 'gỡ' : 'cấp' }} tích xanh…" required maxlength="1000">
                        </div>
                        <button type="submit" class="btn btn-outline-forest">Xác nhận</button>
                    </form>
                </details>
            </div>
        </article>
    @empty
        <section class="panel admin-users-empty"><x-journal-icon name="people" :size="32"/><h2>Không tìm thấy người dùng</h2><p>Thử tìm bằng tên, email, username hoặc thay đổi bộ lọc.</p><a class="btn btn-outline-forest" href="{{ route('admin.users.index') }}">Đặt lại bộ lọc</a></section>
    @endforelse
</div>
<div class="admin-users-pagination">{{ $users->links() }}</div>
@endsection
