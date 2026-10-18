<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('full_name', 150);
            $table->date('date_of_birth')->nullable();
            $table->foreignId('province_id')->nullable()->constrained('administrative_areas')->restrictOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('administrative_areas')->restrictOnDelete();
            $table->foreignId('ward_id')->nullable()->constrained('administrative_areas')->restrictOnDelete();
            $table->text('bio')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
