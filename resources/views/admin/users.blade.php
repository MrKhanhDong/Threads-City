@extends('layouts.admin')
@php($pageTitle = 'Quản lý người dùng')
@section('content')
<div class="admin-page-heading"><div><p class="eyebrow">QUẢN LÝ CỘNG ĐỒNG</p><h1>Người dùng</h1><p class="muted">Chỉ quản trị viên được cấp hoặc gỡ tích xanh xác minh danh tính. Tích xanh không thay đổi quyền của tài khoản.</p></div></div>
<section class="panel">
    <form method="get" class="media-search"><x-field name="q" label="Tìm tên, tên người dùng hoặc email" :value="request('q')" maxlength="150"/><button class="btn btn-outline-forest">Tìm người dùng</button></form>
    <div class="table-responsive">
        <table class="table editorial-table">
            <thead><tr><th>NGƯỜI DÙNG</th><th>EMAIL</th><th>VAI TRÒ</th><th>BÌNH LUẬN</th><th>TÍCH XANH</th><th>TRẠNG THÁI</th><th>THAO TÁC</th></tr></thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td><div class="author-line"><x-avatar :user="$user"/><div><strong>{{ $user->name }}<x-verified-badge :user="$user"/></strong><small>{{ $user->handle() }}</small></div></div></td>
                    <td>{{ $user->email }}<small>{{ $user->hasVerifiedEmail() ? 'Email đã xác minh' : 'Email chưa xác minh' }}</small></td>
                    <td>{{ $user->isAdmin() ? 'Quản trị viên' : 'Thành viên' }}</td>
                    <td>{{ $user->comments_count }}</td>
                    <td><span class="status">{{ $user->is_verified ? 'Đã cấp tích xanh' : 'Chưa cấp' }}</span></td>
                    <td><span class="status">{{ $user->is_locked ? 'Đang khóa' : 'Hoạt động' }}</span></td>
                    <td><div class="admin-user-actions">
                        <form method="post" action="{{ route('admin.users.verification', $user) }}" data-confirm="{{ $user->is_verified ? 'Gỡ tích xanh của tài khoản này?' : 'Cấp tích xanh xác minh danh tính cho tài khoản này?' }}">
                            @csrf @method('patch')
                            <input name="is_verified" value="{{ $user->is_verified ? 0 : 1 }}" type="hidden">
                            <button type="submit" class="btn btn-outline-forest btn-sm">{{ $user->is_verified ? 'Gỡ tích xanh' : 'Cấp tích xanh' }}</button>
                        </form>
                        @unless($user->isAdmin())
                        <form method="post" action="{{ route('admin.users.update', $user) }}" data-confirm="{{ $user->is_locked ? 'Mở khóa tài khoản này?' : 'Khóa tài khoản này? Phiên đăng nhập sẽ bị từ chối.' }}">
                            @csrf @method('patch')
                            <input name="is_locked" value="{{ $user->is_locked ? 0 : 1 }}" type="hidden">
                            <button type="submit" class="btn btn-outline-forest btn-sm">{{ $user->is_locked ? 'Mở khóa' : 'Khóa tài khoản' }}</button>
                        </form>
                        @endunless
                    </div></td>
                </tr>
                @empty<tr><td colspan="7">Không có người dùng phù hợp.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
</section>
@endsection
