<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('banners')->whereRaw('CHAR_LENGTH(title) > 180')->exists()) {
            throw new RuntimeException('Cannot upgrade banners: shorten legacy titles to 180 characters first.');
        }

        // Older MariaDB versions (including the XAMPP distribution) do not
        // support Laravel's generated `RENAME COLUMN` syntax. `CHANGE COLUMN`
        // is supported there, but requires the current column definition.
        DB::statement('ALTER TABLE `banners`
            CHANGE COLUMN `image` `image_path` VARCHAR(255) NOT NULL,
            CHANGE COLUMN `link` `link_url` VARCHAR(255) NULL,
            CHANGE COLUMN `order` `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
            CHANGE COLUMN `start_date` `starts_at` TIMESTAMP NULL,
            CHANGE COLUMN `end_date` `ends_at` TIMESTAMP NULL');
        Schema::table('banners', function (Blueprint $table) {
            $table->string('title', 180)->nullable()->change();
            $table->string('link_url', 500)->nullable()->change();
            $table->integer('sort_order')->nullable()->default(0)->change();
            $table->string('status', 20)->nullable()->default('active')->change();
            $table->dateTime('starts_at')->nullable()->change();
            $table->dateTime('ends_at')->nullable()->change();
            // Retained for the existing banner admin form.
            $table->string('position')->nullable()->change();
        });
        DB::table('banners')->where('status', '1')->update(['status' => 'active']);
        DB::table('banners')->where('status', '0')->update(['status' => 'inactive']);
        DB::table('banners')->whereNull('created_at')->update(['created_at' => now()]);
        DB::table('banners')->whereNull('updated_at')->update(['updated_at' => now()]);
        Schema::table('banners', function (Blueprint $table) {
            $table->dateTime('created_at')->nullable(false)->change();
            $table->dateTime('updated_at')->nullable(false)->change();
            $table->index(['status', 'sort_order']);
        });
    }

    public function down(): void
    {
        // Restore legacy sizes only when the newer data fits, before any DDL.
        if (DB::table('banners')->whereRaw('CHAR_LENGTH(link_url) > 255 OR sort_order < 0')->exists()) {
            throw new RuntimeException('Cannot downgrade banners: link_url must fit 255 characters and sort_order must be nonnegative.');
        }

        Schema::table('banners', function (Blueprint $table) {
            $table->dropIndex(['status', 'sort_order']);
        });
        DB::table('banners')->update(['status' => DB::raw("CASE WHEN status = 'active' THEN '1' ELSE '0' END")]);
        DB::table('banners')->whereNull('title')->update(['title' => '']);
        DB::table('banners')->whereNull('position')->update(['position' => '']);
        DB::table('banners')->whereNull('sort_order')->update(['sort_order' => 1]);
        Schema::table('banners', function (Blueprint $table) {
            $table->string('title')->nullable(false)->change();
            $table->string('position')->nullable(false)->change();
            $table->string('link_url')->nullable()->change();
            $table->unsignedInteger('sort_order')->nullable(false)->default(1)->change();
            $table->boolean('status')->nullable(false)->default(true)->change();
            $table->timestamp('starts_at')->nullable()->change();
            $table->timestamp('ends_at')->nullable()->change();
            $table->timestamp('created_at')->nullable()->change();
            $table->timestamp('updated_at')->nullable()->change();
        });
        // See the compatibility note in up(): MariaDB uses CHANGE COLUMN.
        DB::statement('ALTER TABLE `banners`
            CHANGE COLUMN `image_path` `image` VARCHAR(255) NOT NULL,
            CHANGE COLUMN `link_url` `link` VARCHAR(255) NULL,
            CHANGE COLUMN `sort_order` `order` INT UNSIGNED NOT NULL DEFAULT 1,
            CHANGE COLUMN `starts_at` `start_date` TIMESTAMP NULL,
            CHANGE COLUMN `ends_at` `end_date` TIMESTAMP NULL');
    }
};
