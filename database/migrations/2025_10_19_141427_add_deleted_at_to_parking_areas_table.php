<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parking_areas', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at'); // adds deleted_at column
        });
    }

    public function down(): void
    {
        Schema::table('parking_areas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
