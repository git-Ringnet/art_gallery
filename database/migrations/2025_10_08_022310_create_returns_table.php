<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_code', 50)->unique()->comment('Mã phiếu trả');
            $table->enum('type', ['return', 'exchange'])->default('return')->comment('Loại: return=trả hàng, exchange=đổi hàng');
            $table->foreignId('sale_id')->constrained('sales')->onDelete('restrict')->comment('ID hóa đơn gốc');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict')->comment('ID khách hàng');
            $table->date('return_date')->comment('Ngày trả/đổi hàng');
            $table->decimal('total_refund', 15, 2)->comment('Tổng tiền hoàn (VND)');
            $table->decimal('exchange_amount', 15, 2)->nullable()->comment('Tiền chênh lệch khi đổi hàng (VND)');
            $table->text('reason')->nullable()->comment('Lý do trả/đổi hàng');
            $table->enum('status', ['pending', 'approved', 'completed', 'cancelled'])->default('pending')->comment('Trạng thái: pending=chờ duyệt, approved=đã duyệt, completed=hoàn thành, cancelled=đã hủy');
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null')->comment('Người xử lý');
            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->timestamps();
            
            $table->index('return_code');
            $table->index('type');
            $table->index('sale_id');
            $table->index('customer_id');
            $table->index('return_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('returns');
    }
};
