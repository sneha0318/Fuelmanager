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
        Schema::table('expenses', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['category_id']);
            
            // Add the new foreign key constraint to expenses_categories table
            $table->foreign('category_id')->references('id')->on('expenses_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Drop the foreign key to expenses_categories
            $table->dropForeign(['category_id']);
            
            // Restore the original foreign key to categories table
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
        });
    }
};
