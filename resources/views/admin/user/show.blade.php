@extends('admin.layout')
@section('title', 'Chi tiết tài khoản - EduMatch')
@section('content')
@php
    $role = \App\Support\AccountRoles::of($user);
    $profile = match($role) { 'teacher' => $user->teacherProfile, 'center' => $user->center, 'student' => $user->studentProfile, default => null };
    $verificationLabels = ['unverified'=>'Chưa xác minh', 'pending'=>'Chờ duyệt', 'verified'=>'Đã xác minh', 'rejected'=>'Bị từ chối'];
    $area = $profile ? collect([$profile->ward?->name, $profile->district?->name, $profile->province?->name])->filter()->join(', ') : '';
    $accountFields = [
        'Mã tài khoản' => '#'.$user->id,
        'Họ tên / Tên tài khoản' => $user->name,
        'Vai trò' => $roles[$role] ?? $role,
        'Nhóm quyền' => $user->roles->pluck('name')->join(', '),
        'Email đăng nhập' => $user->email,
        'Số điện thoại đăng nhập' => $user->phone,
        'Trạng thái' => $user->status === 'active' ? 'Hoạt động' : 'Đã khóa',
        'Lý do khóa' => $user->blocked_reason,
        'Thời điểm khóa' => $user->blocked_at?->format('d/m/Y H:i'),
        'Xác thực email' => $user->email_verified_at?->format('d/m/Y H:i') ?? 'Chưa xác thực',
        'Xác thực số điện thoại' => $user->phone_verified_at?->format('d/m/Y H:i') ?? 'Chưa xác thực',
        'Ngày đăng ký' => $user->created_at?->format('d/m/Y H:i'),
        'Cập nhật gần nhất' => $user->updated_at?->format('d/m/Y H:i'),
        'Đăng nhập gần nhất' => $user->last_login_at?->format('d/m/Y H:i'),
    ];
    $profileFields = [];
    if ($profile) {
        $profileFields = $user->role === 'center' ? [
            'Tên trung tâm' => $profile->name, 'Đường dẫn trung tâm' => $profile->slug,
            'Năm thành lập' => $profile->founded_year, 'Hotline' => $profile->hotline,
            'Email liên hệ' => $profile->contact_email, 'Địa chỉ đăng ký' => $profile->registered_address,
        ] : ['Họ tên trong hồ sơ' => $profile->full_name, 'Ngày sinh' => $profile->date_of_birth?->format('d/m/Y')];
        $profileFields += ['Khu vực' => $area, 'Giới thiệu / Mục tiêu học tập' => $profile->bio];
        if ($user->role === 'teacher') {
            $profileFields += ['Tiêu đề giới thiệu' => $profile->headline, 'Số năm kinh nghiệm' => $profile->experience_years,
                'Công khai số điện thoại' => $profile->show_phone ? 'Có' : 'Không'];
        }
        if (in_array($user->role, ['teacher', 'center'])) {
            $profileFields += ['Xác minh hồ sơ' => $verificationLabels[$profile->verification_status] ?? $profile->verification_status,
                'Ngày xác minh' => $profile->verified_at?->format('d/m/Y H:i'),
                'Điểm đánh giá' => $profile->rating_avg, 'Số đánh giá' => $profile->review_count, 'Lượt tin tưởng' => $profile->trust_count];
        }
        $profileFields += ['Ngày tạo hồ sơ' => $profile->created_at?->format('d/m/Y H:i'), 'Cập nhật hồ sơ' => $profile->updated_at?->format('d/m/Y H:i')];
    }
@endphp
<div class="d-flex justify-content-between align-items-center mb-4"><h1>Chi tiết tài khoản #{{ $user->id }}</h1><div><a class="btn btn-primary" href="{{ route('admin.users.edit', $user) }}">Chỉnh sửa</a> <a class="btn btn-secondary" href="{{ route('admin.users.index') }}">Danh sách tài khoản</a></div></div>
<div class="row"><div class="col-lg-6"><div class="card p-4">
    <h2 class="h4">Thông tin tài khoản</h2>
    @if($user->avatar_url)<img class="rounded-circle mb-3" src="{{ $user->avatar_url }}" width="90" height="90" style="object-fit:cover" alt="Ảnh đại diện {{ $user->name }}">@endif
    <dl class="row mb-0">@foreach($accountFields as $label => $value)<dt class="col-sm-5 mb-2">{{ $label }}</dt><dd class="col-sm-7 mb-2 text-break">{{ $value === null || $value === '' ? 'Chưa cập nhật' : $value }}</dd>@endforeach</dl>
