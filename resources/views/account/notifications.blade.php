@extends('layouts.public')
@php($pageTitle = 'Thông báo')
@section('content')
<section class="container page-section notification-page">
    <h1>Thông báo</h1><p class="lead-muted">{{ $unread }} thông báo chưa đọc. Nhấn một thông báo để đến đúng trả lời.</p>
    <form method="post" action="{{ route('notifications.read-all') }}">@csrf<button type="submit" class="btn btn-outline-forest btn-sm mb-3">Đánh dấu tất cả đã đọc</button></form>
    @include('components.notification-items')
    {{ $notifications->links() }}
</section>
@endsection
