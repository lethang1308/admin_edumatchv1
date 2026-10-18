<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_user_id')->constrained('users')->restrictOnDelete();
            $table->string('target_type', 30);
            $table->bigInteger('target_id');
            $table->string('reason', 100);
            $table->text('description')->nullable();
            $table->string('status', 20)->nullable()->default('open');
            $table->foreignId('handled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('admin_note')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index(['status', 'created_at']);
            $table->index(['target_type', 'target_id']);
            $table->index(['reporter_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
