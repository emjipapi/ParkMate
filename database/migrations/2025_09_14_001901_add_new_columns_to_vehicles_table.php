<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('body_type_model', 30)->after('type');
            $table->string('or_number', 30)->after('body_type_model');
            $table->string('cr_number', 30)->after('or_number');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['body_type_model', 'or_number', 'cr_number']);
        });
    }
};
