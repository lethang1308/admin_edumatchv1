<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('post_id')->constrained('posts')->restrictOnDelete();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->unique(['student_user_id', 'post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_favorites');
    }
};
