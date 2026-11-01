@extends('layouts.threads')

@php $pageTitle = 'Đăng ký tài khoản'; @endphp

@section('content')
<div class="auth-threads-wrapper">
    <div class="auth-threads-box">
        <!-- Threads Logo -->
        <div class="text-center mb-4">
            <svg class="threads-logo-svg" style="width: 48px; height: 48px;" viewBox="0 0 192 192">
                <path d="M141.537 88.9883C140.71 88.5919 139.87 88.2109 139.019 87.8451C137.537 60.5382 122.616 44.905 97.5619 44.745C97.4111 44.744 97.2604 44.744 97.1096 44.744C75.2536 44.744 57.0707 58.751 51.5204 79.882C46.0691 100.638 53.6841 123.332 70.8251 137.416C87.9661 151.5 112.43 155.083 133.024 146.516C148.971 139.878 159.227 124.965 160.827 106.012H144.741C143.342 118.847 136.035 129.351 124.636 134.1C109.117 140.556 90.6811 137.856 77.7656 127.243C64.8501 116.63 59.1121 99.5306 63.2201 83.8906C67.4326 67.8546 81.2336 57.214 97.7331 57.214C97.8471 57.214 97.9611 57.215 98.0751 57.216C113.627 57.315 124.516 67.045 125.753 84.148C117.753 82.522 108.887 81.879 99.5371 82.261C73.1971 83.337 57.4981 97.575 58.3341 117.747C59.1381 137.147 75.8361 149.336 96.0601 148.471C111.455 147.813 124.12 140.174 130.407 127.818C134.825 136.195 142.155 142.365 150.941 145.42C157.65 147.752 164.838 148.33 171.936 147.108L174.524 134.42C169.176 135.337 163.743 134.901 158.673 133.138C150.771 130.392 144.974 123.86 142.753 115.228C148.513 107.035 151.706 97.234 151.706 87.051C151.706 84.821 151.482 82.607 151.042 80.441L141.537 88.9883ZM127.469 110.15C125.132 117.841 117.844 134.871 96.6961 135.773C83.8401 136.321 73.1201 128.528 72.5851 115.632C71.9961 101.444 83.2161 93.684 99.0741 93.037C105.748 92.765 112.186 93.284 118.067 94.55C122.956 95.602 126.155 97.214 127.469 110.15Z"/>
            </svg>
            <h4 class="text-white fw-bold mt-3 mb-1">Tham gia Threads</h4>
            <p class="text-secondary small">Tạo tài khoản để đăng bài, thích và trò chuyện cùng cộng đồng</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger py-2 px-3 rounded-3 small mb-3 border-0" style="background: rgba(239, 68, 68, 0.15); color: var(--journal-danger);">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('register.store') }}" method="post">
            @csrf

            <div class="mb-3">
                <input 
                    type="text" 
                    name="name" 
                    id="name" 
                    class="threads-form-input" 
                    placeholder="Họ và tên" 
                    value="{{ old('name') }}"
                    required 
                    autofocus
                >
            </div>

            <div class="mb-3">
                <div class="position-relative">
                    <span class="position-absolute text-secondary" style="left: 14px; top: 14px; font-size: 15px;">@</span>
                    <input 
                        type="text" 
                        name="username" 
                        id="username" 
                        class="threads-form-input" 
                        style="padding-left: 32px;"
                        placeholder="tên_người_dùng (vd: alex_nguyen)" 
                        value="{{ old('username') }}"
                    >
                </div>
                <small class="text-secondary" style="font-size: 11px;">Tùy chọn. Nếu để trống hệ thống sẽ tự tạo theo tên của bạn.</small>
            </div>

            <div class="mb-3">
                <input 
                    type="email" 
                    name="email" 
                    id="email" 
                    class="threads-form-input" 
                    placeholder="Địa chỉ email" 
                    value="{{ old('email') }}"
                    required
                >
            </div>

            <div class="mb-3">
                <input 
                    type="password" 
                    name="password" 
                    id="password" 
                    class="threads-form-input" 
                    placeholder="Mật khẩu (tối thiểu 8 ký tự)" 
                    required
                >
            </div>

            <div class="mb-4">
                <input 
                    type="password" 
                    name="password_confirmation" 
                    id="password_confirmation" 
                    class="threads-form-input" 
                    placeholder="Xác nhận mật khẩu" 
                    required
                >
            </div>

            <button type="submit" class="btn-threads-primary w-100 py-3 fw-bold fs-6 mb-3">
                Tạo tài khoản
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
            <span>Đăng ký với Google</span>
        </a>

        <!-- Login Link -->
        <div class="text-center pt-3 border-top border-dark">
            <span class="text-secondary small">Đã có tài khoản? </span>
            <a href="{{ route('login') }}" class="text-white fw-bold small text-decoration-none">
                Đăng nhập
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
        max-width: 440px;
        background: var(--journal-surface);
        border: 1px solid var(--border-color);
        border-radius: 24px;
        padding: 36px 32px;
        box-shadow: var(--journal-shadow);
    }
    .threads-form-input {
        width: 100%;
        background: var(--journal-raised);
        border: 1px solid var(--journal-line);
        border-radius: 12px;
        padding: 14px 16px;
        color: var(--journal-text);
        font-size: 15px;
        outline: none;
        transition: border-color 0.2s, background 0.2s;
    }
    .threads-form-input:focus {
        border-color: var(--journal-line);
        background: var(--journal-hover);
    }
    .threads-form-input::placeholder {
        color: var(--journal-faint);
    }
</style>
@endsection
