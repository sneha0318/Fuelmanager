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
        // Update existing expense entries to remove category name from ledger_section
        // Use the actual expense name from the expenses table for consistency
        $expenseEntries = DB::table('party_lists')
            ->where('group_type', 'Expense')
            ->whereNotNull('expense_id')
            ->get();
            
        foreach ($expenseEntries as $entry) {
            // Get the actual expense name from the expenses table
            $expense = DB::table('expenses')->where('id', $entry->expense_id)->first();
            if ($expense) {
                DB::table('party_lists')
                    ->where('id', $entry->id)
                    ->update(['ledger_section' => $expense->name]);
            } else {
                // Fallback: If ledger_section is in format "ExpenseName (CategoryName)", extract just the expense name
                if (preg_match('/^(.+?)\s*\(.*$/', $entry->ledger_section, $matches)) {
                    $expenseName = trim($matches[1]);
                    
                    DB::table('party_lists')
                        ->where('id', $entry->id)
                        ->update(['ledger_section' => $expenseName]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // To reverse, we would need to add category names back
        // This requires looking up the expense and its category
        $expenseEntries = DB::table('party_lists')
            ->where('group_type', 'Expense')
            ->whereNotNull('expense_id')
            ->get();
            
        foreach ($expenseEntries as $entry) {
            // Get the expense and its category
            $expense = DB::table('expenses')->where('id', $entry->expense_id)->first();
            if ($expense) {
                $category = DB::table('expenses_categories')->where('id', $expense->category_id)->first();
                if ($category) {
                    $newLedgerSection = $expense->name . ' (' . $category->category_name . ')';
                    
                    DB::table('party_lists')
                        ->where('id', $entry->id)
                        ->update(['ledger_section' => $newLedgerSection]);
                }
            }
        }
    }
};
