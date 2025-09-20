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
        Schema::table('pump_users', function (Blueprint $table) {
            // First, make pump_name nullable
            $table->string('pump_name')->nullable()->change();
            
            // Then add pump_id if it doesn't exist
            if (!Schema::hasColumn('pump_users', 'pump_id')) {
                $table->foreignId('pump_id')->nullable()->constrained('pumps')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pump_users', function (Blueprint $table) {
            // Make pump_name required again
            $table->string('pump_name')->nullable(false)->change();
            
            // Remove pump_id if it exists
            if (Schema::hasColumn('pump_users', 'pump_id')) {
                $table->dropForeign(['pump_id']);
                $table->dropColumn('pump_id');
            }
        });
    }
}; 