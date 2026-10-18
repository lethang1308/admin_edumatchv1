<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('full_name', 150);
            $table->date('date_of_birth')->nullable();
            $table->string('headline', 200)->nullable();
            $table->longText('bio')->nullable();
            $table->tinyInteger('experience_years')->nullable()->default(0);
            $table->foreignId('province_id')->nullable()->constrained('administrative_areas')->restrictOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('administrative_areas')->restrictOnDelete();
            $table->foreignId('ward_id')->nullable()->constrained('administrative_areas')->restrictOnDelete();
            $table->boolean('show_phone')->nullable()->default(false);
            $table->string('verification_status', 20)->nullable()->default('unverified');
            $table->dateTime('verified_at')->nullable();
            $table->decimal('rating_avg', 3, 2)->nullable()->default(0);
            $table->integer('review_count')->nullable()->default(0);
            $table->integer('trust_count')->nullable()->default(0);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['verification_status', 'rating_avg']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_profiles');
    }
};
