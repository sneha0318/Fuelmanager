<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('pump_id')->after('sale_date')->constrained('pumps')->onDelete('cascade');
            $table->foreignId('nozzle_id')->after('pump_id')->constrained('nozzles')->onDelete('cascade');
            // Drop the old nozzle column since we're replacing it with nozzle_id
            $table->dropColumn('nozzle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['pump_id']);
            $table->dropForeign(['nozzle_id']);
            $table->dropColumn(['pump_id', 'nozzle_id']);
            $table->string('nozzle')->after('sale_date');
        });
    }
};
