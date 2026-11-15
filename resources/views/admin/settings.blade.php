@extends('layouts.admin')
@php($pageTitle = 'Cài đặt website')
@section('content')
<div class="admin-page-heading"><div><p class="eyebrow">THÔNG TIN & NHẬN DIỆN</p><h1>Cài đặt website</h1><p class="muted">Cập nhật thông tin công khai và quy tắc của cộng đồng Threads City.</p></div></div>
<nav class="admin-settings-navigation" aria-label="Các mục cài đặt">
    <a href="#site-information"><x-journal-icon name="home" :size="17"/>Thông tin website</a>
    <a href="#community-information"><x-journal-icon name="book" :size="17"/>Giới thiệu & nội quy</a>
    <a href="#report-reasons"><x-journal-icon name="info" :size="17"/>Lý do báo cáo</a>
    <a href="#external-services"><x-journal-icon name="settings" :size="17"/>Dịch vụ</a>
</nav>
<form method="post" action="{{ route('admin.settings.update') }}" class="admin-settings-form">
    @csrf @method('patch')
    <div class="admin-settings-grid">
        <section class="panel" id="site-information"><div class="admin-panel-heading"><span class="admin-record-symbol"><x-journal-icon name="settings" :size="21"/></span><div><h2>Thông tin website</h2><p>Tên và mô tả xuất hiện trên website công khai.</p></div></div>
            <x-field name="site_name" label="Tên website" :value="$settings['site_name'] ?? config('blog.name')" required maxlength="100"/>
            <x-field name="site_tagline" label="Dòng giới thiệu ngắn" :value="$settings['site_tagline'] ?? config('blog.tagline')" required maxlength="200"/>
            <x-field name="site_description" label="Mô tả mặc định / SEO" type="textarea" :value="$settings['site_description'] ?? config('blog.description')" required maxlength="320" rows="3"/>
            <x-field name="contact_email" label="Email liên hệ công khai" type="email" :value="$settings['contact_email'] ?? ''" maxlength="255"/>
        </section>
        <section class="panel" id="community-information"><div class="admin-panel-heading"><span class="admin-record-symbol"><x-journal-icon name="book" :size="21"/></span><div><h2>Giới thiệu & nội quy</h2><p>Giúp thành viên hiểu và tham gia cộng đồng đúng cách.</p></div></div>
            <x-field name="about" label="Nội dung giới thiệu" type="textarea" :value="$settings['about'] ?? ''" rows="4" maxlength="10000"/>
            <x-field name="community_rules" label="Nội quy cộng đồng" type="textarea" :value="$settings['community_rules'] ?? ''" rows="5" maxlength="10000"/>
            <a class="admin-inline-link" href="{{ route('community.rules') }}" target="_blank" rel="noopener">Xem nội quy công khai<x-journal-icon name="arrow" :size="16"/></a>
        </section>
    </div>
    <section class="panel" id="report-reasons"><div class="admin-panel-heading"><span class="admin-record-symbol"><x-journal-icon name="info" :size="21"/></span><div><h2>Lý do báo cáo</h2><p>Điều chỉnh tên các lý do mà thành viên chọn khi gửi báo cáo.</p></div></div>
        <div class="admin-settings-labels">@foreach(\App\Models\ContentReport::reasons() as $key => $label)<div class="field"><label for="report-label-{{ $key }}">{{ \App\Models\ContentReport::REASONS[$key] }}</label><input id="report-label-{{ $key }}" class="form-control" name="report_reason_labels[{{ $key }}]" value="{{ old('report_reason_labels.'.$key, $label) }}" required maxlength="150"></div>@endforeach</div>
        <div class="admin-review-footer"><span>Thay đổi được áp dụng sau khi lưu.</span><button type="submit" class="btn btn-forest">Lưu cài đặt<x-journal-icon name="arrow" :size="16"/></button></div>
    </section>
</form>
<section class="panel admin-service-panel" id="external-services"><div class="admin-panel-heading"><span class="admin-record-symbol"><x-journal-icon name="info" :size="21"/></span><div><h2>Dịch vụ bên ngoài</h2><p>Trạng thái cấu hình các dịch vụ hỗ trợ đăng nhập và gửi thư.</p></div></div>
    <div class="admin-service-grid"><div><strong>Đăng nhập Google</strong><span @class(['admin-state', 'is-success' => config('services.google.client_id') && config('services.google.client_secret'), 'is-muted' => !config('services.google.client_id') || !config('services.google.client_secret')])><i aria-hidden="true"></i>{{ config('services.google.client_id') && config('services.google.client_secret') ? 'Đã có cấu hình' : 'Chưa cấu hình' }}</span></div><div><strong>Email thông báo</strong><span @class(['admin-state', 'is-success' => !in_array(config('mail.default'), ['array', 'log']), 'is-muted' => in_array(config('mail.default'), ['array', 'log'])])><i aria-hidden="true"></i>{{ in_array(config('mail.default'), ['array', 'log']) ? 'Chưa cấu hình gửi thư' : 'Đã chọn dịch vụ gửi thư' }}</span></div></div>
    <p class="admin-service-note">Cần kiểm tra đăng nhập và gửi thư thực tế khi triển khai. Thông tin xác thực được quản lý riêng.</p>
</section>
@endsection
