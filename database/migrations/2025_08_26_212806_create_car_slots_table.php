<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarSlotsTable extends Migration
{
    public function up()
    {
        Schema::create('car_slots', function (Blueprint $table) {

		$table->bigIncrements('id')->unsigned();
		$table->bigInteger('area_id',20)->unsigned();
		$table->string('label',10);
		$table->tinyInteger('occupied',1)->default('0');
		$table->timestamp('created_at')->nullable()->default('NULL');
		$table->timestamp('updated_at')->nullable()->default('NULL');
		$table->timestamp('last_seen')->nullable()->default('NULL');
		$table->primary('id');
		$table->foreign('area_id')->references('id')->on('parking_areas');
        });
    }

    public function down()
    {
        Schema::dropIfExists('car_slots');
    }
}