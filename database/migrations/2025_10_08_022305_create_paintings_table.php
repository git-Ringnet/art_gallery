<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paintings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Mã tranh');
            $table->string('name')->comment('Tên tranh / Tác tranh');
            $table->string('artist')->comment('Họa sĩ');
            $table->string('material', 100)->comment('Chất liệu');
            $table->decimal('width', 8, 2)->nullable()->comment('Chiều rộng (cm)');
            $table->decimal('height', 8, 2)->nullable()->comment('Chiều cao (cm)');
            $table->string('paint_year', 20)->nullable()->comment('Năm sản xuất');
            $table->decimal('price_usd', 10, 2)->nullable()->comment('Giá bán (USD)');
            $table->decimal('price_vnd', 15, 2)->nullable()->comment('Giá bán (VND)');
            $table->string('image')->nullable()->comment('Ảnh tranh');
            $table->integer('quantity')->default(1)->comment('Số lượng tồn kho');
            $table->date('import_date')->nullable()->comment('Ngày nhập kho');
            $table->date('export_date')->nullable()->comment('Ngày xuất kho');
            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->enum('status', ['in_stock', 'sold', 'reserved'])->default('in_stock')->comment('Trạng thái');
            $table->timestamps();
            
            $table->index('code');
            $table->index('status');
            $table->index('artist');
            $table->index('material');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paintings');
    }
};
