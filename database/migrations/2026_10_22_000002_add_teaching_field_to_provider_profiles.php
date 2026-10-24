<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->string('teaching_field', 40)->nullable()->after('headline');
        });

        Schema::table('centers', function (Blueprint $table) {
            $table->string('teaching_field', 40)->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropColumn('teaching_field');
        });

        Schema::table('centers', function (Blueprint $table) {
            $table->dropColumn('teaching_field');
        });
    }
};
