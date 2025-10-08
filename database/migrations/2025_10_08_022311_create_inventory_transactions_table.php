<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('transaction_type', ['import', 'export', 'return', 'adjustment'])->comment('Loại giao dịch');
            $table->enum('item_type', ['painting', 'supply'])->comment('Loại sản phẩm');
            $table->unsignedBigInteger('item_id')->comment('ID sản phẩm');
            $table->decimal('quantity', 10, 2)->comment('Số lượng');
            $table->string('reference_type', 50)->nullable()->comment('Loại tham chiếu');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('ID tham chiếu');
            $table->date('transaction_date')->comment('Ngày giao dịch');
            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->comment('Người thực hiện');
            $table->timestamps();
            
            $table->index(['item_type', 'item_id']);
            $table->index('transaction_type');
            $table->index('transaction_date');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
