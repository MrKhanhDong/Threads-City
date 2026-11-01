@extends('layouts.threads')
@php $pageTitle = 'Không tìm thấy trang'; @endphp
@section('content')
<section class="text-center py-5">
    <p class="text-secondary">404 / THREADS CITY</p>
    <h1 class="text-white h3">Không tìm thấy trang</h1>
    <p class="text-secondary">Liên kết không tồn tại hoặc bài viết đã bị xóa.</p>
    <a class="btn-threads-primary d-inline-block px-4 py-2" href="{{ route('home') }}">Về Threads City</a>
</section>
@endsection
