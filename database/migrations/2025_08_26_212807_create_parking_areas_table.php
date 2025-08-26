<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParkingAreasTable extends Migration
{
    public function up()
    {
        Schema::create('parking_areas', function (Blueprint $table) {

		$table->bigIncrements('id')->unsigned();
		$table->string('name',100);
		$table->integer('moto_total',10)->unsigned()->default('0');
		$table->timestamp('created_at')->nullable()->default('NULL');
		$table->timestamp('updated_at')->nullable()->default('NULL');
		$table->primary('id');

        });
    }

    public function down()
    {
        Schema::dropIfExists('parking_areas');
    }
}