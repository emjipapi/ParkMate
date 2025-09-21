<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modify ENUM to add 'for_endorsement'
        DB::statement("ALTER TABLE violations MODIFY COLUMN status ENUM('pending','approved','rejected','for_endorsement','resolved') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Rollback: remove 'for_endorsement'
        DB::statement("ALTER TABLE violations MODIFY COLUMN status ENUM('pending','approved','rejected','resolved') NOT NULL DEFAULT 'pending'");
    }
};
