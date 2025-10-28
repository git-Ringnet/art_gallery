<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_code', 50)->unique()->comment('Mã hóa đơn');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict')->comment('ID khách hàng');
            $table->foreignId('showroom_id')->nullable()->constrained('showrooms')->onDelete('set null')->comment('ID phòng trưng bày');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->comment('ID nhân viên bán hàng');
            $table->date('sale_date')->comment('Ngày bán');
            $table->decimal('exchange_rate', 10, 2)->comment('Tỷ giá USD/VND');
            $table->decimal('subtotal_usd', 10, 2)->default(0)->comment('Tạm tính (USD)');
            $table->decimal('subtotal_vnd', 15, 2)->default(0)->comment('Tạm tính (VND)');
            $table->decimal('discount_percent', 5, 2)->default(0)->comment('Giảm giá (%)');
            $table->decimal('discount_usd', 10, 2)->default(0)->comment('Số tiền giảm (USD)');
            $table->decimal('discount_vnd', 15, 2)->default(0)->comment('Số tiền giảm (VND)');
            $table->decimal('total_usd', 10, 2)->comment('Tổng cộng (USD)');
            $table->decimal('total_vnd', 15, 2)->comment('Tổng cộng (VND)');
            $table->decimal('paid_amount', 15, 2)->default(0)->comment('Số tiền đã thanh toán (VND)');
            $table->decimal('debt_amount', 15, 2)->default(0)->comment('Số tiền còn nợ (VND)');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'cancelled'])->default('unpaid')->comment('Trạng thái thanh toán');
            $table->enum('sale_status', ['pending', 'completed', 'cancelled'])->default('pending')->comment('Trạng thái phiếu: pending=Chờ duyệt, completed=Đã hoàn thành, cancelled=Đã hủy');
            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->timestamps();
            
            $table->index('invoice_code');
            $table->index('customer_id');
            $table->index('showroom_id');
            $table->index('sale_date');
            $table->index('payment_status');
            $table->index('sale_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
