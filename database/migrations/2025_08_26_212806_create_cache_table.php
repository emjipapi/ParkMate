<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCacheTable extends Migration
{
    public function up()
    {
        Schema::create('cache', function (Blueprint $table) {

		$table->string('key');
		;
		$table->integer('expiration',11);
		$table->primary('key');

        });
    }

    public function down()
    {
        Schema::dropIfExists('cache');
    }
}