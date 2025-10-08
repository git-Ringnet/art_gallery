<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade')->comment('ID hóa đơn');
            $table->decimal('amount', 15, 2)->comment('Số tiền thanh toán (VND)');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'card', 'other'])->default('cash')->comment('Phương thức thanh toán');
            $table->date('payment_date')->comment('Ngày thanh toán');
            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->comment('Người tạo');
            $table->timestamps();
            
            $table->index('sale_id');
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
