@extends('layouts.threads')

@php $pageTitle = 'Đăng nhập'; @endphp

@section('content')
<div class="auth-threads-wrapper">
    <div class="auth-threads-box">
        <!-- Threads Logo -->
        <div class="text-center mb-4">
            <span class="journal-mark" aria-hidden="true">m.</span>
            <h4 class="text-white fw-bold mt-3 mb-1">Đăng nhập vào góc đọc</h4>
            <p class="text-secondary small">Lưu câu chuyện yêu thích và để lại lời nhắn cho tác giả.</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger py-2 px-3 rounded-3 small mb-3 border-0" style="background: rgba(239, 68, 68, 0.15); color: #fca5a5;">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.store') }}" method="post">
            @csrf

            <div class="mb-3">
                <input
                    type="text"
                    name="login"
                    id="login"
                    class="threads-form-input"
                    placeholder="Tên người dùng hoặc email"
                    value="{{ old('login', old('email')) }}"
                    required
                    autofocus
                >
            </div>

            <div class="mb-3">
                <input
                    type="password"
                    name="password"
                    id="password"
                    class="threads-form-input"
                    placeholder="Mật khẩu"
                    required
                >
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <label class="d-flex align-items-center gap-2 text-secondary small cursor-pointer">
                    <input type="checkbox" name="remember" value="1" style="accent-color: #fff; width: 16px; height: 16px;">
                    <span>Ghi nhớ đăng nhập</span>
                </label>
                <a href="{{ route('password.request') }}" class="text-secondary small text-decoration-none hover-white">
                    Quên mật khẩu?
                </a>
            </div>

            <button type="submit" class="btn-threads-primary w-100 py-3 fw-bold fs-6 mb-3">
                Đăng nhập
            </button>
        </form>

        <!-- Google Login -->
        <a href="{{ route('google.redirect') }}" class="btn-threads-outline w-100 py-2 d-flex align-items-center justify-content-center gap-2 mb-4">
            <svg width="18" height="18" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <span>Tiếp tục với Google</span>
        </a>

        <!-- Register Link -->
        <div class="text-center pt-3 border-top border-dark">
            <span class="text-secondary small">Chưa có tài khoản? </span>
            <a href="{{ route('register') }}" class="text-white fw-bold small text-decoration-none">
                Đăng ký ngay
            </a>
        </div>
    </div>
</div>

<style>
    .auth-threads-wrapper {
        min-height: 75vh;
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
    .hover-white:hover {
        color: #ffffff !important;
    }
    .cursor-pointer {
        cursor: pointer;
    }
</style>
@endsection
