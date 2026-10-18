<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('center_id')->nullable()->constrained('centers')->restrictOnDelete();
            $table->string('title', 220)->nullable();
            $table->longText('content');
            $table->string('status', 20)->nullable()->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->integer('like_count')->nullable()->default(0);
            $table->integer('comment_count')->nullable()->default(0);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->dateTime('deleted_at')->nullable();

            $table->index(['author_user_id', 'status']);
            $table->index(['center_id', 'status']);
            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_posts');
    }
};
