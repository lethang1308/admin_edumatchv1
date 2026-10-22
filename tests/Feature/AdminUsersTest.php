<?php

namespace Tests\Feature;

use App\Mail\RegistrationOtpCode;
use App\Models\AdministrativeArea;
use App\Models\AuthVerificationToken;
use App\Models\Subject;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\IsolatedMysqlTestCase;

class AdminUsersTest extends IsolatedMysqlTestCase
{
    private function account(string $role = 'student', array $attributes = []): User
    {
        return User::create($attributes + ['name' => 'Tài khoản '.$role, 'email' => uniqid().'@gmail.com',
            'password' => 'Password@123', 'role' => $role]);
    }

    private function admin(string $role = 'super_admin'): User
    {
        $admin = $this->account($role);
        $admin->assignRole(Role::findOrCreate($role, 'web'));
        $this->actingAs($admin, 'web');

        return $admin;
    }

    private function data(User $user, array $attributes = []): array
    {
        return $attributes + ['name' => $user->name, 'email' => $user->email, 'phone' => $user->phone,
            'role' => $user->role, 'status' => 'active'];
    }

    public function test_list_uses_current_name_avatar_contacts_roles_and_status_instead_of_legacy_fields(): void
    {
        $this->admin();
        $student = $this->account('student', ['name' => 'Học viên hiện tại', 'full_name' => 'Tên cũ không dùng',
            'avatar_path' => 'avatars/current.jpg', 'phone' => '+84981234567', 'email_verified_at' => now()]);
        $teacher = $this->account('teacher');
        $teacher->teacherProfile()->create(['full_name' => $teacher->name, 'verification_status' => 'pending']);
        $center = $this->account('center');
        $center->center()->create(['name' => 'Trung tâm API', 'slug' => 'trung-tam-api', 'verification_status' => 'verified']);
        $legacyAdmin = $this->account('student', ['name' => 'Quản trị cũ']);
        $legacyAdmin->assignRole(Role::findOrCreate('admin', 'web'));
        $rawSuperAdmin = $this->account('super_admin');
        $rawSuperAdmin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->assertSame('super_admin', \App\Support\AccountRoles::of($rawSuperAdmin));
        $deleted = $this->account('student', ['name' => 'Đã xóa khỏi danh sách']);
        $deleted->delete();
        $this->get('/admin/users')->assertOk()->assertSee($student->name)->assertSee($student->email)
            ->assertSee('+84981234567')->assertSee('avatars/current.jpg')->assertSee('Học viên')
            ->assertSee('Giáo viên')->assertSee('Trung tâm')->assertSee('Quản trị viên')->assertSee('Super Admin')
            ->assertSee('Chờ duyệt')->assertSee('Đã xác minh')->assertDontSee('Số dư tài khoản')
            ->assertDontSee('Giới tính')->assertDontSee('Tên cũ không dùng')->assertDontSee($deleted->name);
    }

    public function test_detail_displays_student_profile_and_location(): void
    {
        $this->admin();
        $user = $this->account();
        $province = AdministrativeArea::create(['code' => 'QA-P', 'name' => 'Tỉnh thử nghiệm', 'level' => 'province']);
        $user->studentProfile()->create(['full_name' => 'Họ tên hồ sơ', 'date_of_birth' => '2005-08-13',
            'province_id' => $province->id, 'bio' => 'Mục tiêu học tập từ database']);
        $this->get('/admin/users/'.$user->id)->assertOk()->assertSee('13/08/2005')
            ->assertSee('Họ tên hồ sơ')->assertSee('Mục tiêu học tập từ database')->assertSee('Tỉnh thử nghiệm')
            ->assertDontSee($user->password);
    }

