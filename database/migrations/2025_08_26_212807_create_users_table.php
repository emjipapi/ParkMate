<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {

		$table->bigIncrements('id')->unsigned();
		$table->string('student_id',10)->nullable()->default('NULL');
		$table->string('employee_id',10)->nullable()->default('NULL');
		$table->string('email')->nullable()->default('NULL');
		$table->string('password')->nullable()->default('NULL');
		$table->string('firstname',50)->nullable()->default('NULL');
		$table->string('middlename',50)->nullable()->default('NULL');
		$table->string('lastname',50)->nullable()->default('NULL');
		$table->string('program',50)->nullable()->default('NULL');
		$table->string('department',50)->nullable()->default('NULL');
		$table->string('license_number',11)->nullable()->default('NULL');
		$table->string('profile_picture')->nullable()->default('NULL');
		$table->primary('id');

        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}