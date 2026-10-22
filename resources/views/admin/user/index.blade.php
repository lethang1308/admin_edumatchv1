@extends('admin.layout')
@section('title', 'Quản lý tài khoản - EduMatch')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1>Quản lý tài khoản</h1><p class="text-muted mb-0">Thông tin tài khoản, vai trò và hồ sơ EduMatch.</p></div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Thêm tài khoản</a>
</div>
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-5"><label for="q" class="form-label">Tìm kiếm</label><input id="q" name="q" class="form-control" value="{{ request('q') }}" placeholder="Tên, email, số điện thoại hoặc ID"></div>
    <div class="col-md-3"><label for="role-filter" class="form-label">Vai trò</label><select id="role-filter" name="role" class="form-select"><option value="">Tất cả vai trò</option>@foreach($roles as $value => $label)<option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-2"><label for="status-filter" class="form-label">Trạng thái</label><select id="status-filter" name="status" class="form-select"><option value="">Tất cả</option><option value="active" @selected(request('status') === 'active')>Hoạt động</option><option value="blocked" @selected(request('status') === 'blocked')>Đã khóa</option></select></div>
    <div class="col-md-2 d-flex align-items-end gap-2"><button class="btn btn-primary">Lọc</button><a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">Đặt lại</a></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-striped align-middle">
    <thead><tr><th>ID</th><th>Tài khoản</th><th>Liên hệ</th><th>Vai trò</th><th>Trạng thái</th><th>Xác minh</th><th>Ngày đăng ký</th><th>Thao tác</th></tr></thead>
    <tbody>
    @forelse($users as $user)
        @php
            $role = \App\Support\AccountRoles::of($user);
            $profile = $role === 'teacher' ? $user->teacherProfile : ($role === 'center' ? $user->center : $user->studentProfile);
        @endphp
        <tr>
            <td>{{ $user->id }}</td>
            <td><div class="d-flex align-items-center gap-2">
                @if($user->avatar_url)<img src="{{ $user->avatar_url }}" width="44" height="44" style="object-fit:cover;border-radius:50%" alt="Ảnh {{ $user->name }}">
                @else<span class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style="width:44px;height:44px" aria-hidden="true"><i class="fas fa-user text-muted"></i></span>@endif
                <div><a href="{{ route('admin.users.show', $user) }}" class="fw-bold">{{ $user->name ?: ($profile?->full_name ?? $profile?->name ?? 'Chưa cập nhật') }}</a>
                    @if($role === 'center' && $profile?->name !== $user->name)<small class="d-block text-muted">{{ $profile?->name }}</small>@endif
                </div>
            </div></td>
            <td><div>{{ $user->email ?: 'Chưa có email' }}</div><small class="text-muted">{{ $user->phone ?: 'Chưa có số điện thoại' }}</small></td>
            <td><span class="badge bg-primary">{{ $roles[$role] ?? $role }}</span></td>
            <td><span class="badge {{ $user->status === 'active' ? 'bg-success' : 'bg-danger' }}">{{ $user->status === 'active' ? 'Hoạt động' : 'Đã khóa' }}</span></td>
            <td><small class="d-block">Email: {{ $user->email_verified_at ? 'Đã xác thực' : 'Chưa xác thực' }}</small>
                @if(in_array($role, ['teacher', 'center']))<small class="d-block">Hồ sơ: {{ ['unverified'=>'Chưa xác minh', 'pending'=>'Chờ duyệt', 'verified'=>'Đã xác minh', 'rejected'=>'Bị từ chối'][$profile?->verification_status ?? 'unverified'] ?? 'Chưa xác minh' }}</small>@endif
            </td>
            <td class="text-nowrap">{{ $user->created_at?->format('d/m/Y H:i') }}</td>
            <td><div class="d-flex gap-1"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.users.show', $user) }}">Chi tiết</a><a class="btn btn-sm btn-warning" href="{{ route('admin.users.edit', $user) }}">Sửa</a>
                @unless($user->is(auth()->user()))<form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Xóa tài khoản và thu hồi đăng nhập? Email và số điện thoại sẽ dùng lại được cho tài khoản mới.')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">Xóa</button></form>@endunless
            </div></td>
        </tr>
    @empty<tr><td colspan="8" class="text-center p-4">Không tìm thấy tài khoản phù hợp.</td></tr>@endforelse
    </tbody>
</table></div></div>
<p class="text-muted">Tổng cộng {{ $users->total() }} tài khoản.</p>
{{ $users->links('pagination::bootstrap-5') }}
@endsection
