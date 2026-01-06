<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Thêm các cột giảm giá bằng số tiền (ngoài giảm giá theo %)
     */
    public function up(): void
    {
        // Thêm cột cho sale_items (giảm giá từng sản phẩm)
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('discount_amount_usd', 10, 2)->nullable()->default(0)->after('discount_percent')->comment('Giảm giá bằng số tiền (USD)');
            $table->decimal('discount_amount_vnd', 15, 2)->nullable()->default(0)->after('discount_amount_usd')->comment('Giảm giá bằng số tiền (VND)');
        });

        // Thêm cột cho sales (giảm giá toàn bộ hóa đơn)
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('discount_amount_usd', 10, 2)->nullable()->default(0)->after('discount_percent')->comment('Giảm giá bằng số tiền cho toàn bộ (USD)');
            $table->decimal('discount_amount_vnd', 15, 2)->nullable()->default(0)->after('discount_amount_usd')->comment('Giảm giá bằng số tiền cho toàn bộ (VND)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['discount_amount_usd', 'discount_amount_vnd']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['discount_amount_usd', 'discount_amount_vnd']);
        });
    }
};
