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
        Schema::table('violations', function (Blueprint $table) {
            // Move violator_id and license_plate after evidence
            $table->bigInteger('violator_id_temp')->unsigned()->nullable()->after('evidence');
            $table->string('license_plate_temp')->nullable()->after('violator_id_temp');
            
            // Add the new timestamp columns
            $table->timestamp('submitted_at')->nullable()->after('updated_at');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');
            $table->timestamp('endorsed_at')->nullable()->after('approved_at');
            $table->timestamp('resolved_at')->nullable()->after('endorsed_at');
            
            // Update the status enum to include 'resolved'
            $table->enum('status', ['pending', 'approved', 'rejected', 'for_endorsement', 'resolved'])
                  ->default('pending')
                  ->change();
        });

        // Copy data to new columns
        DB::statement('UPDATE violations SET violator_id_temp = violator_id, license_plate_temp = license_plate');

        Schema::table('violations', function (Blueprint $table) {
            // Drop old columns and constraints
            $table->dropForeign(['violator_id']);
            $table->dropColumn(['violator_id', 'license_plate']);
            
            // Rename temp columns to final names
            $table->renameColumn('violator_id_temp', 'violator_id');
            $table->renameColumn('license_plate_temp', 'license_plate');
        });

        Schema::table('violations', function (Blueprint $table) {
            // Re-add the foreign key constraint
            $table->foreign('violator_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('violations', function (Blueprint $table) {
            // Remove the timestamp columns
            $table->dropColumn(['submitted_at', 'approved_at', 'endorsed_at', 'resolved_at']);
            
            // Revert the status enum back to original
            $table->enum('status', ['pending', 'approved', 'rejected', 'for_endorsement'])
                  ->default('pending')
                  ->change();
        });
    }
};