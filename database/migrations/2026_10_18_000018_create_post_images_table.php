<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->restrictOnDelete();
            $table->string('image_path', 255);
            $table->string('alt_text', 180)->nullable();
            $table->boolean('is_cover')->nullable()->default(false);
            $table->integer('sort_order')->nullable()->default(0);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['post_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_images');
    }
};
