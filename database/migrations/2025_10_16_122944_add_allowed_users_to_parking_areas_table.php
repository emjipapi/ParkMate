<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parking_areas', function (Blueprint $table) {
            $table->boolean('allow_students')->default(true);
            $table->boolean('allow_employees')->default(true);
            $table->boolean('allow_guests')->default(true);
            $table->dropColumn('moto_total'); // Drop moto_total
        });
    }

    public function down(): void
    {
        Schema::table('parking_areas', function (Blueprint $table) {
            $table->dropColumn(['allow_students', 'allow_employees', 'allow_guests']);
            $table->integer('moto_total')->nullable(); // Restore moto_total
        });
    }
};