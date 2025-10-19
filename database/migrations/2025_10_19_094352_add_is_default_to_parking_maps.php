<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('parking_maps', function (Blueprint $table) {
            // boolean column, default false
            $table->boolean('is_default')->default(false)->after('area_config');
        });
    }

    public function down()
    {
        Schema::table('parking_maps', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};
