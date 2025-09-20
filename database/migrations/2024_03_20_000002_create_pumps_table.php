<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pumps', function (Blueprint $table) {
            $table->id();
            $table->string('pump_name');
            $table->string('location')->nullable();;
            $table->integer('total_nozzles')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pumps');
    }
}; 