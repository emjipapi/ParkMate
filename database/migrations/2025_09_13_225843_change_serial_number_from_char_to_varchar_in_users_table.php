<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('serial_number', 6)->change(); // no need to reapply unique
        });
    }

    public function down(): void
    {
        // Rollback-safe: don't enforce unique or NOT NULL on down if column might have NULLs
        // from previous migration rollbacks
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'serial_number')) {
                $table->char('serial_number', 6)->nullable()->change();
            }
        });
    }
};
