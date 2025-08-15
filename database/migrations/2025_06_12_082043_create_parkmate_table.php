<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admins Table
        Schema::create('admins', function (Blueprint $table) {
            $table->id('admin_id');
            $table->string('username', 30);
            $table->string('firstname', 50);
            $table->string('middlename', 50);
            $table->string('lastname', 50);
            $table->string('password', 20);
        });

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
            $table->string('rfid_tag', 10);
            $table->string('firstname', 50);
            $table->string('middlename', 50);
            $table->string('lastname', 50);
            $table->string('program', 50);
            $table->string('department', 50);
            $table->string('license_number', 13);
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
