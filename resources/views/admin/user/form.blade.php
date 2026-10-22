@php
    $selectedRole = old('role', $user->exists ? \App\Support\AccountRoles::of($user) : 'student');
    $profile = $user->exists ? match(\App\Support\AccountRoles::of($user)) { 'teacher' => $user->teacherProfile, 'center' => $user->center, 'student' => $user->studentProfile, default => null } : null;
@endphp
<div class="card p-4 mb-3"><div class="row g-3">
    <div class="col-md-6"><label for="name" class="form-label">Họ tên / Tên trung tâm *</label><input id="name" name="name" class="form-control" maxlength="150" required value="{{ old('name', $user->name) }}"></div>
    <div class="col-md-6"><label for="role" class="form-label">Vai trò *</label><select id="role" name="role" class="form-select" required>@foreach($roles as $value => $label)<option value="{{ $value }}" @selected($selectedRole === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-6"><label for="email" class="form-label">Email</label><input type="email" id="email" name="email" class="form-control" maxlength="191" value="{{ old('email', $user->email) }}"><small class="text-muted">Cần có ít nhất email hoặc số điện thoại. Thay email sẽ cần xác thực lại.</small></div>
    <div class="col-md-6"><label for="phone" class="form-label">Số điện thoại</label><input id="phone" name="phone" class="form-control" maxlength="20" value="{{ old('phone', $user->phone) }}" placeholder="0981234567 hoặc +84981234567"></div>
    <div class="col-md-6"><label for="password" class="form-label">Mật khẩu {{ $user->exists ? '(để trống nếu không đổi)' : '*' }}</label><input type="password" id="password" name="password" class="form-control" minlength="8" maxlength="128" autocomplete="new-password" @required(!$user->exists)></div>
    <div class="col-md-6"><label for="password_confirmation" class="form-label">Nhập lại mật khẩu</label><input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password"></div>
    <div class="col-md-6"><label for="status" class="form-label">Trạng thái *</label><select id="status" name="status" class="form-select"><option value="active" @selected(old('status', $user->status) === 'active')>Hoạt động</option><option value="blocked" @selected(old('status', $user->status) === 'blocked')>Đã khóa</option></select></div>
    <div class="col-md-6"><label for="blocked_reason" class="form-label">Lý do khóa (bắt buộc khi khóa)</label><textarea id="blocked_reason" name="blocked_reason" class="form-control" maxlength="2000">{{ old('blocked_reason', $user->blocked_reason) }}</textarea></div>
    <div class="col-12"><label for="avatar" class="form-label">Ảnh đại diện</label><input type="file" id="avatar" name="avatar" class="form-control" accept="image/jpeg,image/png,image/webp"><small class="text-muted">JPG, PNG, WebP · Tối đa 2 MB.</small>@if($user->avatar_url)<div class="mt-2"><img src="{{ $user->avatar_url }}" width="80" height="80" style="object-fit:cover" alt="Ảnh đại diện hiện tại"></div>@endif</div>
</div></div>
<div class="card p-4 mb-3" data-profile-fields><h2 class="h5 mb-3">Thông tin hồ sơ</h2><div class="row g-3">
    <div class="col-md-6" data-roles="student teacher"><label for="date_of_birth" class="form-label">Ngày sinh</label><input type="date" id="date_of_birth" name="date_of_birth" class="form-control" max="{{ now()->toDateString() }}" value="{{ old('date_of_birth', $profile?->date_of_birth?->format('Y-m-d') ?? $user->birthday?->format('Y-m-d')) }}"></div>
    <div class="col-md-6" data-roles="teacher"><label for="headline" class="form-label">Tiêu đề giới thiệu</label><input id="headline" name="headline" class="form-control" maxlength="200" value="{{ old('headline', $profile?->headline) }}"></div>
    <div class="col-md-6" data-roles="teacher"><label for="experience_years" class="form-label">Số năm kinh nghiệm</label><input type="number" id="experience_years" name="experience_years" class="form-control" min="0" max="80" value="{{ old('experience_years', $profile?->experience_years ?? 0) }}"></div>
    <div class="col-md-6" data-roles="center"><label for="center_name" class="form-label">Tên trung tâm đầy đủ</label><input id="center_name" name="center_name" class="form-control" maxlength="200" value="{{ old('center_name', $profile?->name) }}"></div>
    <div class="col-md-6" data-roles="center"><label for="founded_year" class="form-label">Năm thành lập</label><input type="number" id="founded_year" name="founded_year" class="form-control" min="1950" max="{{ now()->year }}" value="{{ old('founded_year', $profile?->founded_year) }}"></div>
    <div class="col-md-6" data-roles="center"><label for="registered_address" class="form-label">Địa chỉ đăng ký</label><input id="registered_address" name="registered_address" class="form-control" maxlength="255" value="{{ old('registered_address', $profile?->registered_address) }}"></div>
    <div class="col-12" data-roles="student teacher center"><label for="bio" class="form-label">Giới thiệu / Mục tiêu học tập</label><textarea id="bio" name="bio" class="form-control" rows="4" maxlength="5000">{{ old('bio', $profile?->bio) }}</textarea></div>
</div></div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const role = document.getElementById('role');
    function showProfileFields() {
        document.querySelectorAll('[data-roles]').forEach(function (group) {
            const visible = group.dataset.roles.split(' ').includes(role.value);
            group.hidden = !visible;
            group.querySelectorAll('input, textarea').forEach(function (input) { input.disabled = !visible; });
        });
        document.querySelector('[data-profile-fields]').hidden = !['student', 'teacher', 'center'].includes(role.value);
    }
    role.addEventListener('change', showProfileFields);
    showProfileFields();
});
</script>
@endpush
