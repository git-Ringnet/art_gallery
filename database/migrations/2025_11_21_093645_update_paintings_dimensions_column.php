<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paintings', function (Blueprint $table) {
            // Tăng kích thước cột width và height để chứa giá trị lớn hơn
            // decimal(10, 2) cho phép giá trị tối đa: 99,999,999.99 cm
            $table->decimal('width', 10, 2)->nullable()->change();
            $table->decimal('height', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('paintings', function (Blueprint $table) {
            // Khôi phục về kích thước cũ
            $table->decimal('width', 8, 2)->nullable()->change();
            $table->decimal('height', 8, 2)->nullable()->change();
        });
    }
};
