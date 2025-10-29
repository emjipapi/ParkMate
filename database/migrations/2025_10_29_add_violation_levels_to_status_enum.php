<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modify ENUM to add first_violation, second_violation, third_violation
        DB::statement("ALTER TABLE violations MODIFY COLUMN status ENUM('pending','approved','rejected','for_endorsement','resolved','first_violation','second_violation','third_violation') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Rollback: remove the new violation levels
        DB::statement("ALTER TABLE violations MODIFY COLUMN status ENUM('pending','approved','rejected','for_endorsement','resolved') NOT NULL DEFAULT 'pending'");
    }
};
