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
        Schema::table('loans', function (Blueprint $table) {
            if (!Schema::hasColumn('loans', 'nozzle_id')) {
                $table->unsignedBigInteger('nozzle_id')->nullable();
            }
            
            if (!Schema::hasColumn('loans', 'pump_id')) {
                $table->unsignedBigInteger('pump_id')->nullable();
            }
            
            // Add foreign keys if they don't exist
            if (!Schema::hasColumn('loans', 'nozzle_id')) {
                $table->foreign('nozzle_id')->references('id')->on('nozzles')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('loans', 'pump_id')) {
                $table->foreign('pump_id')->references('id')->on('pumps')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (Schema::hasColumn('loans', 'nozzle_id')) {
                $table->dropForeign(['nozzle_id']);
                $table->dropColumn('nozzle_id');
            }
            
            if (Schema::hasColumn('loans', 'pump_id')) {
                $table->dropForeign(['pump_id']);
                $table->dropColumn('pump_id');
            }
        });
    }
};
