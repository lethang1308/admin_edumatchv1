<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_verification_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('destination', 191);
            $table->string('channel', 20);
            $table->string('purpose', 30);
            $table->string('token_hash', 255);
            $table->dateTime('expires_at');
            $table->dateTime('used_at')->nullable();
            $table->tinyInteger('attempt_count')->nullable()->default(0);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['destination', 'purpose', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_verification_tokens');
    }
};
