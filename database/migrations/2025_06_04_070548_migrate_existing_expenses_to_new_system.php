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
        
        $expensesWithoutCategory = \DB::table('expenses')->whereNull('category_id')->exists();
        
        if (!$expensesWithoutCategory) {
           
            return;
        }
        
        // Get the first user ID if any users exist, otherwise use null
        $firstUserId = \App\Models\User::first()?->id;
        
        // Create a default expense category for existing expenses without categories
        $defaultCategory = \App\Models\ExpensesCategory::firstOrCreate(
            ['category_name' => 'General Expenses'],
            [
                'created_by' => $firstUserId,
                'updated_by' => $firstUserId,
            ]
        );

        // Update existing expenses with null category_id to use the default category
        \DB::table('expenses')
            ->whereNull('category_id')
            ->update(['category_id' => $defaultCategory->id]);

        // Create corresponding party list entries for the default category
        \App\Models\PartyList::firstOrCreate(
            [
                'group_type' => 'Expenses',
                'ledger_section' => 'General Expenses'
            ],
            [
                'status' => 'active',
                'created_by' => $firstUserId,
                'edited_by' => $firstUserId,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the default category and reset expenses
        $defaultCategory = \App\Models\ExpensesCategory::where('category_name', 'General Expenses')->first();
        if ($defaultCategory) {
            // Reset expenses to null category_id
            \DB::table('expenses')
                ->where('category_id', $defaultCategory->id)
                ->update(['category_id' => null]);
            
            // Remove the party list entry
            \App\Models\PartyList::where('group_type', 'Expenses')
                ->where('ledger_section', 'General Expenses')
                ->delete();
            
            // Remove the default category
            $defaultCategory->delete();
        }
    }
};
