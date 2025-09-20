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
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('driver_name')->after('loan_id');
            $table->string('mobile_number')->after('driver_name');
            $table->string('vehicle_name')->after('mobile_number');
            $table->string('bill_no')->nullable()->after('vehicle_number');
            $table->decimal('rate', 10, 2)->nullable()->after('bill_no');
            $table->decimal('litre', 10, 2)->nullable()->after('rate');
            $table->decimal('amount', 10, 2)->nullable()->after('litre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('driver_name');
            $table->dropColumn('mobile_number');
            $table->dropColumn('vehicle_name');
            $table->dropColumn('bill_no');
            $table->dropColumn('rate');
            $table->dropColumn('litre');
            $table->dropColumn('amount');
        });
    }
};
