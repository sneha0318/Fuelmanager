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
        Schema::create('sales', function (Blueprint $table) {
            $table->id(); // Sr. No.
            $table->date('sale_date'); // Sale Date
            $table->string('nozzle'); // Nozzle
            $table->decimal('open_meter', 10, 2); // Open Meter
            $table->decimal('close_meter', 10, 2)->nullable(); // Close Meter
            $table->decimal('test_litre', 10, 2)->nullable(); // Test Litre
            $table->decimal('rate', 10, 2); // Rate
            $table->decimal('net_sale_litre', 10, 2)->nullable(); // Net Sale (Ltr)
            $table->decimal('variation', 10, 2)->nullable(); // Variation between expected and actual closing meter
            $table->enum('status', ['Pending', 'Completed']); // Status

            $table->date('close_date')->nullable(); // Sale close Date
            $table->timestamps(); // Created On


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
