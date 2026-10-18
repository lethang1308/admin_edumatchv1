<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_user_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 200);
            $table->string('issuer', 200)->nullable();
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->boolean('is_verified')->nullable()->default(false);
            $table->boolean('is_public')->nullable()->default(true);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['teacher_user_id', 'is_public']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_qualifications');
    }
};
