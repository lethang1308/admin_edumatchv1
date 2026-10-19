@extends('admin.layout')
@section('title', 'Quản lý Môn học - EduMatch')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4"><h1>Quản lý Môn học</h1><a class="btn btn-primary" href="{{ route('admin.subjects.create') }}">Thêm mới môn học</a></div>
<form class="d-flex gap-2 mb-3" method="GET"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Tìm theo tên môn học"><button class="btn btn-outline-primary">Tìm kiếm</button></form>
<div class="card"><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>ID</th><th>Icon</th><th>Tên môn</th><th>Slug</th><th>Thứ tự</th><th>Trạng thái</th><th>Ngày tạo</th><th>Thao tác</th></tr></thead><tbody>
@forelse($subjects as $subject)<tr><td>{{ $subject->id }}</td><td>@if($subject->icon_path)<img src="{{ asset('storage/'.$subject->icon_path) }}" width="40" height="40" alt="Icon {{ $subject->name }}">@endif</td><td>{{ $subject->name }}</td><td>{{ $subject->slug }}</td><td>{{ $subject->sort_order }}</td><td><form method="POST" action="{{ route('admin.subjects.toggleStatus', $subject) }}">@csrf @method('PATCH')<button class="btn btn-sm {{ $subject->is_active ? 'btn-success' : 'btn-secondary' }}">{{ $subject->is_active ? 'Đang hiển thị' : 'Đã tắt' }}</button></form></td><td>{{ $subject->created_at?->format('d/m/Y') }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.subjects.edit', $subject) }}">Sửa</a><form class="d-inline" method="POST" action="{{ route('admin.subjects.destroy', $subject) }}" onsubmit="return confirm('Xóa môn học này?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Xóa</button></form></td></tr>
@empty<tr><td colspan="8" class="text-center p-4">Chưa có môn học phù hợp.</td></tr>@endforelse
</tbody></table></div></div>{{ $subjects->links('pagination::bootstrap-5') }}
@endsection