    public function test_detail_displays_teacher_qualifications_subjects_and_verification_history(): void
    {
        $admin = $this->admin();
        $user = $this->account('teacher');
        $user->teacherProfile()->create(['full_name' => $user->name, 'experience_years' => 8,
            'headline' => 'Giáo viên tiếng Anh', 'verification_status' => 'verified', 'verified_at' => now()]);
        $subject = Subject::create(['name' => 'Tiếng Anh thực tế', 'slug' => 'tieng-anh-thuc-te']);
        $user->subjects()->attach($subject, ['level_note' => 'Lớp 12']);
        $user->teacherQualifications()->create(['title' => 'IELTS 8.0', 'issuer' => 'British Council', 'issued_on' => '2025-01-01', 'is_verified' => true]);
        $request = VerificationRequest::create(['teacher_user_id' => $user->id, 'submitted_by_user_id' => $user->id,
            'status' => 'approved', 'reviewed_by' => $admin->id, 'submitted_at' => now(), 'reviewed_at' => now()]);
        $this->get('/admin/users/'.$user->id)->assertOk()->assertSee('Giáo viên tiếng Anh')->assertSee('Tiếng Anh thực tế')
            ->assertSee('IELTS 8.0')->assertSee('British Council')->assertSee('Lớp 12')
            ->assertSee(route('admin.verifications.show', $request), false)->assertSee('Đã duyệt');
    }

    public function test_center_detail_edit_and_accounts_without_profiles_render(): void
    {
        $this->admin();
        $user = $this->account('center');
        $center = $user->center()->create(['name' => 'Trung tâm Giáo dục thật', 'slug' => 'giao-duc-that', 'founded_year' => 2020,
            'hotline' => '0901234567', 'contact_email' => 'contact@example.com', 'registered_address' => '123 Đường Thật']);
        $center->branches()->create(['name' => 'Chi nhánh chính', 'address_line' => '456 Đường Thật', 'is_main' => true]);
        $this->get('/admin/users/'.$user->id)->assertOk()->assertSee('Trung tâm Giáo dục thật')->assertSee('contact@example.com')
            ->assertSee('123 Đường Thật')->assertSee('Chi nhánh chính')->assertSee('456 Đường Thật');
        $this->get('/admin/users/'.$user->id.'/edit')->assertOk()->assertSee('2020')->assertSee('Trung tâm Giáo dục thật');
        $this->get('/admin/users/'.$this->account('teacher')->id)->assertOk()->assertSee('Tài khoản chưa cập nhật hồ sơ.');
        $this->get('/admin/users/create')->assertOk()->assertDontSee('Số dư tài khoản');
    }

    public function test_search_role_and_status_filters_and_pagination(): void
    {
        $this->admin();
        $legacy = $this->account('student', ['name' => 'Quản trị legacy']);
        $legacy->assignRole(Role::findOrCreate('admin', 'web'));
        $student = $this->account('student', ['name' => 'Học viên cần tìm', 'phone' => '+84991234567', 'status' => 'blocked']);
        $this->get('/admin/users?role=student')->assertOk()->assertSee($student->name)->assertDontSee($legacy->name);
        $this->get('/admin/users?role=admin')->assertOk()->assertSee($legacy->name)->assertDontSee($student->name);
        $this->get('/admin/users?status=blocked&q=991234567')->assertOk()->assertSee($student->name)->assertDontSee($legacy->name);
        $this->get('/admin/users?q=0991234567')->assertOk()->assertSee($student->email);
        $this->get('/admin/users?q='.$student->id)->assertOk()->assertSee($student->email);
        for ($i = 0; $i < 16; $i++) {
            $this->account();
        }
        $this->get('/admin/users?role=student')->assertOk()->assertViewHas('users', fn ($users) => $users->count() === 15 && $users->total() === 17);
    }

    public function test_only_administrators_can_manage_accounts(): void
    {
        $this->get('/admin/users')->assertRedirect();
        $user = $this->account('teacher');
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->post('/admin/users', [])->assertForbidden();
        $this->delete('/admin/users/'.$user->id)->assertForbidden();
        $this->admin('admin');
        $this->get('/admin/users')->assertOk();
    }

