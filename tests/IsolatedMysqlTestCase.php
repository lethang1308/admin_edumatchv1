<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;

/** One disposable schema per test class; each test rolls its own transaction back. */
abstract class IsolatedMysqlTestCase extends TestCase
{
    private static ?string $databaseName = null;

    private static ?PDO $adminPdo = null;

    private bool $databaseReady = false;

    protected function setUp(): void
    {
        parent::setUp();
        $connection = config('database.connections.mysql');
        $connection['url'] = null;
        $connection['database'] = null;
        config(['database.connections.auth_test_admin' => $connection]);
        if (self::$databaseName === null) {
            self::$adminPdo = DB::connection('auth_test_admin')->getPdo();
            $name = 'edumatch_feature_test_'.bin2hex(random_bytes(8));
            self::$adminPdo->exec("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            self::$databaseName = $name;
        }
        $connection['database'] = self::$databaseName;
        config(['database.connections.auth_testing' => $connection, 'database.default' => 'auth_testing', 'cache.default' => 'array']);
        DB::setDefaultConnection('auth_testing');
        $this->databaseReady = true;
        if (! DB::getSchemaBuilder()->hasTable('student_profiles')) {
            $this->assertSame(0, Artisan::call('migrate', ['--database' => 'auth_testing', '--force' => true]), Artisan::output());
        }
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        try {
            if ($this->app && $this->databaseReady) {
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
        if (self::$databaseName !== null && preg_match('/^edumatch_feature_test_[a-f0-9]{16}$/D', self::$databaseName)) {
            self::$adminPdo?->exec('DROP DATABASE `'.self::$databaseName.'`');
        }
        self::$databaseName = null;
        self::$adminPdo = null;
        parent::tearDownAfterClass();
    }
}
