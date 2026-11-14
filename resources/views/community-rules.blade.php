@extends('layouts.threads')
@php($pageTitle = 'Nội quy cộng đồng')
@section('content')
<article class="p-4"><h1>Nội quy cộng đồng</h1><p class="preserve-lines">{{ $rules ?: 'Tôn trọng người khác. Không đăng spam, lừa đảo, nội dung quấy rối hoặc ngôn từ thù địch. Hãy báo cáo nội dung vi phạm để quản trị viên xem xét.' }}</p></article>
@endsection
