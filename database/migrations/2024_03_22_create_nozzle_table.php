<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('nozzles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pump_id')->constrained('pumps')->onDelete('cascade');
            $table->integer('nozzle_number');
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('nozzles');
    }
};