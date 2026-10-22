@extends('admin.layout')
@section('title', 'Thêm tài khoản - EduMatch')
@section('content')
<h1 class="mb-4">Thêm tài khoản</h1>
<form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data">
    @csrf
    @include('admin.user.form')
    <button class="btn btn-primary">Tạo tài khoản</button><a class="btn btn-secondary" href="{{ route('admin.users.index') }}">Quay lại</a>
</form>
@endsection
