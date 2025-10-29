<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Thêm cột year vào bảng sales
        Schema::table('sales', function (Blueprint $table) {
            $table->integer('year')->default(2025)->after('id')->comment('Năm của giao dịch');
            $table->index('year');
        });

        // Thêm cột year vào bảng debts
        Schema::table('debts', function (Blueprint $table) {
            $table->integer('year')->default(2025)->after('id')->comment('Năm của công nợ');
            $table->index('year');
        });

        // Thêm cột year vào bảng returns
        Schema::table('returns', function (Blueprint $table) {
            $table->integer('year')->default(2025)->after('id')->comment('Năm của phiếu đổi/trả');
            $table->index('year');
        });

        // Thêm cột year vào bảng payments
        Schema::table('payments', function (Blueprint $table) {
            $table->integer('year')->default(2025)->after('id')->comment('Năm của thanh toán');
            $table->index('year');
        });

        // Thêm cột year vào bảng inventory_transactions
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->integer('year')->default(2025)->after('id')->comment('Năm của giao dịch kho');
            $table->index('year');
        });

        // Update year dựa trên created_at cho dữ liệu hiện có
        DB::statement('UPDATE sales SET year = YEAR(created_at)');
        DB::statement('UPDATE debts SET year = YEAR(created_at)');
        DB::statement('UPDATE returns SET year = YEAR(created_at)');
        DB::statement('UPDATE payments SET year = YEAR(created_at)');
        DB::statement('UPDATE inventory_transactions SET year = YEAR(created_at)');
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['year']);
            $table->dropColumn('year');
        });

        Schema::table('debts', function (Blueprint $table) {
            $table->dropIndex(['year']);
            $table->dropColumn('year');
        });

        Schema::table('returns', function (Blueprint $table) {
            $table->dropIndex(['year']);
            $table->dropColumn('year');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['year']);
            $table->dropColumn('year');
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropIndex(['year']);
            $table->dropColumn('year');
        });
    }
};
