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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operator_id');
            $table->unsignedBigInteger('shift_id');
            $table->date('date');
            

            $table->dateTime('opening_meter_time');
            $table->dateTime('closing_meter_time')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->foreign('operator_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['operator_id', 'shift_id', 'date'], 'unique_attendance');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
