<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pump_users', function (Blueprint $table) {
            $table->id();
            $table->string('user_name');
            $table->string('mobile_number');
            $table->string('user_type');
            $table->string('pump_name');
            $table->string('password');
            $table->string('email')->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pump_users');
    }
};