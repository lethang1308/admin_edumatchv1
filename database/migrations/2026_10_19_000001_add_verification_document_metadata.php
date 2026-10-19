<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_documents', function (Blueprint $table) {
            $table->string('document_name', 200)->nullable();
            $table->string('issuing_authority', 200)->nullable();
            $table->unsignedSmallInteger('issued_year')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('verification_documents', function (Blueprint $table) {
            $table->dropColumn(['document_name', 'issuing_authority', 'issued_year']);
        });
    }
};
