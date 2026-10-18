<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('center_id')->nullable()->constrained('centers')->restrictOnDelete();
            $table->foreignId('submitted_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->nullable()->default('pending');
            $table->dateTime('submitted_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['teacher_user_id', 'status']);
            $table->index(['center_id', 'status']);
            $table->index(['status', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_requests');
    }
};
