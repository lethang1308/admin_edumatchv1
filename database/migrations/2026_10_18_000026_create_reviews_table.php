<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->unique()->constrained('consultation_requests')->restrictOnDelete();
            $table->foreignId('student_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('post_id')->constrained('posts')->restrictOnDelete();
            $table->foreignId('teacher_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('center_id')->nullable()->constrained('centers')->restrictOnDelete();
            $table->tinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('status', 20)->nullable()->default('pending');
            $table->foreignId('moderated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('moderated_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->dateTime('deleted_at')->nullable();

            $table->index(['teacher_user_id', 'status']);
            $table->index(['center_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
