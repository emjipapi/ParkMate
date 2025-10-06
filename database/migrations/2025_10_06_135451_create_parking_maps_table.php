<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('parking_maps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_path');
            $table->integer('width');
            $table->integer('height');
            $table->decimal('aspect_ratio', 8, 4);
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->json('area_config')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('parking_maps');
    }
};