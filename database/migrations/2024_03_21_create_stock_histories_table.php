<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained()->onDelete('cascade');
            $table->decimal('previous_quantity', 10, 2);
            $table->decimal('added_quantity', 10, 2);
            $table->decimal('new_quantity', 10, 2);
            $table->timestamp('date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_histories');
    }
}; 