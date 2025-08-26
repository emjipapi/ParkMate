<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminsTable extends Migration
{
    public function up()
    {
        Schema::create('admins', function (Blueprint $table) {

		$table->bigIncrements('admin_id')->unsigned();
		$table->string('username',30);
		$table->string('firstname',50);
		$table->string('middlename',50);
		$table->string('lastname',50);
		$table->string('password');
		$table->primary('admin_id');

        });
    }

    public function down()
    {
        Schema::dropIfExists('admins');
    }
}