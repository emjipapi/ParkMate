<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogsTable extends Migration
{
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {

		$table->bigIncrements('id')->unsigned();
		$table->string('actor_type');
		$table->bigInteger('actor_id',20)->unsigned();
		$table->bigInteger('area_id',20)->unsigned()->nullable()->default('NULL');
		$table->string('action');
		$table->text('details')->nullable()->default('NULL');
		$table->timestamp('created_at')->default('current_timestamp');
		$table->primary('id');

        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
}