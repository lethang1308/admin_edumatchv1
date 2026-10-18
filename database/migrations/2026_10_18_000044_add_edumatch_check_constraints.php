<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Only invariants within a single row belong here; cross-table rules need services. */
    private function constraints(): array
    {
        return [
            'users' => ['contact' => 'email IS NOT NULL OR phone IS NOT NULL'],
            'student_learning_needs' => ['budget' => 'budget_min IS NULL OR budget_max IS NULL OR budget_min <= budget_max'],
            'verification_requests' => ['owner' => '(teacher_user_id IS NOT NULL) + (center_id IS NOT NULL) = 1'],
            'posts' => ['owner' => '(teacher_user_id IS NOT NULL) + (center_id IS NOT NULL) = 1'],
            'course_schedules' => [
                'weekday' => 'weekday BETWEEN 1 AND 7',
                'times' => 'start_time < end_time',
            ],
            'profile_interactions' => ['target' => '(teacher_user_id IS NOT NULL) + (center_id IS NOT NULL) = 1'],
            'reviews' => [
                'rating' => 'rating BETWEEN 1 AND 5',
                'target' => '(teacher_user_id IS NOT NULL) + (center_id IS NOT NULL) = 1',
            ],
            'chat_conversations' => ['recipient' => '(teacher_user_id IS NOT NULL) + (center_id IS NOT NULL) = 1'],
            'homepage_features' => ['target' => '(teacher_user_id IS NOT NULL) + (center_id IS NOT NULL) + (post_id IS NOT NULL) = 1'],
        ];
    }

    public function up(): void
    {
        foreach ($this->constraints() as $table => $constraints) {
            foreach ($constraints as $name => $expression) {
                DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$table}_{$name}_check` CHECK ({$expression})");
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->constraints(), true) as $table => $constraints) {
            foreach ($constraints as $name => $expression) {
                DB::statement("ALTER TABLE `{$table}` DROP CHECK `{$table}_{$name}_check`");
            }
        }
    }
};
