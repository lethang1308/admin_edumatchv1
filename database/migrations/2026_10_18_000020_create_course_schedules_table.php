<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('course_batches')->restrictOnDelete();
            $table->tinyInteger('weekday');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('delivery_mode', 20);
            $table->string('note', 255)->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['batch_id', 'weekday']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_schedules');
    }
};
