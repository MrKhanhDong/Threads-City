@extends('layouts.public')
@php($pageTitle = ($kind === 'followers' ? 'Người theo dõi ' : 'Đang theo dõi · ').$user->name)
@section('content')
<h1>{{ $pageTitle }}</h1>
@include('profile.connection-items')
@if($people->isEmpty())<p>Chưa có tài khoản nào.</p>@endif
{{ $people->links() }}
@endsection
