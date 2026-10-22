@extends('admin.layout')
@section('title', 'Chỉnh sửa tài khoản - EduMatch')
@section('content')
<h1 class="mb-4">Chỉnh sửa tài khoản #{{ $user->id }}</h1>
<form method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    @include('admin.user.form')
    <button class="btn btn-primary">Lưu thay đổi</button><a class="btn btn-secondary" href="{{ route('admin.users.show', $user) }}">Quay lại</a>
</form>
@endsection
