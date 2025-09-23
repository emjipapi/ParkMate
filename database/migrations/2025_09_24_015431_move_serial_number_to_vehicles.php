<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove from users
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('serial_number');
        });

        // Add to vehicles
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('serial_number')->nullable()->after('id');
        });
    }

    public function down(): void
    {
        // Re-add to users
        Schema::table('users', function (Blueprint $table) {
            $table->string('serial_number')->nullable();
        });

        // Remove from vehicles
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('serial_number');
        });
    }
};
