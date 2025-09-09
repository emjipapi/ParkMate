<?php
// database/migrations/xxxx_xx_xx_create_sticker_templates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sticker_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_path');
            $table->integer('width');
            $table->integer('height');
            $table->decimal('aspect_ratio', 8, 4);
            $table->json('element_config')->nullable();
            $table->enum('status', ['draft', 'active'])->default('draft');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sticker_templates');
    }
};