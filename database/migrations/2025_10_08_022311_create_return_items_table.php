<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('returns')->onDelete('cascade')->comment('ID phiếu trả');
            $table->foreignId('sale_item_id')->constrained('sale_items')->onDelete('restrict')->comment('ID sản phẩm trong hóa đơn');
            $table->string('item_type', 20)->comment('Loại: painting/supply');
            $table->foreignId('item_id')->comment('ID sản phẩm');
            $table->integer('quantity')->comment('Số lượng trả');
            $table->foreignId('supply_id')->nullable()->constrained('supplies')->onDelete('set null')->comment('ID vật tư (nếu có)');
            $table->decimal('supply_length', 8, 2)->default(0)->comment('Số mét vật tư (nếu có)');
            $table->decimal('unit_price', 15, 2)->comment('Đơn giá (VND)');
            $table->decimal('subtotal', 15, 2)->comment('Thành tiền (VND)');
            $table->text('reason')->nullable()->comment('Lý do trả');
            $table->timestamps();
            
            $table->index('return_id');
            $table->index('sale_item_id');
            $table->index(['item_type', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_items');
    }
};
