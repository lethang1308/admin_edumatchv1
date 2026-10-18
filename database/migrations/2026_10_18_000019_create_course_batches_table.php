<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->restrictOnDelete();
            $table->foreignId('center_branch_id')->nullable()->constrained('center_branches')->restrictOnDelete();
            $table->foreignId('teacher_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('name', 160);
            $table->smallInteger('capacity')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 20)->nullable()->default('open');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['post_id', 'status']);
            $table->index(['center_branch_id']);
            $table->index(['teacher_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_batches');
    }
};
