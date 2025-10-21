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
        Schema::dropIfExists('parking_slots');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('parking_slots', function (Blueprint $table) {
            $table->id();
            // Recreate columns here if you ever need to roll back
            $table->timestamps();
        });
    }
};
