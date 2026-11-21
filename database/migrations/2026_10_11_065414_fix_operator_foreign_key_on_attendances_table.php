
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign('attendances_operator_id_foreign');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->foreign('operator_id')
                ->references('id')
                ->on('pump_users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign('attendances_operator_id_foreign');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->foreign('operator_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
