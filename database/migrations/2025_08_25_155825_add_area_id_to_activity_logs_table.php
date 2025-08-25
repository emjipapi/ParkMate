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
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('area_id')->nullable()->after('actor_id');
            // Optional: add foreign key if you have a 'parking_areas' table
            // $table->foreign('area_id')->references('id')->on('parking_areas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('area_id');
            // $table->dropForeign(['area_id']); // if you added FK
        });
    }
};
