<?php

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use PDO;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentAuthTest extends TestCase
{
    private static ?string $databaseName = null;

    private static ?PDO $adminPdo = null;

    protected function setUp(): void
    {
        parent::setUp();
        $connection = config('database.connections.mysql');
        $connection['url'] = null;
        $connection['database'] = null;
        config(['database.connections.auth_test_admin' => $connection]);
        if (self::$databaseName === null) {
            self::$adminPdo = DB::connection('auth_test_admin')->getPdo();
            self::$databaseName = 'edumatch_auth_test_'.bin2hex(random_bytes(8));
            self::$adminPdo->exec('CREATE DATABASE `'.self::$databaseName.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
        $connection['database'] = self::$databaseName;
        config(['database.connections.auth_testing' => $connection, 'database.default' => 'auth_testing', 'cache.default' => 'array']);
        DB::setDefaultConnection('auth_testing');
        if (! DB::getSchemaBuilder()->hasTable('student_profiles')) {
            $this->assertSame(0, Artisan::call('migrate', ['--database' => 'auth_testing', '--force' => true]), Artisan::output());
        }
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        try {
            if ($this->app) {
                while (DB::connection('auth_testing')->transactionLevel() > 0) {
                    DB::connection('auth_testing')->rollBack();
                }
                DB::purge('auth_testing');
                DB::purge('auth_test_admin');
            }
        } finally {
            parent::tearDown();
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$databaseName !== null && preg_match('/^edumatch_auth_test_[a-f0-9]{16}$/D', self::$databaseName)) {
            self::$adminPdo?->exec('DROP DATABASE `'.self::$databaseName.'`');
        }
        self::$databaseName = null;
        self::$adminPdo = null;
        parent::tearDownAfterClass();
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Nguyễn Văn A', 'email' => 'edumatch.student.test@gmail.com',
            'phone' => '0981234567', 'date_of_birth' => '2002-05-15',
            'password' => 'Admin@123', 'password_confirmation' => 'Admin@123', 'role' => 'student',
        ], $overrides);
    }

    private function requestApi(string $method, string $path, array $data = [], ?string $token = null): TestResponse
    {
        // Force Sanctum to resolve the bearer token again on each simulated HTTP request.
        $this->app['auth']->forgetGuards();

        return $this->json($method, $path, $data, $token ? ['Authorization' => 'Bearer '.$token] : []);
    }

    public function test_registration_creates_student_profile_role_and_real_token_atomically(): void
    {
        $response = $this->requestApi('POST', '/api/auth/register', $this->payload(['email' => ' EDUMATCH.STUDENT.TEST@GMAIL.COM ', 'phone' => '098 123.4567', 'status' => 'blocked', 'balance' => 999]));
        $response->assertCreated()->assertJsonPath('user.role', 'student')->assertJsonPath('user.status', 'active')
            ->assertJsonPath('user.phone', '+84981234567')->assertJsonPath('user.student_profile.full_name', 'Nguyễn Văn A')
            ->assertJsonPath('token_type', 'Bearer')->assertJsonMissingPath('user.password');
        $this->assertSame($response->json('token'), $response->json('access_token'));
        $user = User::firstOrFail();
        $this->assertTrue(Hash::check('Admin@123', $user->password));
        $this->assertTrue($user->hasRole('student'));
        $this->assertSame('0.00', $user->balance);
        $this->assertSame('2002-05-15', $user->studentProfile->date_of_birth->toDateString());
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('student_profiles', 1);
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->requestApi('GET', '/api/auth/me', [], $response->json('token'))->assertOk()
            ->assertJsonPath('roles.0', 'student')->assertJsonPath('user.id', $user->id);
    }

    public function test_duplicate_email_and_both_phone_formats_return_field_errors(): void
    {
        $this->requestApi('POST', '/api/auth/register', $this->payload())->assertCreated();
        $this->requestApi('POST', '/api/auth/register', $this->payload(['email' => 'EDUMATCH.STUDENT.TEST@gmail.com', 'phone' => '0981234568']))
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        foreach (['0981234567', '+84981234567'] as $phone) {
            $this->requestApi('POST', '/api/auth/register', $this->payload(['email' => 'edumatch.other.test@gmail.com', 'phone' => $phone]))
                ->assertUnprocessable()->assertJsonValidationErrors('phone');
        }
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('student_profiles', 1);
    }

    public function test_validation_rejects_invalid_inputs_and_role_escalation(): void
    {
        $cases = [
            ['name', 'A'], ['name', str_repeat('A', 151)], ['email', 'not-an-email'],
            ['phone', '0123456789'], ['phone', '09812345678'],
            ['date_of_birth', now()->addDay()->toDateString()], ['date_of_birth', '2002-02-31'],
            ['password', '123456'], ['password_confirmation', 'not-matching'],
            ['role', 'super_admin'], ['role', 'teacher'], ['role', 'center'],
        ];
        foreach ($cases as [$field, $value]) {
            $this->requestApi('POST', '/api/auth/register', $this->payload([$field => $value]))
                ->assertUnprocessable()->assertJsonValidationErrors($field === 'password_confirmation' ? 'password' : $field);
        }
        $this->requestApi('POST', '/api/auth/register', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'date_of_birth', 'password', 'password_confirmation']);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_name_and_birth_date_aliases_and_legacy_routes(): void
    {
        $data = $this->payload();
        unset($data['name'], $data['date_of_birth'], $data['role']);
        $response = $this->requestApi('POST', '/api/register', $data + ['full_name' => 'Legacy name', 'dateOfBirth' => '2002-05-15']);
        $response->assertCreated()->assertJsonPath('user.name', 'Legacy name');
        foreach (['/api/user', '/api/auth/user', '/api/me'] as $path) {
            $this->requestApi('GET', $path, [], $response->json('token'))->assertOk()->assertJsonPath('user.name', 'Legacy name');
        }
        $this->requestApi('POST', '/api/login', ['phone' => '0981234567', 'password' => 'Admin@123'])->assertOk();
    }

    public function test_login_accepts_email_domestic_and_international_phone_and_updates_login_time(): void
    {
        $this->requestApi('POST', '/api/auth/register', $this->payload())->assertCreated();
        foreach ([['email' => 'EDUMATCH.STUDENT.TEST@gmail.com'], ['phone' => '0981234567'], ['phone' => '+84981234567'], ['identifier' => '098 123 4567']] as $identifier) {
            $response = $this->requestApi('POST', '/api/auth/login', $identifier + ['password' => 'Admin@123', 'remember' => false]);
            $response->assertOk()->assertJsonPath('user.student_profile.full_name', 'Nguyễn Văn A');
            $this->assertNotEmpty($response->json('token'));
        }
        $this->assertNotNull(User::first()->last_login_at);
        $this->requestApi('POST', '/api/auth/login', ['email' => 'edumatch.student.test@gmail.com', 'password' => 'wrong'])->assertUnauthorized();
        $this->requestApi('POST', '/api/auth/login', ['phone' => '0980000000', 'password' => 'wrong'])->assertUnauthorized();
        $this->requestApi('POST', '/api/auth/login', [])->assertUnprocessable()->assertJsonValidationErrors(['identifier', 'password']);
    }

    public function test_legacy_domestic_phone_is_found_and_cannot_be_registered_again(): void
    {
        User::create(['name' => 'Legacy', 'email' => 'legacy@example.test', 'phone' => '0981234567', 'password' => 'Admin@123']);
        $this->requestApi('POST', '/api/auth/login', ['phone' => '+84981234567', 'password' => 'Admin@123'])->assertOk();
        $this->requestApi('POST', '/api/auth/register', $this->payload(['phone' => '+84981234567']))->assertUnprocessable()->assertJsonValidationErrors('phone');
    }

    public function test_blocked_and_deleted_users_cannot_authenticate_or_refresh(): void
    {
        $response = $this->requestApi('POST', '/api/auth/register', $this->payload())->assertCreated();
        $token = $response->json('token');
        $user = User::firstOrFail();
        $user->update(['status' => 'blocked']);
        $this->requestApi('POST', '/api/auth/login', ['phone' => '0981234567', 'password' => 'Admin@123'])->assertForbidden();
        $this->requestApi('GET', '/api/auth/me', [], $token)->assertForbidden();
        $this->requestApi('POST', '/api/auth/refresh', [], $token)->assertForbidden();
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $user->delete();
        $this->requestApi('POST', '/api/auth/login', ['phone' => '0981234567', 'password' => 'Admin@123'])->assertUnauthorized();
        $this->requestApi('GET', '/api/auth/me', [], $token)->assertUnauthorized();
    }

    public function test_logout_revokes_only_current_token_and_refresh_replaces_it(): void
    {
        $first = $this->requestApi('POST', '/api/auth/register', $this->payload())->json('token');
        $second = $this->requestApi('POST', '/api/auth/login', ['phone' => '0981234567', 'password' => 'Admin@123'])->json('token');
        $this->requestApi('POST', '/api/auth/logout', [], $first)->assertOk();
        $this->requestApi('GET', '/api/auth/me', [], $first)->assertUnauthorized();
        $this->requestApi('GET', '/api/auth/me', [], $second)->assertOk();
        $new = $this->requestApi('POST', '/api/auth/refresh', [], $second)->assertOk()->json('token');
        $this->assertNotSame($second, $new);
        $this->requestApi('GET', '/api/auth/me', [], $second)->assertUnauthorized();
        $this->requestApi('GET', '/api/auth/me', [], $new)->assertOk();
        $this->requestApi('POST', '/api/logout', [], $new)->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->requestApi('GET', '/api/auth/me')->assertUnauthorized();
    }

    public function test_profile_failure_rolls_back_user_and_token(): void
    {
        StudentProfile::creating(function () {
            throw new \RuntimeException('Simulated profile failure');
        });
        try {
            $this->requestApi('POST', '/api/auth/register', $this->payload())->assertStatus(500);
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('student_profiles', 0);
            $this->assertDatabaseCount('personal_access_tokens', 0);
        } finally {
            StudentProfile::flushEventListeners();
        }
    }

    public function test_role_seeder_is_repeatable_and_preserves_existing_permissions(): void
    {
        $role = Role::findOrCreate('content', 'web');
        $user = User::create(['name' => 'Teacher', 'email' => 'teacher@example.test', 'password' => 'secret123', 'role' => 'teacher']);
        $user->assignRole($role);
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(RoleAndPermissionSeeder::class);
        $this->assertDatabaseCount('users', 1);
        foreach (['student', 'teacher', 'center', 'super_admin'] as $name) {
            $this->assertDatabaseHas('roles', ['name' => $name, 'guard_name' => 'web']);
        }
        $this->assertTrue($user->fresh()->hasAllRoles(['content', 'teacher']));
        $this->assertTrue(Role::findByName('super_admin')->hasPermissionTo('access_admin'));
    }
}
