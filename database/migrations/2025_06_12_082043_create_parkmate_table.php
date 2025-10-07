<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {


        // Login Sessions Table
        Schema::create('login_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('admin_id', 20);
            $table->timestamp('timestamp')->useCurrent()->useCurrentOnUpdate();
        });

        // Parking Slots Table
        Schema::create('parking_slots', function (Blueprint $table) {
            $table->id('slot_id');
            $table->string('area_name', 20);
            $table->integer('slot_number');
            $table->boolean('status');
            $table->string('occupied_by', 20);
        });

        // Users Table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('student_id', 10)->nullable();
            $table->string('employee_id', 10)->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('firstname', 50)->nullable();
            $table->string('middlename', 50)->nullable();
            $table->string('lastname', 50)->nullable();
            $table->string('program', 50)->nullable();
            $table->string('department', 50)->nullable();
            $table->string('license_number', 11)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('parking_slots');
        Schema::dropIfExists('login_sessions');
        Schema::dropIfExists('admins');
    }
};
