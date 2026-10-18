<?php

namespace Tests\Feature;

use App\Models\AdminLog;
use App\Models\AuthVerificationToken;
use App\Models\Banner;
use App\Models\Center;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ConsultationRequest;
use App\Models\CourseBatch;
use App\Models\CourseSchedule;
use App\Models\EducationLevel;
use App\Models\FeedPost;
use App\Models\HomepageFeature;
use App\Models\Notification;
use App\Models\Post;
use App\Models\ProfileInteraction;
use App\Models\Report;
use App\Models\Review;
use App\Models\SearchLog;
use App\Models\StudentLearningNeed;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\SystemSetting;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** MySQL 8 integration tests. All writes use a disposable, randomly named database. */
class EduMatchDatabaseTest extends TestCase
{
    private ?string $testDatabase = null;

    protected function setUp(): void
    {
        parent::setUp();

        $connection = config('database.connections.mysql');
        $connection['url'] = null;
        $connection['database'] = null;
        config(['database.connections.edumatch_admin' => $connection]);
        // Fail clearly if MySQL is unavailable; never fall back to the app database.
        DB::connection('edumatch_admin')->getPdo();
        $database = 'edumatch_test_'.bin2hex(random_bytes(8));
        DB::connection('edumatch_admin')->statement("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->testDatabase = $database;
        $connection['database'] = $database;
        config([
            'database.connections.edumatch_testing' => $connection,
            'database.default' => 'edumatch_testing',
            'cache.default' => 'array',
        ]);
        DB::setDefaultConnection('edumatch_testing');
    }

    protected function tearDown(): void
    {
        try {
            DB::purge('edumatch_testing');
            if ($this->testDatabase !== null && preg_match('/^edumatch_test_[a-f0-9]{16}$/D', $this->testDatabase)) {
                DB::connection('edumatch_admin')->statement("DROP DATABASE `{$this->testDatabase}`");
            }
            DB::purge('edumatch_admin');
        } finally {
            parent::tearDown();
        }
    }

    private function migrate(array $arguments = []): void
    {
        $exit = Artisan::call('migrate', ['--database' => 'edumatch_testing', '--force' => true] + $arguments);
        $this->assertSame(0, $exit, Artisan::output());
    }

    public function test_schema_columns_indexes_foreign_keys_and_models_match_the_document(): void
    {
        $this->migrate();
        $document = file_get_contents(app_path('docs/database.txt'));
        preg_match_all('/Table (\w+) \{(.*?)\n\}/s', $document, $tables, PREG_SET_ORDER);
        $this->assertCount(42, $tables);

        foreach ($tables as [, $table, $body]) {
            $this->assertTrue(Schema::hasTable($table), $table);
            $class = 'App\\Models\\'.match ($table) {
                'feed_media' => 'FeedMedia',
                'center_staff' => 'CenterStaff',
                default => Str::studly(Str::singular($table)),
            };
            $model = new $class;
            if ($model instanceof Report) {
                // A MorphTo needs a type for a direct relation query on an unsaved model.
                $model->target_type = 'post';
            }
            $this->assertSame($table, $model->getTable());
            $columns = collect(DB::select('SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [$this->testDatabase, $table]))->keyBy('COLUMN_NAME');
            preg_match_all('/^  (\w+) (\w+(?:\([\d,]+\))?) \[(.*?)\]/m', $body, $fields, PREG_SET_ORDER);
            foreach ($fields as [, $name, $type, $options]) {
                $this->assertTrue($columns->has($name), "$table.$name");
                $column = $columns[$name];
                $nullable = ! str_contains(explode('note:', $options)[0], 'not null') && $name !== 'id';
                $this->assertSame($nullable ? 'YES' : 'NO', $column->IS_NULLABLE, "$table.$name nullable");
                $baseType = explode('(', $type)[0];
                $this->assertSame($baseType === 'boolean' ? 'tinyint' : $baseType, $column->DATA_TYPE, "$table.$name type");
                if (preg_match('/varchar\((\d+)\)/', $type, $length)) {
                    $this->assertSame((int) $length[1], (int) $column->CHARACTER_MAXIMUM_LENGTH, "$table.$name length");
                }
                if (preg_match('/decimal\((\d+),(\d+)\)/', $type, $precision)) {
                    $this->assertSame((int) $precision[1], (int) $column->NUMERIC_PRECISION);
                    $this->assertSame((int) $precision[2], (int) $column->NUMERIC_SCALE);
                }
                if (preg_match("/default: ('[^']*'|true|false|[\\d.]+)/", explode('note:', $options)[0], $default)) {
                    $expected = trim($default[1], "'");
                    if (in_array($expected, ['true', 'false'], true)) {
                        $expected = $expected === 'true' ? '1' : '0';
                    }
                    if (is_numeric($expected)) {
                        $this->assertSame((float) $expected, (float) $column->COLUMN_DEFAULT, "$table.$name default");
                    } else {
                        $this->assertSame($expected, $column->COLUMN_DEFAULT, "$table.$name default");
                    }
                }
                if (! in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                    $this->assertContains($name, $model->getFillable(), "$class.$name fillable");
                }
                if (str_contains(explode('note:', $options)[0], 'unique')) {
                    $this->assertIndex($table, [$name], true);
                }
            }
            preg_match_all('/^    \(([^)]+)\)(.*)$/m', $body, $indexes, PREG_SET_ORDER);
            foreach ($indexes as [, $names, $options]) {
                $this->assertIndex($table, explode(', ', $names), str_contains($options, 'unique'));
            }
            // Every declared relation must instantiate and execute against the actual schema.
            foreach ((new ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $class || ! $method->hasReturnType()) {
                    continue;
                }
                if (is_a((string) $method->getReturnType(), Relation::class, true)) {
                    $relation = $model->{$method->getName()}();
                    $this->assertInstanceOf(Relation::class, $relation);
                    $relation->limit(1)->get();
                }
            }
        }

        preg_match_all('/Ref: (\w+)\.(\w+) > (\w+)\.(\w+)/', $document, $references, PREG_SET_ORDER);
        $this->assertCount(93, $references);
        foreach ($references as [, $table, $column, $target, $key]) {
            $fk = DB::selectOne('SELECT REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL', [$this->testDatabase, $table, $column]);
            $this->assertNotNull($fk, "$table.$column FK");
            $this->assertSame($target, $fk->REFERENCED_TABLE_NAME);
            $this->assertSame($key, $fk->REFERENCED_COLUMN_NAME);
        }
    }

    private function assertIndex(string $table, array $columns, bool $unique): void
    {
        $indexes = collect(DB::select("SHOW INDEX FROM `{$table}`"))->groupBy('Key_name');
        $this->assertTrue($indexes->contains(function ($parts) use ($columns, $unique) {
            return $parts->sortBy('Seq_in_index')->pluck('Column_name')->all() === $columns
                && (! $unique || (int) $parts->first()->Non_unique === 0);
        }), $table.' index '.implode(',', $columns));
    }

    public function test_models_relations_casts_constraints_and_existing_integrations(): void
    {
        $this->migrate();
        $student = User::create(['name' => 'Student', 'phone' => '+84900000001', 'password' => 'secret']);
        $teacher = User::create(['name' => 'Teacher', 'role' => 'teacher', 'email' => 'teacher@example.test', 'password' => 'secret']);
        $owner = User::create(['full_name' => 'Center owner', 'role' => 'center', 'email' => 'center@example.test', 'password' => 'secret']);
        $center = Center::create(['owner_user_id' => $owner->id, 'name' => 'Center', 'slug' => 'center']);
        StudentProfile::create(['user_id' => $student->id, 'full_name' => 'Student', 'date_of_birth' => '2005-01-02']);
        TeacherProfile::create(['user_id' => $teacher->id, 'full_name' => 'Teacher']);
        $subject = Subject::create(['name' => 'Math', 'slug' => 'math']);
        $level = EducationLevel::create(['code' => 'grade-9', 'name' => 'Grade 9']);
        $teacher->subjects()->attach($subject, ['level_note' => 'Advanced']);
        $post = Post::create(['teacher_user_id' => $teacher->id, 'subject_id' => $subject->id, 'title' => 'Math course', 'slug' => 'math-course', 'description' => 'Course description', 'teaching_mode' => 'online', 'price_amount' => '120000.50']);
        $post->levels()->attach($level);
        $student->favoritePosts()->attach($post);
        $batch = CourseBatch::create(['post_id' => $post->id, 'name' => 'October class']);
        $schedule = CourseSchedule::create(['batch_id' => $batch->id, 'weekday' => 1, 'start_time' => '08:00', 'end_time' => '09:00', 'delivery_mode' => 'online']);
        $lead = ConsultationRequest::create(['student_user_id' => $student->id, 'post_id' => $post->id, 'batch_id' => $batch->id, 'contact_name' => 'Student', 'contact_phone' => '+84900000001', 'status' => 'converted']);
        $review = Review::create(['request_id' => $lead->id, 'student_user_id' => $student->id, 'post_id' => $post->id, 'teacher_user_id' => $teacher->id, 'rating' => 5]);
        $request = VerificationRequest::create(['teacher_user_id' => $teacher->id, 'submitted_by_user_id' => $teacher->id, 'submitted_at' => now()]);
        $interaction = ProfileInteraction::create(['student_user_id' => $student->id, 'teacher_user_id' => $teacher->id, 'interaction_type' => 'trust']);
        $conversation = ChatConversation::create(['student_user_id' => $student->id, 'teacher_user_id' => $teacher->id]);
        $conversation->participants()->attach([$student->id, $teacher->id], ['joined_at' => now()]);
        ChatMessage::create(['conversation_id' => $conversation->id, 'sender_user_id' => $student->id, 'body' => 'Hello', 'sent_at' => now()]);
        $feature = HomepageFeature::create(['post_id' => $post->id]);
        $need = StudentLearningNeed::create(['student_user_id' => $student->id, 'budget_min' => 100, 'budget_max' => 200]);
        $feed = FeedPost::create(['author_user_id' => $teacher->id, 'content' => 'Learning tips']);
        $student->likedFeedPosts()->attach($feed);
        $notice = Notification::create(['user_id' => $student->id, 'type' => 'lead', 'title' => 'New lead', 'data' => ['post_id' => $post->id]]);
        $notice->markAsRead();
        $this->assertSame(1, $student->readNotifications()->count());
        $notice->markAsUnread();
        $this->assertSame(1, $student->unreadNotifications()->count());
        $this->assertSame($post->id, $notice->fresh()->data['post_id']);
        SystemSetting::create(['setting_key' => 'example', 'setting_value' => ['enabled' => true]]);
        AdminLog::create(['admin_user_id' => $owner->id, 'action' => 'test', 'new_values' => ['ok' => true]]);
        SearchLog::create(['subject_id' => $subject->id]);
        $token = AuthVerificationToken::create(['destination' => 'student@example.test', 'channel' => 'email', 'purpose' => 'signup', 'token_hash' => 'hash', 'expires_at' => now()->addMinutes(5)]);
        $this->assertArrayNotHasKey('token_hash', $token->toArray());
        $this->assertSame('120000.50', $post->fresh()->price_amount);
        $this->assertSame('2005-01-02', $student->studentProfile->date_of_birth->toDateString());
        $this->assertSame('Advanced', $teacher->subjects->first()->pivot->level_note);
        $this->assertTrue($teacher->teacherProfile->user->is($teacher));
        $this->assertTrue($lead->review->is($review));
        $this->assertTrue($post->levels->first()->is($level));
        $this->assertTrue($student->favoritePosts->first()->is($post));
        $this->assertTrue($feed->likedBy->first()->is($student));
        $this->assertSame(2, $conversation->participants()->count());
        $this->assertSame(1, $conversation->chatMessages()->count());

        // Report aliases work for both lazy/eager loading without changing Spatie morph IDs.
        foreach (['teacher' => $teacher, 'post' => $post, 'center' => $center, 'review' => $review, 'feed_post' => $feed, 'user' => $student] as $type => $target) {
            $report = new Report(['reporter_user_id' => $student->id, 'target_type' => $type, 'reason' => 'test']);
            $report->target()->associate($target);
            $report->save();
            $this->assertSame($type, $report->target_type);
            $this->assertTrue($report->fresh()->target->is($target));
        }
        $this->assertCount(6, Report::with('target')->get()->filter(fn ($report) => $report->target !== null));
        $role = Role::create(['name' => 'schema-test-role', 'guard_name' => 'web']);
        $teacher->assignRole($role);
        $this->assertTrue($teacher->fresh()->hasRole('schema-test-role'));
        $this->assertSame(User::class, $teacher->getMorphClass());
        $this->assertNotEmpty($teacher->createToken('schema-test')->plainTextToken);

        $this->assertRejected(fn () => $student->favoritePosts()->attach($post), 1062);
        $this->assertRejected(fn () => $student->update(['phone' => null, 'email' => null]), 3819);
        $this->assertRejected(fn () => $post->update(['teacher_user_id' => null]), 3819);
        $this->assertRejected(fn () => $post->fresh()->update(['center_id' => $center->id]), 3819);
        $this->assertRejected(fn () => $schedule->update(['weekday' => 0]), 3819);
        $this->assertRejected(fn () => $schedule->fresh()->update(['end_time' => '07:00']), 3819);
        $this->assertRejected(fn () => $review->update(['rating' => 6]), 3819);
        $this->assertRejected(fn () => $review->fresh()->update(['teacher_user_id' => null]), 3819);
        $this->assertRejected(fn () => $request->update(['teacher_user_id' => null]), 3819);
        $this->assertRejected(fn () => $interaction->update(['teacher_user_id' => null]), 3819);
        $this->assertRejected(fn () => $conversation->update(['teacher_user_id' => null]), 3819);
        $this->assertRejected(fn () => $feature->update(['teacher_user_id' => $teacher->id]), 3819);
        $this->assertRejected(fn () => $need->update(['budget_max' => 50]), 3819);
        $this->assertRejected(fn () => $post->fresh()->update(['subject_id' => 999999]), 1452);

        $post = $post->fresh();
        $post->delete();
        $this->assertNull(Post::find($post->id));
        $this->assertNotNull(Post::withTrashed()->find($post->id));
        $post->restore();
        $this->assertNotNull(Post::find($post->id));
    }

    private function assertRejected(callable $operation, int $mysqlError): void
    {
        try {
            $operation();
            $this->fail('Expected MySQL constraint violation '.$mysqlError);
        } catch (QueryException $exception) {
            $this->assertSame($mysqlError, $exception->errorInfo[1]);
        }
    }

    public function test_legacy_data_upgrade_rollback_and_reapply(): void
    {
        $legacyPaths = array_map(fn ($path) => 'database/migrations/'.basename($path), glob(database_path('migrations/*.php')));
        $legacyPaths = array_values(array_filter($legacyPaths, fn ($path) => ! str_contains($path, '2026_10_18_')));
        $this->migrate(['--path' => $legacyPaths]);
        $userId = DB::table('users')->insertGetId(['full_name' => 'Legacy admin', 'email' => 'legacy@example.test', 'password' => bcrypt('secret'), 'role' => 'admin', 'avatar' => 'old.png', 'is_active' => false]);
        $bannerId = DB::table('banners')->insertGetId(['title' => 'Legacy banner', 'image' => 'banner.png', 'link' => 'https://example.test', 'position' => 'home', 'order' => 2, 'status' => false]);
        $this->migrate();
        $user = User::findOrFail($userId);
        $this->assertSame('Legacy admin', $user->name);
        $this->assertSame('super_admin', $user->role);
        $this->assertSame('blocked', $user->status);
        $this->assertSame('old.png', $user->avatar_path);
        $banner = Banner::findOrFail($bannerId);
        $this->assertSame('banner.png', $banner->image_path);
        $this->assertSame('banner.png', $banner->image);
        $this->assertSame(2, $banner->order);
        $this->assertSame('inactive', $banner->status);
        $banner->image = 'updated.png';
        $banner->status = true;
        $banner->save();
        $this->assertSame('active', $banner->fresh()->status);
        Banner::create(['image_path' => 'new.png']);

        $newMigrationCount = count(glob(database_path('migrations/2026_10_18_*.php')));
        $this->assertSame(0, Artisan::call('migrate:rollback', ['--database' => 'edumatch_testing', '--step' => $newMigrationCount, '--force' => true]), Artisan::output());
        $this->assertFalse(Schema::hasTable('posts'));
        $this->assertSame('updated.png', DB::table('banners')->where('id', $bannerId)->value('image'));
        $this->assertSame('admin', DB::table('users')->where('id', $userId)->value('role'));
        $this->migrate();
        $this->assertTrue(Schema::hasTable('posts'));
        $this->assertSame(0, Artisan::call('migrate:reset', ['--database' => 'edumatch_testing', '--force' => true]), Artisan::output());
        $this->assertFalse(Schema::hasTable('users'));
    }
}
