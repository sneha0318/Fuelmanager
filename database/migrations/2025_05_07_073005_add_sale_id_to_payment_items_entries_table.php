<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSaleIdToPaymentItemsEntriesTable extends Migration
{
    public function up()
    {
        Schema::table('payment_items_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_id')->nullable()->after('id'); // Add sale_id column
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade'); // Add foreign key constraint
        });
    }

    public function down()
    {
        Schema::table('payment_items_entries', function (Blueprint $table) {
            $table->dropForeign(['sale_id']); // Drop foreign key constraint
            $table->dropColumn('sale_id'); // Drop sale_id column
        });
    }
};

