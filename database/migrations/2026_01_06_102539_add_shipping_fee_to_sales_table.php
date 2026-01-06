<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('shipping_fee_usd', 15, 2)->default(0)->after('discount_vnd');
            $table->decimal('shipping_fee_vnd', 15, 2)->default(0)->after('shipping_fee_usd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['shipping_fee_usd', 'shipping_fee_vnd']);
        });
    }
};
