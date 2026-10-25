@extends('layouts.public')
@php $pageTitle='Không tìm thấy trang'; @endphp
@section('content')<section class="container page-section empty-state"><p class="eyebrow">404 / MỘC JOURNAL</p><h1>Câu chuyện này không ở đây.</h1><p>Liên kết có thể đã thay đổi hoặc bài viết chưa được công khai.</p><a class="btn btn-forest" href="{{ route('posts.index') }}">Về góc đọc ↗</a></section>@endsection
