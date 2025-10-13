<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesToAdminsTable extends Migration
{
    public function up()
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->softDeletes(); // adds nullable deleted_at
        });
    }

    public function down()
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
