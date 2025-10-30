<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_passes', function (Blueprint $table) {
            $table->dropColumn('reason');
        });
    }

    public function down(): void
    {
        Schema::table('guest_passes', function (Blueprint $table) {
            $table->string('reason')->nullable();
        });
    }
};