<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\PartyList;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, remove all Individual Expense party list entries
        PartyList::where('group_type', 'Individual Expense')->delete();
        
        // Then remove the expense_id column and foreign key
        Schema::table('party_lists', function (Blueprint $table) {
            $table->dropForeign(['expense_id']);
            $table->dropColumn('expense_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the expense_id column and foreign key
        Schema::table('party_lists', function (Blueprint $table) {
            $table->unsignedBigInteger('expense_id')->nullable()->after('ledger_section');
            $table->foreign('expense_id')->references('id')->on('expenses')->onDelete('cascade');
        });
    }
};
