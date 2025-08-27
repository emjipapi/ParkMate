<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('car_slots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('area_id');
            $table->string('label', 10);
            $table->boolean('occupied')->default(0);
            $table->timestamps(); // adds created_at and updated_at
            $table->timestamp('last_seen')->nullable();

            $table->foreign('area_id')->references('id')->on('parking_areas')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('car_slots');
    }
};
