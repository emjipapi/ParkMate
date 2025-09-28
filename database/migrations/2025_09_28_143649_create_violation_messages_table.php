<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateViolationMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('violation_messages', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Links to violation
            $table->unsignedBigInteger('violation_id');

            // Polymorphic sender: can be App\Models\User or App\Models\Admin
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('sender_type')->nullable();

            $table->text('message');
            $table->enum('type', ['approval', 'rejection']);

            // Only created_at required per your request
            $table->timestamp('created_at')->useCurrent();

            // indexes / foreign keys
            $table->index(['violation_id']);
            $table->index(['sender_type', 'sender_id']);

            $table->foreign('violation_id')
                  ->references('id')->on('violations')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('violation_messages');
    }
}
