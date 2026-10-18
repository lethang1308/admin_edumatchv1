<?php

namespace Tests\Feature;

use App\Mail\PasswordResetCode;
use App\Models\AdministrativeArea;
use App\Models\AuthVerificationToken;
use App\Models\Center;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\AdministrativeAreaSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\SeedsRegistrationOtp;
use Tests\IsolatedMysqlTestCase;

class Phase1CompletionTest extends IsolatedMysqlTestCase
{
    use SeedsRegistrationOtp;

    private function api(string $method, string $path, array $data = [], ?string $token = null): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->json($method, $path, $data, $token ? ['Authorization' => 'Bearer '.$token] : []);
    }

    private function payload(array $data = []): array
    {
        $payload = array_replace([
            'name' => 'Nguyễn Văn A', 'email' => 'edumatch.phase1@gmail.com', 'phone' => '0981234567',
            'date_of_birth' => '1990-05-15', 'password' => 'Password@123', 'password_confirmation' => 'Password@123',
            'otp' => '123456',
        ], $data);
        $this->seedRegistrationOtp($payload['email']);

        return $payload;
    }

    private function account(string $role = 'student'): User
    {
        $user = User::create(['name' => 'Test '.$role, 'email' => $role.'@example.test', 'phone' => '+84981234567', 'password' => 'Password@123', 'role' => $role]);
        if ($role === 'student') {
            $user->studentProfile()->create(['full_name' => $user->name, 'date_of_birth' => '2002-05-15']);
        }

        return $user;
    }

    public function test_teacher_registration_creates_user_and_profile(): void
    {
        $response = $this->api('POST', '/api/auth/register', $this->payload([
            'role' => 'teacher', 'headline' => 'Cử nhân Sư phạm', 'bio' => 'Dạy học bằng thực hành',
            'experience_years' => 5, 'show_phone' => true, 'verification_status' => 'verified',
        ]))->assertCreated()->assertJsonPath('user.role', 'teacher')
            ->assertJsonPath('user.teacher_profile.headline', 'Cử nhân Sư phạm')
            ->assertJsonPath('user.teacher_profile.experience_years', 5)
            ->assertJsonPath('user.teacher_profile.date_of_birth', '1990-05-15')
            ->assertJsonPath('user.teacher_profile.show_phone', true)
            ->assertJsonPath('user.teacher_profile.verification_status', 'unverified');
        $user = User::firstOrFail();
        $this->assertTrue($user->hasRole('teacher'));
        $this->assertNull($user->studentProfile);
        $this->assertDatabaseCount('teacher_profiles', 1);
        $this->api('GET', '/api/auth/me', [], $response->json('token'))->assertOk()->assertJsonPath('user.teacher_profile.full_name', $user->name);
        $this->api('GET', '/api/profile', [], $response->json('token'))->assertOk()->assertJsonPath('user.role', 'teacher');
    }

    public function test_teacher_aliases_and_validation(): void
    {
        $data = $this->payload(['role' => 'teacher', 'qualification' => 'IELTS 8.0', 'biography' => 'Giới thiệu', 'experience' => '6 năm giảng dạy', 'isPhonePublic' => false]);
        $this->api('POST', '/api/auth/register', $data)->assertCreated()
            ->assertJsonPath('user.teacher_profile.headline', 'IELTS 8.0')->assertJsonPath('user.teacher_profile.experience_years', 6)
            ->assertJsonPath('user.teacher_profile.bio', 'Giới thiệu')->assertJsonPath('user.teacher_profile.show_phone', false);
        $this->api('POST', '/api/auth/register', $this->payload(['role' => 'teacher', 'experience_years' => 61, 'headline' => str_repeat('x', 201)]))
            ->assertUnprocessable()->assertJsonValidationErrors(['experience_years', 'headline']);
    }

    public function test_center_registration_creates_user_center_and_unique_slug(): void
    {
        $data = ['role' => 'center', 'centerName' => 'Trung tâm Ánh Dương', 'foundedYear' => '2018', 'centerDescription' => 'Trung tâm đào tạo'];
        $first = $this->api('POST', '/api/auth/register', $this->payload($data))->assertCreated()->assertJsonPath('user.center.founded_year', 2018)
            ->assertJsonPath('user.center.name', 'Trung tâm Ánh Dương')->assertJsonPath('user.center.verification_status', 'unverified');
        $center = Center::firstOrFail();
        $this->assertTrue(User::first()->hasRole('center'));
        $this->assertSame('+84981234567', $center->hotline);
        $center->delete();
        $second = $this->api('POST', '/api/auth/register', $this->payload($data + ['phone' => '0981234568', 'email' => 'edumatch.center2@gmail.com']))->assertCreated();
        $this->assertNotSame($first->json('user.center.slug'), $second->json('user.center.slug'));
        $this->assertDatabaseCount('student_profiles', 0);
        $this->api('GET', '/api/auth/me', [], $second->json('token'))->assertOk()->assertJsonPath('user.role', 'center');
    }

    public function test_center_long_names_and_invalid_years(): void
    {
        $name = str_repeat('A', 180);
        $this->api('POST', '/api/auth/register', $this->payload(['name' => $name, 'role' => 'center', 'founded_year' => 2020]))
            ->assertCreated()->assertJsonPath('user.center.name', $name);
        $this->assertSame(150, mb_strlen(User::first()->name));
        foreach ([1949, now()->year + 1] as $year) {
            $this->api('POST', '/api/auth/register', $this->payload(['role' => 'center', 'founded_year' => $year]))
                ->assertUnprocessable()->assertJsonValidationErrors('founded_year');
        }
    }

    public function test_registration_rolls_back_if_teacher_profile_creation_fails(): void
    {
        TeacherProfile::creating(fn () => throw new \RuntimeException('Simulated failure'));
        try {
            $this->api('POST', '/api/auth/register', $this->payload(['role' => 'teacher']))->assertStatus(500);
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('personal_access_tokens', 0);
        } finally {
            TeacherProfile::flushEventListeners();
        }
    }

    public function test_student_profile_update_and_avatar_upload(): void
    {
        Storage::fake('public');
        $student = $this->account();
        $token = $student->createToken('test')->plainTextToken;
        $province = AdministrativeArea::create(['code' => '01', 'name' => 'Hà Nội', 'level' => 'province']);
        $ward = AdministrativeArea::create(['code' => '00001', 'name' => 'Phường Ba Đình', 'level' => 'ward', 'parent_id' => $province->id]);
        $this->api('PUT', '/api/student/profile', [
            'name' => 'Tên mới', 'bio' => 'Mục tiêu IELTS 7.0', 'province_id' => $province->id, 'ward_id' => $ward->id,
            'date_of_birth' => '2001-03-12', 'role' => 'super_admin', 'email' => 'changed@gmail.com', 'user_id' => 999,
        ], $token)->assertOk()->assertJsonPath('user.name', 'Tên mới')->assertJsonPath('user.student_profile.full_name', 'Tên mới')
            ->assertJsonPath('user.student_profile.ward.name', 'Phường Ba Đình')->assertJsonPath('user.role', 'student')
            ->assertJsonPath('user.email', 'student@example.test');
        $this->api('GET', '/api/profile', [], $token)->assertOk()->assertJsonPath('user.student_profile.bio', 'Mục tiêu IELTS 7.0');
        $this->app['auth']->forgetGuards();
        $this->post('/api/profile/avatar', ['avatar' => UploadedFile::fake()->image('avatar.png')], ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'])
            ->assertOk()->assertJsonStructure(['avatar_url', 'user']);
        $old = $student->fresh()->avatar_path;
        Storage::disk('public')->assertExists($old);
        $this->app['auth']->forgetGuards();
        $this->post('/api/profile/avatar', ['avatar' => UploadedFile::fake()->image('new.webp')], ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'])->assertOk();
        Storage::disk('public')->assertMissing($old);
        $this->assertStringContainsString('/storage/avatars/', $student->fresh()->avatar_url);
    }

    public function test_profile_permissions_location_hierarchy_and_invalid_avatar(): void
    {
        $this->api('GET', '/api/profile')->assertUnauthorized();
        $student = $this->account();
        $token = $student->createToken('test')->plainTextToken;
        $province = AdministrativeArea::create(['code' => '01', 'name' => 'Province A', 'level' => 'province']);
        $other = AdministrativeArea::create(['code' => '02', 'name' => 'Province B', 'level' => 'province']);
        $district = AdministrativeArea::create(['code' => '001', 'name' => 'District', 'level' => 'district', 'parent_id' => $province->id]);
        $ward = AdministrativeArea::create(['code' => '00001', 'name' => 'Ward', 'level' => 'ward', 'parent_id' => $district->id]);
        $this->api('PUT', '/api/profile', ['province_id' => $other->id, 'district_id' => $district->id], $token)->assertUnprocessable()->assertJsonValidationErrors('district_id');
        $this->api('PUT', '/api/profile', ['province_id' => $province->id, 'ward_id' => $ward->id], $token)->assertUnprocessable()->assertJsonValidationErrors('ward_id');
        $this->api('PUT', '/api/profile', ['province_id' => $province->id, 'district_id' => $district->id, 'ward_id' => $ward->id], $token)->assertOk();
        $this->api('PUT', '/api/profile', ['province_id' => $ward->id, 'bio' => str_repeat('x', 1001), 'date_of_birth' => '2999-01-01'], $token)
            ->assertUnprocessable()->assertJsonValidationErrors(['province_id', 'bio', 'date_of_birth']);
        $this->app['auth']->forgetGuards();
        $this->post('/api/profile/avatar', ['avatar' => UploadedFile::fake()->create('bad.svg', 10, 'image/svg+xml')], ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'])->assertUnprocessable();
        $student->update(['role' => 'teacher']);
        $this->api('PUT', '/api/profile', ['name' => 'No edit'], $token)->assertForbidden();
        $student->update(['status' => 'blocked']);
        $this->api('GET', '/api/profile', [], $token)->assertForbidden();
    }

    public function test_change_password_with_correct_and_incorrect_current_password(): void
    {
        $user = $this->account();
        $current = $user->createToken('current')->plainTextToken;
        $other = $user->createToken('other')->plainTextToken;
        $payload = ['current_password' => 'wrong', 'password' => 'NewPassword@123', 'password_confirmation' => 'NewPassword@123'];
        $this->api('POST', '/api/auth/change-password', $payload, $current)->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->api('POST', '/api/auth/change-password', array_replace($payload, ['current_password' => 'Password@123']), $current)->assertOk();
        $this->assertTrue(Hash::check('NewPassword@123', $user->fresh()->password));
        $this->api('GET', '/api/auth/me', [], $other)->assertUnauthorized();
        $this->api('GET', '/api/auth/me', [], $current)->assertOk();
        $this->api('POST', '/api/auth/change-password', ['current_password' => 'NewPassword@123', 'password' => 'NewPassword@123', 'password_confirmation' => 'NewPassword@123'], $current)
            ->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_forgot_password_flow_and_otp_verification(): void
    {
        $user = $this->account();
        $token = $user->createToken('old')->plainTextToken;
        $response = $this->api('POST', '/api/auth/forgot-password', ['identifier' => '0981234567'])->assertOk();
        $otp = $response->json('debug_otp');
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $otp);
        $record = AuthVerificationToken::firstOrFail();
        $this->assertTrue(Hash::check($otp, $record->token_hash));
        $this->assertSame('sms', $record->channel);
        $this->assertSame('reset_password', $record->purpose);
        $this->assertArrayNotHasKey('token_hash', $record->toArray());
        $data = ['identifier' => '+84981234567', 'otp' => $otp, 'password' => 'ResetPassword@123', 'password_confirmation' => 'ResetPassword@123'];
        $this->api('POST', '/api/auth/reset-password', $data)->assertOk();
        $this->assertNotNull($record->fresh()->used_at);
        $this->assertTrue(Hash::check('ResetPassword@123', $user->fresh()->password));
        $this->api('GET', '/api/auth/me', [], $token)->assertUnauthorized();
        $this->api('POST', '/api/auth/reset-password', $data)->assertUnprocessable()->assertJsonValidationErrors('otp');
        $this->api('POST', '/api/auth/login', ['phone' => '0981234567', 'password' => 'ResetPassword@123'])->assertOk();
    }

    public function test_invalid_otp_attempts_commit_and_lock_after_five_failures(): void
    {
        $this->account();
        $otp = $this->api('POST', '/api/auth/forgot-password', ['identifier' => 'student@example.test'])->json('debug_otp');
        for ($i = 1; $i <= 5; $i++) {
            $this->api('POST', '/api/auth/reset-password', ['identifier' => 'student@example.test', 'otp' => $otp === '000000' ? '111111' : '000000', 'password' => 'ResetPassword@123', 'password_confirmation' => 'ResetPassword@123'])
                ->assertUnprocessable()->assertJsonValidationErrors('otp');
            $this->assertSame($i, AuthVerificationToken::first()->attempt_count);
        }
        $this->assertNotNull(AuthVerificationToken::first()->used_at);
        $this->api('POST', '/api/auth/reset-password', ['identifier' => 'student@example.test', 'otp' => $otp, 'password' => 'ResetPassword@123', 'password_confirmation' => 'ResetPassword@123'])->assertUnprocessable();
    }

    public function test_expired_and_superseded_codes_and_resend_rate_limit(): void
    {
        $this->account();
        $first = $this->api('POST', '/api/auth/forgot-password', ['identifier' => 'student@example.test'])->assertOk()->json('debug_otp');
        $this->api('POST', '/api/auth/forgot-password', ['identifier' => 'STUDENT@example.test'])->assertStatus(429);
        $this->travel(61)->seconds();
        $this->api('POST', '/api/auth/forgot-password', ['identifier' => 'student@example.test'])->assertOk();
        $this->assertNotNull(AuthVerificationToken::oldest('id')->first()->used_at);
        $record = AuthVerificationToken::latest('id')->first();
        $record->update(['expires_at' => now()->subSecond()]);
        $this->api('POST', '/api/auth/reset-password', ['identifier' => 'student@example.test', 'otp' => $first, 'password' => 'ResetPassword@123', 'password_confirmation' => 'ResetPassword@123'])->assertUnprocessable();
        $this->travelBack();
    }

    public function test_unknown_and_blocked_accounts_get_generic_forgot_response(): void
    {
        $user = $this->account();
        $user->update(['status' => 'blocked']);
        $first = $this->api('POST', '/api/auth/forgot-password', ['identifier' => 'student@example.test'])->assertOk()->assertJsonMissingPath('debug_otp')->json('message');
        $second = $this->api('POST', '/api/auth/forgot-password', ['identifier' => 'missing@example.test'])->assertOk()->assertJsonMissingPath('debug_otp')->json('message');
        $this->assertSame($first, $second);
        $this->assertDatabaseCount('auth_verification_tokens', 0);
    }

    public function test_production_never_exposes_debug_otp_and_delivers_mail_and_sms(): void
    {
        $this->account();
        $this->app->instance('env', 'production');
        config(['password_reset.debug' => true, 'mail.default' => 'smtp', 'password_reset.sms_url' => 'https://sms.example.test/send']);
        Mail::fake();
        Http::fake(['sms.example.test/*' => Http::response(['ok' => true])]);
        $this->api('POST', '/api/auth/forgot-password', ['identifier' => 'student@example.test'])->assertOk()->assertJsonMissingPath('debug_otp');
        Mail::assertSent(PasswordResetCode::class);
        $this->api('POST', '/api/auth/forgot-password', ['identifier' => '0981234567'])->assertOk()->assertJsonMissingPath('debug_otp');
        Http::assertSent(fn ($request) => $request['to'] === '+84981234567');
    }

    public function test_delivery_failure_invalidates_code_and_missing_sms_configuration_is_clear(): void
    {
        $this->account();
        $this->app->instance('env', 'production');
        config(['password_reset.sms_url' => null]);
        $this->api('POST', '/api/auth/forgot-password', ['identifier' => '0981234567'])->assertStatus(503)->assertJsonMissingPath('debug_otp');
        config(['password_reset.sms_url' => 'https://sms.example.test/send']);
        Http::fake(['sms.example.test/*' => Http::response([], 500)]);
        $this->travel(61)->seconds();
        $this->api('POST', '/api/auth/forgot-password', ['identifier' => '0981234567'])->assertStatus(503);
        $this->assertNotNull(AuthVerificationToken::firstOrFail()->used_at);
        $this->travelBack();
    }

    public function test_master_data_seed_is_repeatable_and_cascading_api_works(): void
    {
        $this->seed(AdministrativeAreaSeeder::class);
        $this->seed(AdministrativeAreaSeeder::class);
        $this->assertSame(34, AdministrativeArea::where('level', 'province')->count());
        $this->assertSame(3321, AdministrativeArea::where('level', 'ward')->count());
        $province = AdministrativeArea::where('code', '01')->firstOrFail();
        $this->api('GET', '/api/administrative-areas?level=province')->assertOk()->assertJsonCount(34, 'data');
        $response = $this->api('GET', '/api/administrative-areas?parent_id='.$province->id)->assertOk();
        $this->assertGreaterThan(0, count($response->json('data')));
        foreach ($response->json('data') as $area) {
            $this->assertSame($province->id, $area['parent_id']);
        }
        $this->api('GET', '/api/administrative-areas?level=bad')->assertUnprocessable();
    }
}
