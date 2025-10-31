<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade')->comment('ID hóa đơn');
            $table->foreignId('painting_id')->nullable()->constrained('paintings')->onDelete('set null')->comment('ID tranh');
            $table->text('description')->nullable()->comment('Mô tả sản phẩm');
            $table->integer('quantity')->default(1)->comment('Số lượng');
            $table->foreignId('supply_id')->nullable()->constrained('supplies')->onDelete('set null')->comment('ID vật tư khung');
            $table->decimal('supply_length', 8, 2)->nullable()->default(0)->comment('Số mét khung sử dụng');
            $table->enum('currency', ['USD', 'VND'])->default('USD')->comment('Loại tiền');
            $table->decimal('price_usd', 10, 2)->nullable()->comment('Giá bán (USD)');
            $table->decimal('price_vnd', 15, 2)->nullable()->comment('Giá bán (VND)');
            $table->decimal('discount_percent', 5, 2)->default(0)->comment('Giảm giá (%)');
            $table->decimal('total_usd', 10, 2)->nullable()->comment('Thành tiền (USD)');
            $table->decimal('total_vnd', 15, 2)->nullable()->comment('Thành tiền (VND)');
            $table->timestamps();
            
            $table->index('sale_id');
            $table->index('painting_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
