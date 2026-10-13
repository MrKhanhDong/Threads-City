<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $pageTitle ?? $site['site_name'] }}{{ isset($pageTitle) ? ' — '.$site['site_name'] : '' }}</title>
<meta name="description" content="{{ $seo['description'] ?? $site['site_description'] }}">
<meta name="robots" content="{{ ($preview ?? false) || request()->routeIs('auth.*','login','register','saved','account.*','password.*','verification.*','notifications.*') || request()->has('q') ? 'noindex,nofollow' : 'index,follow' }}">
<link rel="canonical" href="{{ $seo['canonical'] ?? url()->current() }}">
<meta property="og:type" content="{{ isset($post) ? 'article' : 'website' }}">
<meta property="og:title" content="{{ $seo['og_title'] ?? $pageTitle ?? $site['site_name'] }}">
<meta property="og:description" content="{{ $seo['og_description'] ?? $seo['description'] ?? $site['site_description'] }}">
<meta property="og:url" content="{{ $seo['canonical'] ?? url()->current() }}">
<meta property="og:site_name" content="{{ $site['site_name'] }}">
<meta name="twitter:card" content="{{ !empty($seo['image']) ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo['og_title'] ?? $pageTitle ?? $site['site_name'] }}">
<meta name="twitter:description" content="{{ $seo['og_description'] ?? $seo['description'] ?? $site['site_description'] }}">
@if(!empty($seo['image']))<meta property="og:image" content="{{ $seo['image'] }}"><meta name="twitter:image" content="{{ $seo['image'] }}">@endif
@vite(['resources/css/app.css','resources/js/app.js'])
@stack('head')
</head>
<body class="public-body">
<a href="#main" class="skip-link">Đến nội dung chính</a>
<header class="site-header">
<div class="container header-inner">
<a class="brand" href="{{ route('home') }}"><span class="brand-mark">m.</span><span>{{ $site['site_name'] }}<small>GHI CHÉP & CÂU CHUYỆN</small></span></a>
<nav aria-label="Điều hướng chính"><a @class(['active'=>request()->routeIs('home')]) href="{{ route('home') }}">Trang chủ</a><a @class(['active'=>request()->routeIs('posts.*')]) href="{{ route('posts.index') }}">Bài viết</a><a href="{{ route('about') }}">Về Mộc</a><a href="{{ route('contact') }}">Liên hệ</a></nav>
<div class="header-actions"><a href="{{ route('posts.index') }}" aria-label="Tìm bài viết" class="search-link">⌕</a>
@auth
<x-notification-bell />
<div class="dropdown"><button class="account-button" data-bs-toggle="dropdown" aria-expanded="false"><x-avatar :user="auth()->user()" /><span>{{ auth()->user()->name }}</span></button><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="{{ route('account.edit') }}">Hồ sơ của tôi</a></li><li><a class="dropdown-item" href="{{ route('saved') }}">Bài viết đã lưu</a></li>@if(auth()->user()->isAdmin())<li><a class="dropdown-item" href="{{ route('admin.index') }}">Quản trị blog</a></li>@endif<li><form action="{{ route('logout') }}" method="post">@csrf<button class="dropdown-item" type="submit">Đăng xuất</button></form></li></ul></div>
@else<a class="btn btn-outline-forest btn-sm" href="{{ route('login') }}">Đăng nhập ↗</a>@endauth
</div></div></header>
<main id="main"><div class="container"><x-flash /></div>@yield('content')</main>
<footer class="site-footer"><div class="container footer-top"><div><a class="brand" href="{{ route('home') }}"><span class="brand-mark">m.</span>{{ $site['site_name'] }}</a><p>{{ $site['site_tagline'] }}</p></div><div class="footer-links"><a href="{{ route('author') }}">Tác giả</a><a href="{{ route('privacy') }}">Quyền riêng tư</a><a href="{{ route('terms') }}">Điều khoản</a></div></div><div class="container footer-bottom"><span>© {{ date('Y') }} {{ $site['site_name'] }}.</span><span>Viết từ những điều giản dị. Đọc theo nhịp của bạn.</span></div></footer>
@stack('scripts')
</body></html>
