<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Expense;
use App\Models\PartyList;
use App\Models\ExpensesCategory;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create party list entries for existing expenses
        $expenses = Expense::with('category')->get();
        
        foreach ($expenses as $expense) {
            // Check if party list entry already exists
            $existingEntry = PartyList::where('expense_id', $expense->id)->first();
            
            if (!$existingEntry) {
                $category = $expense->category;
                $ledgerSection = $expense->name . ' - ' . ($category ? $category->category_name : 'Expense');
                
                PartyList::create([
                    'group_type' => 'Individual Expense',
                    'ledger_section' => $ledgerSection,
                    'expense_id' => $expense->id,
                    'created_by' => $expense->created_by ?? 1, // Default to user ID 1 if not set
                    'updated_by' => $expense->updated_by ?? 1,
                    'created_at' => $expense->created_at,
                    'updated_at' => $expense->updated_at,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all Individual Expense party list entries
        PartyList::where('group_type', 'Individual Expense')->delete();
    }
};
