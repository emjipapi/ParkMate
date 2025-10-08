<?php

// Migration file: database/migrations/XXXX_XX_XX_create_unknown_rfid_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unknown_rfid_logs', function (Blueprint $table) {
            $table->id();
            $table->string('rfid_tag');
            $table->unsignedBigInteger('area_id')->nullable(); // NULL = main gate
            $table->timestamp('created_at');
            
            $table->index(['rfid_tag', 'created_at']);
            $table->foreign('area_id')->references('id')->on('parking_areas')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unknown_rfid_logs');
    }
};