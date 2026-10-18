<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('post_id')->constrained('posts')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('course_batches')->restrictOnDelete();
            $table->foreignId('assigned_staff_id')->nullable()->constrained('center_staff')->restrictOnDelete();
            $table->string('contact_name', 150);
            $table->string('contact_phone', 20);
            $table->string('contact_email', 191)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->nullable()->default('new');
            $table->text('internal_note')->nullable();
            $table->dateTime('contacted_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->dateTime('deleted_at')->nullable();

            $table->index(['student_user_id', 'created_at']);
            $table->index(['post_id', 'status', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['assigned_staff_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_requests');
    }
};
