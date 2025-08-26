<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSessionsTable extends Migration
{
    public function up()
    {
        Schema::create('sessions', function (Blueprint $table) {

		$table->string('id');
		$table->bigInteger('user_id',20)->unsigned()->nullable()->default('NULL');
		$table->string('ip_address',45)->nullable()->default('NULL');
		$table->text('user_agent')->nullable()->default('NULL');
		$table->text('payload');
		$table->integer('last_activity',11);
		$table->primary('id');

        });
    }

    public function down()
    {
        Schema::dropIfExists('sessions');
    }
}