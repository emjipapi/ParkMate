<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehiclesTable extends Migration
{
    public function up()
    {
        Schema::create('vehicles', function (Blueprint $table) {

		$table->bigIncrements('id')->unsigned();
		$table->bigInteger('user_id',20)->unsigned();
		$table->enum('type',['car','motorcycle']);
		$table->string('rfid_tag',20);
		$table->string('license_plate',20)->nullable()->default('NULL');
		$table->timestamp('created_at')->nullable()->default('NULL');
		$table->timestamp('updated_at')->nullable()->default('NULL');
		$table->primary('id');
		$table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicles');
    }
}