<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('parking_areas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100);
            $table->unsignedInteger('moto_total')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('parking_areas');
    }
};
