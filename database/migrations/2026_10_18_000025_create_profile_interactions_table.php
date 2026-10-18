<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('teacher_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('center_id')->nullable()->constrained('centers')->restrictOnDelete();
            $table->string('interaction_type', 20);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->unique(['student_user_id', 'teacher_user_id', 'interaction_type'], 'profile_interactions_0_unique');
            $table->unique(['student_user_id', 'center_id', 'interaction_type'], 'profile_interactions_1_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_interactions');
    }
};
