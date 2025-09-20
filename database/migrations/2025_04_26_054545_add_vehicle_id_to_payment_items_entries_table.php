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
        Schema::table('payment_items_entries', function (Blueprint $table) {
            $table->string('driver_name')->nullable()->after('customer_name');
            $table->string('mobile_number')->nullable()->after('driver_name');
            $table->string('vehicle_name')->nullable()->after('mobile_number');
            $table->string('vehicle_number')->nullable()->after('vehicle_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_items_entries', function (Blueprint $table) {
            $table->dropColumn('driver_name');
            $table->dropColumn('mobile_number');
            $table->dropColumn('vehicle_name');
            $table->dropColumn('vehicle_number');
        });
    }
};
