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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Tên khách hàng');
            $table->string('phone', 20)->nullable()->comment('Số điện thoại');
            $table->string('email')->nullable()->comment('Email');
            $table->text('address')->nullable()->comment('Địa chỉ');
            $table->decimal('total_purchased', 15, 2)->default(0)->comment('Tổng giá trị đã mua (VND)');
            $table->decimal('total_debt', 15, 2)->default(0)->comment('Tổng công nợ hiện tại (VND)');
            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->timestamps();
            
            $table->index('phone');
            $table->index('name');
            $table->index('total_debt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
