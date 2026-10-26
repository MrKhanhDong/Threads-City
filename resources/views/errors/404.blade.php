@extends('layouts.public')
@php $pageTitle='Không tìm thấy trang'; @endphp
@section('content')<section class="container page-section empty-state"><p class="eyebrow">404 / THREADS</p><h1>Nội dung này không tồn tại.</h1><p>Liên kết có thể đã thay đổi hoặc bài viết đã bị xóa.</p><a class="btn btn-forest" href="{{ route('home') }}">Về trang Threads ↗</a></section>@endsection
