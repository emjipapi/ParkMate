<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Select the 'users' table to modify it
        Schema::table('users', function (Blueprint $table) {
            // Change the 'address' column to be nullable
            $table->string('address')->nullable()->change();
            $table->string('expiration_date')->nullable()->change();
            $table->string('contact_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert the 'address' column to be non-nullable
            $table->string('address')->nullable(false)->change();
        });
    }
};