    public function test_create_saves_profile_avatar_and_matching_business_role_without_legacy_mass_assignment(): void
    {
        Storage::fake('public');
        $this->admin();
        $this->post('/admin/users', ['name' => 'Giáo viên mới', 'email' => ' NEW.TEACHER@GMAIL.COM ', 'phone' => '098 123.4567',
            'role' => 'teacher', 'status' => 'active', 'password' => 'Password@123', 'password_confirmation' => 'Password@123',
            'date_of_birth' => '1990-05-15', 'headline' => 'Dạy Toán', 'experience_years' => 5, 'bio' => 'Giới thiệu thật',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'), 'balance' => 999999, 'email_verified_at' => now()->toDateTimeString()])
            ->assertRedirect();
        $user = User::where('email', 'new.teacher@gmail.com')->firstOrFail();
        $this->assertSame('+84981234567', $user->phone);
        $this->assertSame('teacher', $user->role);
        $this->assertTrue($user->hasRole('teacher'));
        $this->assertTrue(Hash::check('Password@123', $user->password));
        $this->assertNull($user->email_verified_at);
        $this->assertEquals(0, $user->balance);
        $this->assertSame('Dạy Toán', $user->teacherProfile->headline);
        $this->assertSame('1990-05-15', $user->teacherProfile->date_of_birth->format('Y-m-d'));
        Storage::disk('public')->assertExists($user->avatar_path);
    }

    public function test_edit_updates_correct_profile_preserves_password_and_resets_changed_contact_verification(): void
    {
        $this->admin();
        $user = $this->account('student', ['phone' => '+84981234567', 'email_verified_at' => now(), 'phone_verified_at' => now()]);
        $user->studentProfile()->create(['full_name' => $user->name, 'bio' => 'Giữ giới thiệu này', 'date_of_birth' => '2001-01-01']);
        $password = $user->password;
        $this->put('/admin/users/'.$user->id, $this->data($user, ['name' => 'Họ tên mới', 'email' => 'CHANGED@GMAIL.COM', 'password' => '']))->assertRedirect();
        $user->refresh();
        $this->assertSame('Họ tên mới', $user->studentProfile->full_name);
        $this->assertSame('Giữ giới thiệu này', $user->studentProfile->bio);
        $this->assertSame($password, $user->password);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertSame('changed@gmail.com', $user->email);
    }

    public function test_role_changes_are_synchronized_and_blocking_revokes_access_tokens(): void
    {
        $this->admin();
        $user = $this->account();
        $user->assignRole(Role::findOrCreate('student', 'web'));
        $user->createToken('old-session');
        $this->put('/admin/users/'.$user->id, $this->data($user, ['role' => 'center', 'status' => 'blocked',
            'blocked_reason' => 'Tài khoản cần kiểm tra', 'founded_year' => 2020, 'center_name' => 'Tên trung tâm đầy đủ']))->assertRedirect();
        $user->refresh();
        $this->assertSame('center', $user->role);
        $this->assertTrue($user->hasRole('center'));
        $this->assertFalse($user->hasRole('student'));
        $this->assertSame('Tên trung tâm đầy đủ', $user->center->name);
        $this->assertSame(0, $user->tokens()->count());
        $this->assertNotNull($user->blocked_at);
    }

    public function test_duplicate_contacts_invalid_roles_and_future_birthdays_are_rejected(): void
    {
        $this->admin();
        $existing = $this->account('student', ['phone' => '0981234567']);
        $user = $this->account();
        $this->putJson('/admin/users/'.$user->id, $this->data($user, ['phone' => '+84981234567', 'email' => $existing->email,
            'role' => 'invalid', 'date_of_birth' => now()->addDay()->toDateString()]))
            ->assertUnprocessable()->assertJsonValidationErrors(['phone', 'email', 'role', 'date_of_birth']);
        $this->assertSame('student', $user->fresh()->role);
    }

    public function test_admin_cannot_delete_lock_or_demote_own_account(): void
    {
        $admin = $this->admin();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->put('/admin/users/'.$admin->id, $this->data($admin, ['name' => 'Quản trị đã cập nhật']))->assertRedirect();
        $this->assertTrue($admin->fresh()->hasAllRoles(['admin', 'super_admin']));
        $this->deleteJson('/admin/users/'.$admin->id)->assertUnprocessable()->assertJsonValidationErrors('user');
        $this->putJson('/admin/users/'.$admin->id, $this->data($admin, ['role' => 'student']))->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->putJson('/admin/users/'.$admin->id, $this->data($admin, ['status' => 'blocked', 'blocked_reason' => 'Tự khóa']))->assertUnprocessable();
        $this->assertFalse($admin->fresh()->trashed());
    }

    public function test_deleted_accounts_release_both_contacts_and_all_roles_can_register_again_with_fresh_otp(): void
    {
        Mail::fake();
        config(['registration_otp.debug' => true]);
        $admin = $this->admin();
        foreach (['student', 'teacher', 'center'] as $index => $role) {
            $email = 'edumatch.deleted.'.$role.'@gmail.com';
            $phone = '098123456'.$index;
            $user = $this->account($role, ['email' => $email, 'phone' => '+84'.substr($phone, 1)]);
            $oldToken = $user->createToken('old')->plainTextToken;
            $oldOtp = AuthVerificationToken::create(['user_id' => $user->id, 'destination' => $email, 'channel' => 'email',
                'purpose' => 'reset_password', 'token_hash' => Hash::make('123456'), 'expires_at' => now()->addMinutes(10)]);
            $this->actingAs($admin, 'web')->delete('/admin/users/'.$user->id)->assertRedirect('/admin/users');
            $old = User::withTrashed()->findOrFail($user->id);
            $this->assertTrue($old->trashed());
            $this->assertStringEndsWith('@deleted.invalid', $old->email);
            $this->assertNull($old->phone);
            $this->assertSame(0, $old->tokens()->count());
            $this->assertNull($oldOtp->fresh());
            $this->get('/admin/users/'.$user->id)->assertNotFound();
            Auth::guard('web')->logout();
            $this->app['auth']->forgetGuards();
            $this->withToken($oldToken)->getJson('/api/auth/me')->assertUnauthorized();
            $this->postJson('/api/auth/login', ['identifier' => $email, 'password' => 'Password@123'])->assertUnauthorized();
            $this->withHeaders(['Authorization' => ''])->postJson('/api/auth/register', ['name' => 'Tài khoản mới',
                'email' => $email, 'phone' => $phone, 'role' => $role, 'date_of_birth' => '2000-01-01', 'show_phone' => false,
                'founded_year' => 2020, 'password' => 'Password@123', 'password_confirmation' => 'Password@123', 'otp' => '123456'])
                ->assertUnprocessable()->assertJsonValidationErrors('otp');
            $otp = $this->postJson('/api/auth/register/send-otp', ['email' => $email, 'phone' => $phone])->assertOk()->json('debug_otp');
            $response = $this->postJson('/api/auth/register', ['name' => 'Tài khoản mới', 'email' => $email, 'phone' => $phone,
                'role' => $role, 'date_of_birth' => '2000-01-01', 'show_phone' => false, 'founded_year' => 2020,
                'password' => 'Password@123', 'password_confirmation' => 'Password@123', 'otp' => $otp])->assertCreated()->assertJsonPath('user.role', $role);
            $this->assertNotSame($user->id, $response->json('user.id'));
            $this->assertNotNull($response->json('user.email_verified_at'));
        }
        Mail::assertSent(RegistrationOtpCode::class, 3);
    }

    public function test_migration_repairs_previously_soft_deleted_accounts_without_changing_active_accounts(): void
    {
        $old = $this->account('teacher', ['email' => 'old.deleted@gmail.com', 'phone' => '0981234567']);
        $old->teacherProfile()->create(['full_name' => 'Hồ sơ lịch sử']);
        $old->createToken('legacy-token');
        $old->delete();
        $active = $this->account('student', ['phone' => '+84981234568']);
        $activeToken = $active->createToken('keep-active');
        $before = $active->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_10_22_000001_release_deleted_user_identifiers.php');
        $migration->up();
        $migration->up();
        $deleted = User::withTrashed()->findOrFail($old->id);
        $this->assertStringEndsWith('@deleted.invalid', $deleted->email);
        $this->assertNull($deleted->phone);
        $this->assertSame(0, $deleted->tokens()->count());
        $this->assertSame('Hồ sơ lịch sử', $deleted->teacherProfile->full_name);
        $this->assertSame($before, $active->fresh()->getAttributes());
        $this->assertNotNull($activeToken->accessToken->fresh());
        $this->assertFalse(DB::table('users')->where('email', 'old.deleted@gmail.com')->exists());
    }
}
