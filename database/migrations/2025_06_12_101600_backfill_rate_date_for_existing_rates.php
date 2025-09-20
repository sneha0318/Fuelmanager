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
        // Update existing rates to have a rate_date based on their created_at date
        DB::table('rates')
            ->whereNull('rate_date')
            ->update([
                'rate_date' => DB::raw('DATE(created_at)')
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this as it's just data population
    }
};
