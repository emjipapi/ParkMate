<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('guest_pass_id')->constrained('guest_passes')->onDelete('cascade');
            $table->string('reason'); // delivery, visitor, service, etc
            $table->enum('vehicle_type', ['motorcycle', 'car']);
            $table->string('license_plate');
            $table->foreignId('registered_by')->constrained('admins')->onDelete('set null')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_registrations');
    }
};