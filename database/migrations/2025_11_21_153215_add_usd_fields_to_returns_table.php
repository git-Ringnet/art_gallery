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
        Schema::table('returns', function (Blueprint $table) {
            // Thêm các trường USD và tỷ giá
            $table->decimal('total_refund_usd', 15, 2)->default(0)->after('total_refund')->comment('Tổng tiền hoàn (USD)');
            $table->decimal('exchange_amount_usd', 15, 2)->nullable()->after('exchange_amount')->comment('Tiền chênh lệch khi đổi hàng (USD)');
            $table->decimal('exchange_rate', 15, 2)->default(25000)->after('exchange_amount_usd')->comment('Tỷ giá VND/USD tại thời điểm tạo phiếu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('returns', function (Blueprint $table) {
            $table->dropColumn(['total_refund_usd', 'exchange_amount_usd', 'exchange_rate']);
        });
    }
};
