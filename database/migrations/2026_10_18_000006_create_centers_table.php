<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('name', 200);
            $table->string('slug', 230)->unique();
            $table->string('logo_path', 255)->nullable();
            $table->string('cover_path', 255)->nullable();
            $table->longText('bio')->nullable();
            $table->smallInteger('founded_year')->nullable();
            $table->string('hotline', 20)->nullable();
            $table->string('contact_email', 191)->nullable();
            $table->string('registered_address', 255)->nullable();
            $table->foreignId('province_id')->nullable()->constrained('administrative_areas')->restrictOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('administrative_areas')->restrictOnDelete();
            $table->foreignId('ward_id')->nullable()->constrained('administrative_areas')->restrictOnDelete();
            $table->string('verification_status', 20)->nullable()->default('unverified');
            $table->dateTime('verified_at')->nullable();
            $table->decimal('rating_avg', 3, 2)->nullable()->default(0);
            $table->integer('review_count')->nullable()->default(0);
            $table->integer('trust_count')->nullable()->default(0);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->dateTime('deleted_at')->nullable();

            $table->index(['verification_status', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centers');
    }
};
