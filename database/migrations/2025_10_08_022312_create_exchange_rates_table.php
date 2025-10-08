<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->decimal('rate', 10, 2)->comment('Tỷ giá (1 USD = ? VND)');
            $table->date('effective_date')->comment('Ngày áp dụng');
            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->comment('Người tạo');
            $table->timestamps();
            
            $table->index('effective_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
