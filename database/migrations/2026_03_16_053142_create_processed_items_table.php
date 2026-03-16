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
        Schema::create('processed_items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Mã hàng gia công');
            $table->string('name', 255)->comment('Tên hàng gia công');
            $table->decimal('quantity', 10, 2)->default(0)->comment('Số lượng (có thể âm)');
            $table->string('unit', 50)->default('cái')->comment('Đơn vị tính');
            $table->decimal('price_vnd', 15, 2)->default(0)->comment('Giá nhập (VND)');
            $table->decimal('price_usd', 15, 2)->default(0)->comment('Giá nhập (USD)');
            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processed_items');
    }
};
