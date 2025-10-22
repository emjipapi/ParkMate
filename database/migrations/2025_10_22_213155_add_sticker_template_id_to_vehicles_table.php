<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration:
     * 1. Converts `sticker_templates` to InnoDB so it can be referenced by a foreign key.
     * 2. Adds `sticker_template_id` (nullable) after `license_plate` on `vehicles`.
     * 3. Adds FK constraint to `sticker_templates(id)` with ON DELETE SET NULL.
     *
     * Note: converting engine may lock the table during the operation; run during maintenance if the table is large.
     */
    public function up(): void
    {
        // 1) Convert sticker_templates to InnoDB so it supports FKs
        DB::statement('ALTER TABLE `sticker_templates` ENGINE=InnoDB');

        // 2) Add column and foreign key to vehicles
        Schema::table('vehicles', function (Blueprint $table) {
            $table->unsignedBigInteger('sticker_template_id')->nullable()->after('license_plate');
            $table->index('sticker_template_id');
            $table->foreign('sticker_template_id')
                ->references('id')
                ->on('sticker_templates')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * This drops the FK and column. Optionally you can convert `sticker_templates` back to MyISAM
     * if you absolutely need to preserve the original engine, but keeping it InnoDB is generally
     * preferable for referential integrity.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // drop foreign first, then index, then column
            $table->dropForeign(['sticker_template_id']);
            $table->dropIndex(['sticker_template_id']);
            $table->dropColumn('sticker_template_id');
        });

        // OPTIONAL: convert sticker_templates back to MyISAM if you must restore original engine
        // DB::statement('ALTER TABLE `sticker_templates` ENGINE=MyISAM');
    }
};
