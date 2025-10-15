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
        Schema::create('guest_tags', function (Blueprint $table) {
            $table->id();
            
            // The custom name for the tag (e.g., "Guest Pass 01")
            $table->string('name');
            
            // The actual RFID code, which must be unique.
            $table->string('rfid_tag')->unique();
            
            // Tracks the current state of the tag.
            // Can be 'available', 'in_use', 'lost', etc.
            $table->string('status')->default('available');
            
            $table->timestamps();
            $table->softDeletes(); // For tracking lost or retired tags without deleting the record
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_tags');
    }
};
