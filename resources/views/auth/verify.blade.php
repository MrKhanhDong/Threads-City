@extends('layouts.threads')

@php $pageTitle = 'Xác minh email'; @endphp

@section('content')
<div class="auth-threads-wrapper">
    <div class="auth-threads-box text-center">
        <div class="mb-4">
            <span class="journal-mark" aria-hidden="true">m.</span>
            <h4 class="text-white fw-bold mt-3 mb-1">Xác minh địa chỉ Email</h4>
            <p class="text-secondary small">Vui lòng kiểm tra hộp thư để hoàn tất xác minh tài khoản</p>
        </div>

        <p class="text-white mb-4">
            Email của bạn: <strong class="text-info">{{ auth()->user()->email }}</strong>
        </p>

        @if(auth()->user()->hasVerifiedEmail())
            <div class="alert alert-success py-2 px-3 rounded-3 small mb-4 border-0" style="background: rgba(34, 197, 94, 0.15); color: #86efac;">
                ✓ Email của bạn đã được xác minh.
            </div>
            <a class="btn-threads-primary w-100 py-3 fw-bold d-inline-block text-center" href="{{ route('home') }}">
                Đến dòng thời gian
            </a>
        @else
            <form action="{{ route('verification.send') }}" method="post" class="mb-3">
                @csrf
                <button type="submit" class="btn-threads-primary w-100 py-3 fw-bold">
                    Gửi lại email xác minh
                </button>
            </form>
            <a class="btn-threads-outline w-100 py-2 d-inline-block text-center" href="{{ route('home') }}">
                Bỏ qua lúc này
            </a>
        @endif
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
        max-width: 440px;
        background: #181818;
        border: 1px solid var(--border-color);
        border-radius: 24px;
        padding: 36px 32px;
        box-shadow: 0 16px 48px rgba(0,0,0,0.4);
    }
</style>
@endsection
