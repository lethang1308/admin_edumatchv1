@extends('admin.layout')
@section('title', 'Cấp lớp - EduMatch')
@section('content')
<h1>{{ $level->exists ? 'Chỉnh sửa cấp lớp' : 'Thêm cấp lớp' }}</h1><form class="card p-4" method="POST" action="{{ $level->exists ? route('admin.education-levels.update', $level) : route('admin.education-levels.store') }}">@csrf @if($level->exists) @method('PUT') @endif
<div class="mb-3"><label class="form-label" for="code">Mã cấp lớp</label><input class="form-control" name="code" id="code" value="{{ old('code', $level->code) }}" maxlength="30" pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="grade-1, dai-hoc" required></div>
<div class="mb-3"><label class="form-label" for="name">Tên hiển thị</label><input class="form-control" name="name" id="name" value="{{ old('name', $level->name) }}" maxlength="100" required></div>
<div class="mb-3"><label class="form-label" for="sort_order">Thứ tự</label><input class="form-control" name="sort_order" id="sort_order" type="number" value="{{ old('sort_order', $level->sort_order ?? 0) }}" min="0" max="1000000" required></div>
<input type="hidden" name="is_active" value="0"><label class="mb-4"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $level->is_active ?? true))> Kích hoạt hiển thị</label>
<div><button class="btn btn-primary">Lưu cấp lớp</button> <a class="btn btn-secondary" href="{{ route('admin.education-levels.index') }}">Quay lại</a></div></form>
@endsection
