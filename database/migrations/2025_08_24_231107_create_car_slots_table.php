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
        Schema::create('car_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')
                  ->constrained('parking_areas')
                  ->onDelete('cascade');
            $table->string('label', 10); // e.g., C1, N1
            $table->boolean('occupied')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_slots');
    }
};
