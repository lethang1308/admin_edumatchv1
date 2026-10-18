<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('keyword', 255)->nullable();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->restrictOnDelete();
            $table->foreignId('level_id')->nullable()->constrained('education_levels')->restrictOnDelete();
            $table->string('teaching_mode', 20)->nullable();
            $table->foreignId('province_id')->nullable()->constrained('administrative_areas')->restrictOnDelete();
            $table->integer('result_count')->nullable()->default(0);
            $table->dateTime('created_at');

            $table->index(['subject_id', 'created_at']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};
