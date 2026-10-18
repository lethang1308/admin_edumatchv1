<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_learning_needs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->restrictOnDelete();
            $table->foreignId('level_id')->nullable()->constrained('education_levels')->restrictOnDelete();
            $table->string('preferred_mode', 20)->nullable();
            $table->decimal('budget_min', 12, 2)->nullable();
            $table->decimal('budget_max', 12, 2)->nullable();
            $table->text('goals')->nullable();
            $table->boolean('is_active')->nullable()->default(true);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['student_user_id', 'is_active']);
            $table->index(['subject_id', 'level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_learning_needs');
    }
};
