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
        Schema::table('showrooms', function (Blueprint $table) {
            $table->text('address_en')->nullable()->after('address')->comment('Địa chỉ tiếng Anh');
            $table->string('phone_en', 20)->nullable()->after('phone')->comment('Số điện thoại tiếng Anh');
            $table->string('bank_holder_en')->nullable()->after('bank_holder')->comment('Chủ tài khoản tiếng Anh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('showrooms', function (Blueprint $table) {
            $table->dropColumn(['address_en', 'phone_en', 'bank_holder_en']);
        });
    }
};
