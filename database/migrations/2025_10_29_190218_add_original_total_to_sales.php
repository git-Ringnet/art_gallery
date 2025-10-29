<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('original_total_vnd', 15, 2)->nullable()->after('total_vnd');
            $table->decimal('original_total_usd', 15, 2)->nullable()->after('total_usd');
        });
        
        // Copy giá trị hiện tại vào original (cho dữ liệu cũ)
        DB::statement('UPDATE sales SET original_total_vnd = total_vnd, original_total_usd = total_usd WHERE original_total_vnd IS NULL');
        
        // Khôi phục total cho các sale đã bị set = 0
        $cancelledSales = DB::table('sales')
            ->where('sale_status', 'cancelled')
            ->where('total_vnd', 0)
            ->get();
        
        foreach ($cancelledSales as $sale) {
            // Tính lại total từ sale_items
            $itemsTotal = DB::table('sale_items')
                ->where('sale_id', $sale->id)
                ->sum('total_vnd');
            
            if ($itemsTotal > 0) {
                $discountPercent = $sale->discount_percent ?? 0;
                $correctTotal = $itemsTotal * (1 - $discountPercent / 100);
                
                DB::table('sales')
                    ->where('id', $sale->id)
                    ->update([
                        'total_vnd' => $correctTotal,
                        'total_usd' => $correctTotal / $sale->exchange_rate,
                        'original_total_vnd' => $correctTotal,
                        'original_total_usd' => $correctTotal / $sale->exchange_rate,
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['original_total_vnd', 'original_total_usd']);
        });
    }
};
