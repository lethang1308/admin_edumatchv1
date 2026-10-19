<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('name', 150)->change();
            $table->string('slug', 180)->change();
        });
    }

    public function down(): void
    {
        if (DB::table('subjects')->whereRaw('CHAR_LENGTH(name) > 120 OR CHAR_LENGTH(slug) > 140')->exists()) {
            throw new RuntimeException('Shorten subject names/slugs before rolling back this migration.');
        }
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('name', 120)->change();
            $table->string('slug', 140)->change();
        });
    }
};
