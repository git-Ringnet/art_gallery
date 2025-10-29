<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cập nhật transaction_type cho các payment hiện có
        // Dựa vào notes để phân loại
        
        // 1. Các payment từ trả hàng (có notes chứa "Hoàn tiền" hoặc "phiếu trả")
        DB::table('payments')
            ->where('notes', 'like', '%Hoàn tiền%')
            ->orWhere('notes', 'like', '%phiếu trả%')
            ->update(['transaction_type' => 'return']);
        
        // 2. Các payment từ đổi hàng (có notes chứa "Chênh lệch đổi hàng")
        DB::table('payments')
            ->where('notes', 'like', '%Chênh lệch đổi hàng%')
            ->orWhere('notes', 'like', '%đổi hàng%')
            ->update(['transaction_type' => 'exchange']);
        
        // 3. Các payment còn lại là thanh toán bán hàng (đã có default value)
        // Không cần update vì đã có default 'sale_payment'
    }

    public function down(): void
    {
        // Không cần rollback vì chỉ update data
    }
};
