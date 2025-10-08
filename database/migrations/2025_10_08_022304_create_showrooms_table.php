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
        Schema::create('showrooms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Mã phòng');
            $table->string('name')->comment('Tên phòng trưng bày');
            $table->string('phone', 20)->nullable()->comment('Số điện thoại');
            $table->text('address')->nullable()->comment('Địa chỉ');
            $table->string('bank_name', 100)->nullable()->comment('Tên ngân hàng');
            $table->string('bank_account', 50)->nullable()->comment('Số tài khoản');
            $table->string('bank_holder')->nullable()->comment('Chủ tài khoản');
            $table->string('logo')->nullable()->comment('Logo phòng trưng bày');
            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động');
            $table->timestamps();
            
            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('showrooms');
    }
};
