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
        Schema::table('party_lists', function (Blueprint $table) {
            $table->unsignedBigInteger('expense_id')->nullable()->after('ledger_section');
            $table->foreign('expense_id')->references('id')->on('expenses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('party_lists', function (Blueprint $table) {
            $table->dropForeign(['expense_id']);
            $table->dropColumn('expense_id');
        });
    }
};
