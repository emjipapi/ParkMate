<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('area_id')->nullable()->constrained('parking_areas')->onDelete('set null');
            $table->text('description');
            $table->string('evidence')->nullable();
            $table->enum('status', ['pending','approved','rejected','resolved'])->default('pending');
            $table->text('action_taken')->nullable();
            $table->timestamps(); // creates created_at and updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('violations');
    }
};
