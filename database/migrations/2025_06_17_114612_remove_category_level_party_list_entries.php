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
        // Remove all party list entries that are for expense categories (group_type = 'Expenses')
        // These were created automatically when categories were created, but should not exist
        DB::table('party_lists')
            ->where('group_type', 'Expenses')
            ->whereNull('expense_id') // Only remove category-level entries, not individual expense entries
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot easily reverse this migration as we don't have a way to 
        // recreate the deleted category-level party list entries
        // This is intentional as these entries should not exist
    }
};
