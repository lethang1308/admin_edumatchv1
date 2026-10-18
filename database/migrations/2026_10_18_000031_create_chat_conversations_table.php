<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('teacher_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('center_id')->nullable()->constrained('centers')->restrictOnDelete();
            $table->foreignId('post_id')->nullable()->constrained('posts')->restrictOnDelete();
            $table->string('status', 20)->nullable()->default('active');
            $table->dateTime('last_message_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['student_user_id', 'last_message_at']);
            $table->index(['teacher_user_id', 'last_message_at']);
            $table->index(['center_id', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};
