<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Change employee_id to varchar(15)
            $table->string('employee_id', 15)->nullable()->change();

            // Add soft deletes
            $table->softDeletes(); // adds a nullable deleted_at column
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert employee_id back to original (assuming varchar(10))
            $table->string('employee_id', 10)->nullable()->change();

            // Remove soft deletes column
            $table->dropSoftDeletes();
        });
    }
};
