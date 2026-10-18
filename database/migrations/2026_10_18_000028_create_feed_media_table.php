<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_post_id')->constrained('feed_posts')->restrictOnDelete();
            $table->string('media_type', 20);
            $table->string('file_path', 255);
            $table->string('thumbnail_path', 255)->nullable();
            $table->integer('sort_order')->nullable()->default(0);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['feed_post_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_media');
    }
};
