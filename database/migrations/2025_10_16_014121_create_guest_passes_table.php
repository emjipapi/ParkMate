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
        Schema::create('guest_passes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('A friendly name for the pass, e.g., Guest Pass 01');
            $table->string('rfid_tag')->unique();
            $table->string('status')->default('available')->comment('e.g., available, in_use');
            
            // This is the reason for the guest's visit, filled upon assignment.
            $table->string('reason')->nullable();

            // This links to the guest's record in the users table.
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes(); // Adds the `deleted_at` column for soft deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_passes');
    }
};
