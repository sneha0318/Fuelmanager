<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all existing 'Individual Expense' records to 'Expense'
        DB::table('party_lists')
            ->where('group_type', 'Individual Expense')
            ->update(['group_type' => 'Expense']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the change back to 'Individual Expense'
        DB::table('party_lists')
            ->where('group_type', 'Expense')
            ->update(['group_type' => 'Individual Expense']);
    }
};
