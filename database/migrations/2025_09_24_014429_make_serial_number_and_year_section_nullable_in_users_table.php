<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('serial_number')->nullable()->change();
            $table->string('year_section')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Rollback-safe: keep these columns nullable to avoid failures when
        // previous migrations re-create columns with NULL values.
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'serial_number')) {
                $table->string('serial_number')->nullable()->change();
            }
            if (Schema::hasColumn('users', 'year_section')) {
                $table->string('year_section')->nullable()->change();
            }
        });
    }
};
