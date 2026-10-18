<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('center_id')->nullable()->constrained('centers')->restrictOnDelete();
            $table->foreignId('post_id')->nullable()->constrained('posts')->restrictOnDelete();
            $table->integer('sort_order')->nullable()->default(0);
            $table->boolean('is_active')->nullable()->default(true);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_features');
    }
};
