<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
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
            $table->string('profile_picture')->nullable();
        });
    }

    public function down(): void {
        Schema::dropIfExists('users');
    }
};

