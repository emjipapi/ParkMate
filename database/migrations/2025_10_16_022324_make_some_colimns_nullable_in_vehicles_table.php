<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Change the specified columns to be nullable
            $table->string('body_type_model')->nullable()->change();
            $table->string('or_number')->nullable()->change();
            $table->string('cr_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Revert the columns to be non-nullable
            $table->string('body_type_model')->nullable(false)->change();
            $table->string('or_number')->nullable(false)->change();
            $table->string('cr_number')->nullable(false)->change();
        });
    }
};
