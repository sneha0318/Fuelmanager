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
        // First drop the table if it exists
        Schema::dropIfExists('vehicles');
        
        // Then create the table with new structure
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->onDelete('cascade');
            $table->string('driver_name');
            $table->string('mobile_number');
            $table->string('vehicle_name');
            $table->string('vehicle_number');
            $table->string('bill_no')->nullable();
            $table->decimal('rate', 10, 2)->nullable();
            $table->decimal('litre', 10, 2)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
}; 