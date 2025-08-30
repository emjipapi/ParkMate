<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('violations', function (Blueprint $table) {
            $table->foreignId('violator_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->after('area_id');

            $table->string('license_plate')
                  ->nullable()
                  ->after('violator_id');
        });
    }
    public function down(): void
    {
        Schema::table('violations', function (Blueprint $table) {
            $table->dropForeign(['violator_id']);
            $table->dropColumn('violator_id');
            $table->dropColumn('license_plate');
        });
    }
};
