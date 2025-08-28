<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('motorcycle_counts', function (Blueprint $table) {
            $table->unsignedInteger('total_available')->default(0)->after('available_count');
        });
    }

    public function down()
    {
        Schema::table('motorcycle_counts', function (Blueprint $table) {
            $table->dropColumn('total_available');
        });
    }
};
