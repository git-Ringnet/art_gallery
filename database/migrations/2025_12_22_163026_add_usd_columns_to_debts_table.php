<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->decimal('total_usd', 10, 2)->default(0)->after('customer_id')->comment('Tổng tiền hóa đơn (USD)');
            $table->decimal('paid_usd', 10, 2)->default(0)->after('total_usd')->comment('Số tiền đã trả (USD)');
            $table->decimal('debt_usd', 10, 2)->default(0)->after('paid_usd')->comment('Số tiền còn nợ (USD)');
            $table->decimal('exchange_rate', 10, 2)->default(0)->after('debt_usd')->comment('Tỷ giá tại thời điểm tạo');
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->dropColumn(['total_usd', 'paid_usd', 'debt_usd', 'exchange_rate']);
        });
    }
};
