@extends('admin.layout')
@section('title', 'Quản lý Cấp lớp - EduMatch')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4"><h1>Quản lý Cấp lớp</h1><a class="btn btn-primary" href="{{ route('admin.education-levels.create') }}">Thêm cấp lớp</a></div>
<form class="d-flex gap-2 mb-3" method="GET"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Tìm cấp lớp"><button class="btn btn-outline-primary">Tìm kiếm</button></form>
<div class="card table-responsive"><table class="table table-striped"><thead><tr><th>ID</th><th>Mã code</th><th>Tên cấp lớp</th><th>Thứ tự</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
@forelse($levels as $level)<tr><td>{{ $level->id }}</td><td>{{ $level->code }}</td><td>{{ $level->name }}</td><td>{{ $level->sort_order }}</td><td><form method="POST" action="{{ route('admin.education-levels.toggleStatus', $level) }}">@csrf @method('PATCH')<button class="btn btn-sm {{ $level->is_active ? 'btn-success' : 'btn-secondary' }}">{{ $level->is_active ? 'Đang hiển thị' : 'Đã tắt' }}</button></form></td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.education-levels.edit', $level) }}">Sửa</a><form class="d-inline" method="POST" action="{{ route('admin.education-levels.destroy', $level) }}" onsubmit="return confirm('Xóa cấp lớp này?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Xóa</button></form></td></tr>@empty<tr><td colspan="6">Chưa có cấp lớp.</td></tr>@endforelse
</tbody></table></div>{{ $levels->links('pagination::bootstrap-5') }}
@endsection
