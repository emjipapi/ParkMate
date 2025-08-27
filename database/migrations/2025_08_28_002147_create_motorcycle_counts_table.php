<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('motorcycle_counts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('area_id');
            $table->unsignedInteger('available_count')->default(0);
            $table->unsignedInteger('total_available')->default(0);
            $table->timestamps();

            $table->foreign('area_id')->references('id')->on('parking_areas')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('motorcycle_counts');
    }
};
