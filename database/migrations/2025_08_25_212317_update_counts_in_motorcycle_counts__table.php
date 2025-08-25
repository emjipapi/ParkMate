<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('motorcycle_counts', function (Blueprint $table) {
            // Add a "total_available" column (default 0 so it's safe)
            $table->unsignedInteger('total_available')->default(0)->after('available');
        });
    }

    public function down(): void
    {
        Schema::table('motorcycle_counts', function (Blueprint $table) {
            $table->dropColumn('total_available');
        });
    }
};
