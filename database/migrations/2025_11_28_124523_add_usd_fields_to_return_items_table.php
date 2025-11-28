<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_items', function (Blueprint $table) {
            // Thêm các trường USD
            $table->decimal('unit_price_usd', 15, 2)->default(0)->after('unit_price')->comment('Đơn giá (USD)');
            $table->decimal('subtotal_usd', 15, 2)->default(0)->after('subtotal')->comment('Thành tiền (USD)');
            $table->string('currency', 3)->default('VND')->after('subtotal_usd')->comment('Loại tiền tệ: USD hoặc VND');
        });
    }

    public function down(): void
    {
        Schema::table('return_items', function (Blueprint $table) {
            $table->dropColumn(['unit_price_usd', 'subtotal_usd', 'currency']);
        });
    }
};
