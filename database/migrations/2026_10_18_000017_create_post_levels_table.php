<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->restrictOnDelete();
            $table->foreignId('level_id')->constrained('education_levels')->restrictOnDelete();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->unique(['post_id', 'level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_levels');
    }
};
