<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('center_id')->nullable()->constrained('centers')->restrictOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->string('title', 220);
            $table->string('slug', 240)->unique();
            $table->string('short_description', 500)->nullable();
            $table->longText('description');
            $table->text('target_audience')->nullable();
            $table->text('outcomes')->nullable();
            $table->string('teaching_mode', 20);
            $table->decimal('price_amount', 12, 2)->nullable();
            $table->string('price_unit', 20)->nullable()->default('course');
            $table->string('schedule_text', 255)->nullable();
            $table->foreignId('province_id')->nullable()->constrained('administrative_areas')->restrictOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('administrative_areas')->restrictOnDelete();
            $table->foreignId('ward_id')->nullable()->constrained('administrative_areas')->restrictOnDelete();
            $table->string('moderation_status', 20)->nullable()->default('draft');
            $table->string('enrollment_status', 20)->nullable()->default('open');
            $table->text('rejection_reason')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->integer('view_count')->nullable()->default(0);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->dateTime('deleted_at')->nullable();

            $table->index(['subject_id', 'moderation_status', 'price_amount']);
            $table->index(['teacher_user_id', 'moderation_status']);
            $table->index(['center_id', 'moderation_status']);
            $table->index(['moderation_status', 'published_at']);
            $table->index(['province_id', 'district_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
