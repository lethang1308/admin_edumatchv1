<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('center_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('centers')->restrictOnDelete();
            $table->string('name', 180);
            $table->string('address_line', 255);
            $table->foreignId('province_id')->nullable()->constrained('administrative_areas')->restrictOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('administrative_areas')->restrictOnDelete();
            $table->foreignId('ward_id')->nullable()->constrained('administrative_areas')->restrictOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_main')->nullable()->default(false);
            $table->string('status', 20)->nullable()->default('active');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['center_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_branches');
    }
};
