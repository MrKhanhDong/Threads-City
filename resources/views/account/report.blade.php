@extends('layouts.public')
@php($pageTitle = $thread ? 'Báo cáo bài viết' : 'Báo cáo tài khoản')
@section('content')
<div class="safety-page">
    <header class="safety-heading"><h1>{{ $pageTitle }}</h1><p>Giúp cộng đồng an toàn hơn. Chỉ quản trị viên xem được thông tin người gửi báo cáo.</p></header>
    <section class="safety-panel">
        <h2>{{ $targetName }}</h2><blockquote class="safety-report-excerpt">{{ $excerpt }}</blockquote>
        <form method="post" action="{{ $thread ? route('reports.thread', $thread) : route('reports.user', $user) }}">
            @csrf
            <label for="report-reason">Lý do báo cáo</label>
            <select name="reason" id="report-reason" class="form-select" required><option value="">Chọn lý do</option>@foreach($reasons as $value => $label)<option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>@endforeach</select>
            @error('reason')<p class="text-danger">{{ $message }}</p>@enderror
            <label for="report-details">Thông tin bổ sung (không bắt buộc)</label>
            <textarea name="details" id="report-details" class="form-control" rows="4" maxlength="2000" placeholder="Mô tả thêm để quản trị viên xem xét">{{ old('details') }}</textarea>
            @error('details')<p class="text-danger">{{ $message }}</p>@enderror
            <div class="safety-actions"><button type="submit" class="stream-submit">Gửi báo cáo</button><a href="{{ route('home') }}" class="btn-threads-outline">Hủy</a></div>
        </form>
    </section>
</div>
@endsection
