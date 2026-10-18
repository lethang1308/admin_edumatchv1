<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('chat_messages')->restrictOnDelete();
            $table->string('file_path', 255);
            $table->string('file_name', 255);
            $table->string('mime_type', 100)->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_attachments');
    }
};
