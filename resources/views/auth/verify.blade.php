@extends('layouts.threads')

@php $pageTitle = 'Xác minh email'; @endphp

@section('content')
<div class="auth-threads-wrapper">
    <div class="auth-threads-box text-center">
        <div class="mb-4">
            <svg class="threads-logo-svg" style="width: 48px; height: 48px;" viewBox="0 0 192 192">
                <path d="M141.537 88.9883C140.71 88.5919 139.87 88.2109 139.019 87.8451C137.537 60.5382 122.616 44.905 97.5619 44.745C97.4111 44.744 97.2604 44.744 97.1096 44.744C75.2536 44.744 57.0707 58.751 51.5204 79.882C46.0691 100.638 53.6841 123.332 70.8251 137.416C87.9661 151.5 112.43 155.083 133.024 146.516C148.971 139.878 159.227 124.965 160.827 106.012H144.741C143.342 118.847 136.035 129.351 124.636 134.1C109.117 140.556 90.6811 137.856 77.7656 127.243C64.8501 116.63 59.1121 99.5306 63.2201 83.8906C67.4326 67.8546 81.2336 57.214 97.7331 57.214C97.8471 57.214 97.9611 57.215 98.0751 57.216C113.627 57.315 124.516 67.045 125.753 84.148C117.753 82.522 108.887 81.879 99.5371 82.261C73.1971 83.337 57.4981 97.575 58.3341 117.747C59.1381 137.147 75.8361 149.336 96.0601 148.471C111.455 147.813 124.12 140.174 130.407 127.818C134.825 136.195 142.155 142.365 150.941 145.42C157.65 147.752 164.838 148.33 171.936 147.108L174.524 134.42C169.176 135.337 163.743 134.901 158.673 133.138C150.771 130.392 144.974 123.86 142.753 115.228C148.513 107.035 151.706 97.234 151.706 87.051C151.706 84.821 151.482 82.607 151.042 80.441L141.537 88.9883ZM127.469 110.15C125.132 117.841 117.844 134.871 96.6961 135.773C83.8401 136.321 73.1201 128.528 72.5851 115.632C71.9961 101.444 83.2161 93.684 99.0741 93.037C105.748 92.765 112.186 93.284 118.067 94.55C122.956 95.602 126.155 97.214 127.469 110.15Z"/>
            </svg>
            <h4 class="text-white fw-bold mt-3 mb-1">Xác minh địa chỉ Email</h4>
            <p class="text-secondary small">Vui lòng kiểm tra hộp thư để hoàn tất xác minh tài khoản</p>
        </div>

        <p class="text-white mb-4">
            Email của bạn: <strong class="text-info">{{ auth()->user()->email }}</strong>
        </p>

        @if(auth()->user()->hasVerifiedEmail())
            <div class="alert alert-success py-2 px-3 rounded-3 small mb-4 border-0" style="background: rgba(34, 197, 94, 0.15); color: var(--journal-success);">
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
        background: var(--journal-surface);
        border: 1px solid var(--border-color);
        border-radius: 24px;
        padding: 36px 32px;
        box-shadow: var(--journal-shadow);
    }
</style>
@endsection
