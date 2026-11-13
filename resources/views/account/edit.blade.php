@extends('layouts.threads')

@php 
    $pageTitle = 'Cài đặt tài khoản'; 
    $user = auth()->user(); 
@endphp

@section('content')
<div class="py-3">
    <p><a class="btn-threads-outline" href="{{ route('safety.index') }}">Quyền riêng tư, yêu cầu theo dõi và tài khoản đã chặn</a></p>
    <!-- Header -->
    <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom border-dark">
        <a href="{{ route('profile.show', $user->username ?? $user->id) }}" class="btn-threads-outline rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="Quay lại">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </a>
        <div>
            <h4 class="text-white fs-5 fw-bold mb-0">Cài đặt tài khoản</h4>
            <small class="text-secondary">Quản lý thông tin hồ sơ và bảo mật</small>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 rounded-3 small mb-4 border-0" style="background: rgba(239, 68, 68, 0.15); color: var(--journal-danger);">
            {{ $errors->first() }}
        </div>
    @endif

    <!-- Profile Information Panel -->
    <div class="p-4 rounded-4 mb-4" style="background: var(--journal-surface); border: 1px solid var(--border-color);">
        <h5 class="text-white fw-bold mb-3 fs-6">Hồ sơ cá nhân</h5>
        
        <form action="{{ route('account.update') }}" method="post" enctype="multipart/form-data">
            @csrf
            @method('patch')

            <!-- Avatar Section -->
            <div class="d-flex align-items-center gap-3 mb-4">
                @if($user->avatar_path)
                    <img src="{{ route('avatar.show', $user->id) }}" alt="{{ $user->name }}" style="width: 64px; height: 64px; border-radius: 50%; object-fit: cover; border: 2px solid var(--journal-line);">
                @else
                    <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--journal-raised); color: var(--journal-text); display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; border: 2px solid var(--journal-line);">
                        {{ mb_substr($user->name, 0, 1) }}
                    </div>
                @endif
                <div>
                    <label for="avatarInput" class="btn-threads-outline px-3 py-1 cursor-pointer">
                        Đổi ảnh đại diện
                    </label>
                    <input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/png,image/webp" hidden>
                    <div class="text-secondary small mt-1">JPEG, PNG, WebP tối đa 2 MB</div>
                </div>
            </div>

            <!-- Full Name -->
            <div class="mb-3">
                <label for="name" class="form-label text-secondary small fw-semibold">Họ và tên</label>
                <input 
                    type="text" 
                    name="name" 
                    id="name" 
                    class="threads-form-input" 
                    value="{{ old('name', $user->name) }}" 
                    required 
                    maxlength="100"
                >
            </div>

            <!-- Username -->
            <div class="mb-3">
                <label for="username" class="form-label text-secondary small fw-semibold">Tên người dùng (Handle)</label>
                <div class="position-relative">
                    <span class="position-absolute text-secondary" style="left: 14px; top: 14px; font-size: 15px;">@</span>
                    <input 
                        type="text" 
                        name="username" 
                        id="username" 
                        class="threads-form-input" 
                        style="padding-left: 32px;"
                        value="{{ old('username', $user->username) }}" 
                        maxlength="30"
                    >
                </div>
            </div>

            <!-- Bio -->
            <div class="mb-4">
                <label for="bio" class="form-label text-secondary small fw-semibold">Tiểu sử</label>
                <textarea 
                    name="bio" 
                    id="bio" 
                    class="threads-form-input" 
                    rows="3" 
                    placeholder="Viết một vài dòng giới thiệu về bạn..."
                    maxlength="1000"
                >{{ old('bio', $user->bio) }}</textarea>
            </div>

            <button type="submit" class="btn-threads-primary px-4 py-2 fw-bold">
                Lưu thay đổi
            </button>
        </form>
    </div>

    <!-- Security & Password Panel -->
    <div class="p-4 rounded-4" style="background: #181818; border: 1px solid var(--border-color);">
        <h5 class="text-white fw-bold mb-3 fs-6">Đổi mật khẩu</h5>

        <form action="{{ route('account.password') }}" method="post">
            @csrf
            @method('put')

            @if(!$user->google_id || $user->has_local_password)
                <div class="mb-3">
                    <label for="current_password" class="form-label text-secondary small fw-semibold">Mật khẩu hiện tại</label>
                    <input 
                        type="password" 
                        name="current_password" 
                        id="current_password" 
                        class="threads-form-input" 
                        required 
                        autocomplete="current-password"
                    >
                </div>
            @endif

            <div class="mb-3">
                <label for="password" class="form-label text-secondary small fw-semibold">Mật khẩu mới</label>
                <input 
                    type="password" 
                    name="password" 
                    id="password" 
                    class="threads-form-input" 
                    placeholder="Tối thiểu 8 ký tự, có chữ và số"
                    required 
                    minlength="8" 
                    autocomplete="new-password"
                >
            </div>

            <div class="mb-4">
                <label for="password_confirmation" class="form-label text-secondary small fw-semibold">Xác nhận mật khẩu mới</label>
                <input 
                    type="password" 
                    name="password_confirmation" 
                    id="password_confirmation" 
                    class="threads-form-input" 
                    required 
                    autocomplete="new-password"
                >
            </div>

            <button type="submit" class="btn-threads-outline px-4 py-2 fw-bold">
                Cập nhật mật khẩu
            </button>
        </form>
    </div>
</div>

<style>
    .threads-form-input {
        width: 100%;
        background: var(--journal-raised);
        border: 1px solid var(--journal-line);
        border-radius: 12px;
        padding: 12px 16px;
        color: var(--journal-text);
        font-size: 14px;
        outline: none;
        transition: border-color 0.2s, background 0.2s;
    }
    .threads-form-input:focus {
        border-color: var(--journal-line);
        background: var(--journal-hover);
    }
    .cursor-pointer {
        cursor: pointer;
    }
</style>
@endsection