</div></div><div class="col-lg-6"><div class="card p-4"><h2 class="h4">Hồ sơ {{ $roles[$role] ?? $role }}</h2>
    @if($profile)
        <dl class="row mb-0">@foreach($profileFields as $label => $value)<dt class="col-sm-5 mb-2">{{ $label }}</dt><dd class="col-sm-7 mb-2 text-break" style="white-space:pre-line">{{ $value === null || $value === '' ? 'Chưa cập nhật' : $value }}</dd>@endforeach</dl>
        @if($user->role === 'center')
            @foreach(['logo_path'=>'Logo trung tâm', 'cover_path'=>'Ảnh bìa trung tâm'] as $field => $label)
                @if($profile->{$field})<p class="fw-bold mt-3">{{ $label }}</p><img src="{{ preg_match('#^https?://#', $profile->{$field}) ? $profile->{$field} : asset('storage/'.$profile->{$field}) }}" class="img-fluid rounded" style="max-height:180px" alt="{{ $label }}">@endif
            @endforeach
        @endif
    @else<p class="text-muted">{{ in_array($role, ['admin', 'super_admin']) ? 'Tài khoản quản trị không có hồ sơ học tập / giảng dạy.' : 'Tài khoản chưa cập nhật hồ sơ.' }}</p>@endif
</div></div></div>
@if($user->role === 'teacher')
<div class="card p-4"><h2 class="h4">Môn giảng dạy</h2><ul>@forelse($user->subjects as $subject)<li>{{ $subject->name }}{{ $subject->pivot->level_note ? ' — '.$subject->pivot->level_note : '' }}</li>@empty<li>Chưa cập nhật môn giảng dạy.</li>@endforelse</ul>
<h2 class="h4">Bằng cấp / Chứng chỉ</h2><div class="table-responsive"><table class="table"><thead><tr><th>Tên</th><th>Đơn vị cấp</th><th>Ngày cấp</th><th>Hết hạn</th><th>Xác minh</th><th>Công khai</th></tr></thead><tbody>@forelse($user->teacherQualifications as $qualification)<tr><td>{{ $qualification->title }}</td><td>{{ $qualification->issuer ?: 'Chưa cập nhật' }}</td><td>{{ $qualification->issued_on?->format('d/m/Y') ?: 'Chưa cập nhật' }}</td><td>{{ $qualification->expires_on?->format('d/m/Y') ?: 'Không có' }}</td><td>{{ $qualification->is_verified ? 'Đã xác minh' : 'Chưa xác minh' }}</td><td>{{ $qualification->is_public ? 'Có' : 'Không' }}</td></tr>@empty<tr><td colspan="6">Chưa cập nhật bằng cấp / chứng chỉ.</td></tr>@endforelse</tbody></table></div></div>
@endif
@if($user->role === 'center' && $user->center)
<div class="card p-4"><h2 class="h4">Chi nhánh trung tâm</h2><div class="table-responsive"><table class="table"><thead><tr><th>Tên chi nhánh</th><th>Địa chỉ</th><th>Trụ sở chính</th><th>Trạng thái</th></tr></thead><tbody>@forelse($user->center->branches as $branch)<tr><td>{{ $branch->name }}</td><td>{{ $branch->address_line }}</td><td>{{ $branch->is_main ? 'Có' : 'Không' }}</td><td>{{ $branch->status === 'active' ? 'Hoạt động' : 'Đã tắt' }}</td></tr>@empty<tr><td colspan="4">Chưa có chi nhánh.</td></tr>@endforelse</tbody></table></div></div>
@endif
@if(in_array($user->role, ['teacher', 'center']))
<div class="card p-4"><h2 class="h4">Lịch sử hồ sơ xác minh</h2><div class="table-responsive"><table class="table"><thead><tr><th>Hồ sơ</th><th>Ngày nộp</th><th>Trạng thái</th><th>Tài liệu</th><th>Người duyệt</th><th>Kết quả</th></tr></thead><tbody>
@forelse($user->submittedVerificationRequests->sortByDesc('id') as $verification)
<tr><td><a href="{{ route('admin.verifications.show', $verification) }}">#{{ $verification->id }}</a></td><td>{{ $verification->submitted_at?->format('d/m/Y H:i') }}</td><td>{{ ['pending'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Từ chối'][$verification->status] ?? $verification->status }}</td><td>@foreach($verification->verificationDocuments as $document)<div>{{ $document->document_name }}{{ $document->issuing_authority ? ' — '.$document->issuing_authority : '' }}{{ $document->issued_year ? ' ('.$document->issued_year.')' : '' }}</div>@endforeach</td><td>{{ $verification->reviewer?->name ?? 'Chưa xử lý' }}<small class="d-block">{{ $verification->reviewed_at?->format('d/m/Y H:i') }}</small></td><td>{{ $verification->rejection_reason ?: '—' }}</td></tr>
@empty<tr><td colspan="6">Chưa nộp hồ sơ xác minh.</td></tr>@endforelse
</tbody></table></div></div>
@endif
@endsection
