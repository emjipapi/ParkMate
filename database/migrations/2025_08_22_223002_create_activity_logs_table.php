<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type'); // e.g. 'admin', 'student', 'teacher'
            $table->unsignedBigInteger('actor_id'); // user ID
            $table->string('action'); // e.g. 'login', 'logout'
            $table->text('details')->nullable(); // extra info
            $table->timestamp('created_at')->useCurrent(); // log time
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
