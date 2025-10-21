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
        Schema::create('exchange_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('returns')->onDelete('cascade')->comment('ID phiếu đổi/trả');
            $table->string('item_type', 20)->comment('Loại: painting/supply');
            $table->foreignId('item_id')->comment('ID sản phẩm mới');
            $table->integer('quantity')->comment('Số lượng đổi');
            $table->decimal('unit_price', 15, 2)->comment('Đơn giá (VND)');
            $table->decimal('subtotal', 15, 2)->comment('Thành tiền (VND)');
            $table->timestamps();
            
            $table->index('return_id');
            $table->index(['item_type', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_items');
    }
};
