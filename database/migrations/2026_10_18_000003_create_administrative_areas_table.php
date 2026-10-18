<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('administrative_areas')->restrictOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('level', 20);
            $table->boolean('is_active')->nullable()->default(true);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['parent_id', 'level', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_areas');
    }
};
