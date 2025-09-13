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
        Schema::table('users', function (Blueprint $table) {
            // Add serial_number after id
            $table->char('serial_number', 6)->after('id');

            // Add year_section, address, contact_number after program
            $table->char('year_section', 2)->after('program');
            $table->string('address')->after('year_section');
            $table->string('contact_number')->after('address');

            // Add expiration_date after license_number
            $table->string('expiration_date')->after('license_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'serial_number',
                'year_section',
                'address',
                'contact_number',
                'expiration_date',
            ]);
        });
    }
};
