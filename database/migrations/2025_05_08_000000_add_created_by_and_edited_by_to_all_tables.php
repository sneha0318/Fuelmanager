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
        $tables = [
            'users',
            'items',
            'payment_items',
            'pumps',
            'pump_users',
            'nozzles',
            'shifts',
            'payment_items_entries',
            'loans',
            'vehicles',
            'sales',
            'rates',
            'payments'
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if ($tableName === 'payments') {
                    if (!Schema::hasColumn($tableName, 'created_by')) {
                        $table->foreignId('created_by')->nullable()->after('created_on')->constrained('users');
                    }
                    if (!Schema::hasColumn($tableName, 'edited_by')) {
                        $table->foreignId('edited_by')->nullable()->after('edited_on')->constrained('users');
                    }
                } else {
                    if (!Schema::hasColumn($tableName, 'created_by')) {
                        $table->foreignId('created_by')->nullable()->after('created_at')->constrained('users');
                    }
                    if (!Schema::hasColumn($tableName, 'edited_by')) {
                        $table->foreignId('edited_by')->nullable()->after('updated_at')->constrained('users');
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'users',
            'items',
            'payment_items',
            'pumps',
            'pump_users',
            'nozzles',
            'shifts',
            'payment_items_entries',
            'loans',
            'vehicles',
            'sales',
            'rates',
            'payments'
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->dropColumn('created_by');
                }
                if (Schema::hasColumn($tableName, 'edited_by')) {
                    $table->dropForeign(['edited_by']);
                    $table->dropColumn('edited_by');
                }
            });
        }
    }
}; 