@extends('layouts.threads')

@php $pageTitle = 'Đặt lại mật khẩu'; @endphp

@section('content')
<div class="auth-threads-wrapper">
    <div class="auth-threads-box">
        <div class="text-center mb-4">
            <span class="journal-mark" aria-hidden="true">m.</span>
            <h4 class="text-white fw-bold mt-3 mb-1">Tạo mật khẩu mới</h4>
            <p class="text-secondary small">Nhập mật khẩu mới cho tài khoản của bạn</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger py-2 px-3 rounded-3 small mb-3 border-0" style="background: rgba(239, 68, 68, 0.15); color: #fca5a5;">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="mb-3">
                <input
                    type="email"
                    name="email"
                    id="email"
                    class="threads-form-input"
                    placeholder="Email"
                    value="{{ is_string($email) ? $email : '' }}"
                    required
                >
            </div>

            <div class="mb-3">
                <input
                    type="password"
                    name="password"
                    id="password"
                    class="threads-form-input"
                    placeholder="Mật khẩu mới (tối thiểu 8 ký tự)"
                    required
                    minlength="8"
                    autocomplete="new-password"
                >
            </div>

            <div class="mb-4">
                <input
                    type="password"
                    name="password_confirmation"
                    id="password_confirmation"
                    class="threads-form-input"
                    placeholder="Xác nhận mật khẩu mới"
                    required
                    autocomplete="new-password"
                >
            </div>

            <button type="submit" class="btn-threads-primary w-100 py-3 fw-bold fs-6">
                Đặt lại mật khẩu
            </button>
        </form>
    </div>
</div>

<style>
    .auth-threads-wrapper {
        min-height: 70vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px 0;
    }
    .auth-threads-box {
        width: 100%;
        max-width: 420px;
        background: #181818;
        border: 1px solid var(--border-color);
        border-radius: 24px;
        padding: 36px 32px;
        box-shadow: 0 16px 48px rgba(0,0,0,0.4);
    }
    .threads-form-input {
        width: 100%;
        background: #242424;
        border: 1px solid #333333;
        border-radius: 12px;
        padding: 14px 16px;
        color: #ffffff;
        font-size: 15px;
        outline: none;
        transition: border-color 0.2s, background 0.2s;
    }
    .threads-form-input:focus {
        border-color: #555555;
        background: #282828;
    }
    .threads-form-input::placeholder {
        color: #777777;
    }
</style>
@endsection
