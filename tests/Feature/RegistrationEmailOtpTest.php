<?php

namespace Tests\Feature;

use App\Mail\RegistrationOtpCode;
use App\Models\AuthVerificationToken;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\SeedsRegistrationOtp;
use Tests\IsolatedMysqlTestCase;

class RegistrationEmailOtpTest extends IsolatedMysqlTestCase
{
    use SeedsRegistrationOtp;

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Nguyễn Văn A', 'email' => 'edumatch.otp.test@gmail.com', 'phone' => '0981234567',
            'date_of_birth' => '2002-05-15', 'password' => 'Password@123',
            'password_confirmation' => 'Password@123', 'role' => 'student', 'otp' => '123456',
        ], $overrides);
    }

    private function send(array $overrides = [], string $path = '/api/auth/register/send-otp')
    {
        return $this->postJson($path, array_intersect_key($this->payload($overrides), array_flip(['name', 'email', 'phone'])));
    }

    public function test_send_registration_otp_dispatches_mail_and_creates_hashed_token_without_account(): void
    {
        Mail::fake();
        $response = $this->send(['email' => ' EDUMATCH.OTP.TEST@GMAIL.COM ', 'phone' => '098 123.4567'])
            ->assertOk()->assertJsonPath('email', 'edumatch.otp.test@gmail.com')
            ->assertJsonPath('expires_in', 600)->assertJsonPath('resend_after', 60)->assertJsonMissingPath('debug_otp');
        $record = AuthVerificationToken::firstOrFail();
        Mail::assertSent(RegistrationOtpCode::class, function ($mail) use ($record) {
            return $mail->hasTo('edumatch.otp.test@gmail.com') && $mail->name === 'Nguyễn Văn A'
                && preg_match('/^[0-9]{6}$/D', $mail->otp) && Hash::check($mail->otp, $record->token_hash);
        });
        $this->assertNull($record->user_id);
        $this->assertNull($record->used_at);
        $this->assertEqualsWithDelta(now()->addMinutes(10)->timestamp, $record->expires_at->timestamp, 2);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseCount('auth_verification_tokens', 1);
    }

    public function test_send_registration_otp_validates_unique_email_and_phone(): void
    {
        Mail::fake();
        User::create(['name' => 'Existing', 'email' => 'edumatch.otp.test@gmail.com', 'phone' => '0981234567', 'password' => 'Password@123']);
        $this->send(['email' => ' EDUMATCH.OTP.TEST@GMAIL.COM ', 'phone' => '0981234568'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        foreach (['0981234567', '+84981234567'] as $index => $phone) {
            $this->send(['email' => 'other'.$index.'@gmail.com', 'phone' => $phone])
                ->assertUnprocessable()->assertJsonValidationErrors('phone');
        }
        Mail::assertNothingSent();
        $this->assertDatabaseCount('auth_verification_tokens', 0);
    }

    public function test_send_rejects_malformed_email_and_phone_before_delivery(): void
    {
        Mail::fake();
        $this->send(['email' => 'invalid', 'phone' => '12345'])->assertUnprocessable()->assertJsonValidationErrors(['email', 'phone']);
        Mail::assertNothingSent();
        $this->assertDatabaseCount('auth_verification_tokens', 0);
    }

    public function test_registration_requires_email_and_otp_for_every_role_and_legacy_api(): void
    {
        foreach (['student', 'teacher', 'center'] as $role) {
            $data = $this->payload(['role' => $role, 'founded_year' => 2020, 'show_phone' => false]);
            unset($data['otp']);
            $this->postJson('/api/auth/register', $data)->assertUnprocessable()->assertJsonValidationErrors('otp');
            $data['email'] = null;
            $this->postJson('/api/register', $data)->assertUnprocessable()->assertJsonValidationErrors(['email', 'otp']);
        }
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_fails_with_invalid_expired_missing_or_wrong_purpose_otp(): void
    {
        $this->postJson('/api/auth/register', $this->payload())->assertUnprocessable()->assertJsonValidationErrors('otp');
        $record = $this->seedRegistrationOtp('edumatch.otp.test@gmail.com');
        $this->postJson('/api/auth/register', $this->payload(['otp' => '000000']))->assertUnprocessable()->assertJsonValidationErrors('otp');
        $this->assertSame(1, $record->fresh()->attempt_count);
        $record->update(['expires_at' => now()]);
        $this->postJson('/api/auth/register', $this->payload())->assertUnprocessable()->assertJsonValidationErrors('otp');
        $record->update(['expires_at' => now()->addMinutes(10), 'purpose' => 'reset_password']);
        $this->postJson('/api/auth/register', $this->payload())->assertUnprocessable()->assertJsonValidationErrors('otp');
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_five_wrong_attempts_are_persisted_and_disable_even_the_correct_code(): void
    {
        $record = $this->seedRegistrationOtp('edumatch.otp.test@gmail.com');
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/auth/register', $this->payload(['otp' => '000000']))->assertUnprocessable()->assertJsonValidationErrors('otp');
            $this->assertSame($attempt, $record->fresh()->attempt_count);
        }
        $this->assertNotNull($record->fresh()->used_at);
        $this->postJson('/api/auth/register', $this->payload())->assertUnprocessable()->assertJsonValidationErrors('otp');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_succeeds_with_correct_otp_and_sets_email_verified_at(): void
    {
        Mail::fake();
        config(['registration_otp.debug' => true]);
        $code = $this->send()->assertOk()->json('debug_otp');
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/D', $code);
        $response = $this->postJson('/api/auth/register', $this->payload(['otp' => $code, 'email' => ' EDUMATCH.OTP.TEST@GMAIL.COM ']))
            ->assertCreated()->assertJsonPath('user.role', 'student');
        $this->assertNotEmpty($response->json('token'));
        $this->assertNotNull(User::firstOrFail()->email_verified_at);
        $this->assertNotNull(AuthVerificationToken::firstOrFail()->used_at);
        $this->assertDatabaseCount('student_profiles', 1);
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->app['auth']->forgetGuards();
        $this->withToken($response->json('token'))->getJson('/api/auth/me')->assertOk();
        $this->postJson('/api/auth/register', $this->payload(['otp' => $code]))->assertUnprocessable();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_teacher_and_center_capability_profiles_accept_long_form_content(): void
    {
        $bio = str_repeat('Hồ sơ năng lực chi tiết của đơn vị đào tạo. ', 160);

        $center = $this->payload([
            'role' => 'center',
            'name' => 'Trung tâm dài',
            'email' => 'center.long.profile@gmail.com',
            'phone' => '0981234568',
            'founded_year' => 2019,
            'teaching_field' => 'Tiếng Anh và luyện thi',
            'centerDescription' => $bio,
        ]);
        $this->seedRegistrationOtp($center['email']);
        $this->postJson('/api/auth/register', $center)->assertCreated()
            ->assertJsonPath('user.center.bio', rtrim($bio))
            ->assertJsonPath('user.center.teaching_field', 'Tiếng Anh và luyện thi');

        $teacher = $this->payload([
            'role' => 'teacher',
            'name' => 'Giáo viên dài',
            'email' => 'teacher.long.profile@gmail.com',
            'phone' => '0981234569',
            'teachingField' => 'Tiếng Anh giao tiếp',
            'biography' => $bio,
            'show_phone' => false,
        ]);
        $this->seedRegistrationOtp($teacher['email']);
        $this->postJson('/api/auth/register', $teacher)->assertCreated()
            ->assertJsonPath('user.teacher_profile.bio', rtrim($bio))
            ->assertJsonPath('user.teacher_profile.teaching_field', 'Tiếng Anh giao tiếp');
    }

    public function test_codes_are_bound_to_the_email_and_consumed_codes_cannot_be_reused(): void
    {
        $record = $this->seedRegistrationOtp('other@gmail.com');
        $this->postJson('/api/auth/register', $this->payload())->assertUnprocessable()->assertJsonValidationErrors('otp');
        $record->update(['destination' => 'edumatch.otp.test@gmail.com', 'used_at' => now()]);
        $this->postJson('/api/auth/register', $this->payload())->assertUnprocessable()->assertJsonValidationErrors('otp');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_resend_invalidates_old_codes_and_legacy_send_route_shares_cooldown(): void
    {
        Mail::fake();
        $old = $this->seedRegistrationOtp('edumatch.otp.test@gmail.com', '000000');
        $this->send()->assertOk();
        $this->assertNotNull($old->fresh()->used_at);
        $this->send(['email' => ' EDUMATCH.OTP.TEST@GMAIL.COM '], '/api/register/send-otp')->assertStatus(429)->assertHeader('Retry-After');
        $this->postJson('/api/auth/register', $this->payload(['otp' => '000000']))->assertUnprocessable()->assertJsonValidationErrors('otp');
        $this->travel(61)->seconds();
        try {
            $this->send([], '/api/register/send-otp')->assertOk();
            $this->assertSame(1, AuthVerificationToken::whereNull('used_at')->count());
        } finally {
            $this->travelBack();
        }
        Mail::assertSent(RegistrationOtpCode::class, 2);
    }

    public function test_send_is_limited_to_five_different_emails_per_ip_per_minute(): void
    {
        Mail::fake();
        for ($index = 1; $index <= 5; $index++) {
            $this->send(['email' => 'edumatch.otp'.$index.'@gmail.com'])->assertOk();
        }
        $this->send(['email' => 'edumatch.otp6@gmail.com'])->assertStatus(429);
        Mail::assertSent(RegistrationOtpCode::class, 5);
    }

    public function test_delivery_failure_rolls_back_new_code_and_preserves_previous_code(): void
    {
        $record = $this->seedRegistrationOtp('edumatch.otp.test@gmail.com');
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('Simulated SMTP failure'));
        $this->send()->assertStatus(503)->assertJsonMissingPath('debug_otp');
        $this->assertNull($record->fresh()->used_at);
        $this->assertDatabaseCount('auth_verification_tokens', 1);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_account_creation_failure_does_not_consume_a_valid_code(): void
    {
        $record = $this->seedRegistrationOtp('edumatch.otp.test@gmail.com');
        StudentProfile::creating(fn () => throw new \RuntimeException('Simulated profile failure'));
        try {
            $this->postJson('/api/auth/register', $this->payload())->assertStatus(500);
            $this->assertNull($record->fresh()->used_at);
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('personal_access_tokens', 0);
        } finally {
            StudentProfile::flushEventListeners();
        }
        $this->postJson('/api/auth/register', $this->payload())->assertCreated();
    }

    public function test_production_never_returns_debug_code_even_when_enabled(): void
    {
        Mail::fake();
        $this->app->instance('env', 'production');
        config(['registration_otp.debug' => true, 'mail.default' => 'smtp']);
        $this->send()->assertOk()->assertJsonMissingPath('debug_otp');
        Mail::assertSent(RegistrationOtpCode::class);
    }

    public function test_undeliverable_mailer_cannot_claim_email_was_sent(): void
    {
        $this->app->instance('env', 'local');
        config(['mail.default' => 'log', 'registration_otp.debug' => true]);
        $this->send()->assertStatus(503);
        $this->assertDatabaseCount('auth_verification_tokens', 0);
    }

    public function test_mail_template_renders_code_expiry_and_escapes_the_name(): void
    {
        $mail = new RegistrationOtpCode('123456', '<script>alert(1)</script>');
        $mail->assertSeeInHtml('123456');
        $mail->assertSeeInHtml('10 phút');
        $mail->assertSeeInHtml('&lt;script&gt;', false);
        $mail->assertDontSeeInHtml('<script>', false);
    }
}
