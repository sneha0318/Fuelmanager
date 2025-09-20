<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->string('petrol_pump');
            $table->text('item_desc')->nullable(); // Updated field name from payment_method
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('created_on')->default(now()); // Custom timestamp for creation
            $table->timestamp('edited_on')->nullable(); // Timestamp for last edit
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
};