<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('center_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('centers')->restrictOnDelete();
            $table->string('full_name', 150);
            $table->string('phone', 20)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('status', 20)->nullable()->default('active');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['center_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_staff');
    }
};
