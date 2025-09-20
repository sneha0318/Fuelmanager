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
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'edited_by')) {
                $table->foreignId('edited_by')->nullable()->after('updated_by')->constrained('users');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'edited_by')) {
                $table->dropForeign(['edited_by']);
                $table->dropColumn('edited_by');
            }
        });
    }
};
