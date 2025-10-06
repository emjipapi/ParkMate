<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parking_areas', function (Blueprint $table) {
            $table->dropColumn('moto_total');
        });
    }

    public function down(): void
    {
        Schema::table('parking_areas', function (Blueprint $table) {
            $table->integer('moto_total')->nullable(); // adjust type if different originally
        });
    }
};
