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
        // Update existing "Expense Entry" records to "Expense"
        DB::table('party_lists')
            ->where('group_type', 'Expense Entry')
            ->update(['group_type' => 'Expense']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert "Expense" records back to "Expense Entry"
        DB::table('party_lists')
            ->where('group_type', 'Expense')
            ->whereNotNull('expense_id') // Only revert those that have expense_id (were expense entries)
            ->update(['group_type' => 'Expense Entry']);
    }
};
