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
        Schema::create('payment_items_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nozzle_id')->constrained('nozzles')->onDelete('cascade');
            $table->foreignId('payment_item_id')->constrained('payments')->onDelete('cascade');
            $table->string('bill_no');
            $table->decimal('litre', 10, 2);
            $table->decimal('rate', 10, 2);
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('completed');
            $table->string('customer_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_items_entries');
    }
};
