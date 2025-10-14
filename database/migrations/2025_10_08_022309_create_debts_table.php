<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade')->comment('ID hóa đơn');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade')->comment('ID khách hàng');
            $table->decimal('total_amount', 15, 2)->comment('Tổng tiền hóa đơn (VND)');
            $table->decimal('paid_amount', 15, 2)->default(0)->comment('Số tiền đã trả (VND)');
            $table->decimal('debt_amount', 15, 2)->comment('Số tiền còn nợ (VND)');
            $table->date('due_date')->nullable()->comment('Ngày đến hạn thanh toán');
            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid')->comment('Trạng thái');
            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->timestamps();
            
            $table->index('sale_id');
            $table->index('customer_id');
            $table->index('status');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
/*  */