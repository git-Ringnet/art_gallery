<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\YearDatabase;

class CleanupYearData extends Command
{
    protected $signature = 'year:cleanup {year : Năm cần dọn dẹp} {--force : Bỏ qua xác nhận}';
    protected $description = 'Xóa dữ liệu năm cũ khỏi database chính sau khi đã export';

    public function handle()
    {
        $year = $this->argument('year');
        
        $this->warn("⚠️  CẢNH BÁO: Lệnh này sẽ XÓA dữ liệu năm {$year} khỏi database chính!");
        
        // Kiểm tra năm đã được archive chưa
        $yearDb = YearDatabase::where('year', $year)->where('is_active', false)->first();
        if (!$yearDb) {
            $this->error("❌ Năm {$year} chưa được archive! Chạy 'year:create-archive {$year}' trước.");
            return 1;
        }
        
        // Xác nhận
        if (!$this->option('force')) {
            if (!$this->confirm("Bạn có chắc chắn muốn xóa dữ liệu năm {$year}?")) {
                $this->info("Đã hủy.");
                return 0;
            }
            
            if (!$this->confirm("Bạn đã backup database chưa?")) {
                $this->warn("Vui lòng backup trước khi tiếp tục!");
                return 0;
            }
        }
        
        $this->info("🧹 Bắt đầu dọn dẹp dữ liệu năm {$year}...");
        
        // Xóa dữ liệu theo thứ tự (foreign key constraints)
        $this->info("🗑️  Xóa sale_items...");
        $saleIds = DB::table('sales')->where('year', $year)->pluck('id');
        $deleted = DB::table('sale_items')->whereIn('sale_id', $saleIds)->delete();
        $this->info("  ✓ Đã xóa {$deleted} records");
        
        $this->info("🗑️  Xóa return_items và exchange_items...");
        $returnIds = DB::table('returns')->where('year', $year)->pluck('id');
        $deleted1 = DB::table('return_items')->whereIn('return_id', $returnIds)->delete();
        $deleted2 = DB::table('exchange_items')->whereIn('return_id', $returnIds)->delete();
        $this->info("  ✓ Đã xóa {$deleted1} return_items, {$deleted2} exchange_items");
        
        $this->info("🗑️  Xóa payments...");
        $deleted = DB::table('payments')->where('year', $year)->delete();
        $this->info("  ✓ Đã xóa {$deleted} records");
        
        $this->info("🗑️  Xóa debts...");
        $deleted = DB::table('debts')->where('year', $year)->delete();
        $this->info("  ✓ Đã xóa {$deleted} records");
        
        $this->info("🗑️  Xóa returns...");
        $deleted = DB::table('returns')->where('year', $year)->delete();
        $this->info("  ✓ Đã xóa {$deleted} records");
        
        $this->info("🗑️  Xóa sales...");
        $deleted = DB::table('sales')->where('year', $year)->delete();
        $this->info("  ✓ Đã xóa {$deleted} records");
        
        $this->info("🗑️  Xóa inventory_transactions...");
        $deleted = DB::table('inventory_transactions')->where('year', $year)->delete();
        $this->info("  ✓ Đã xóa {$deleted} records");
        
        $this->info("✅ Hoàn tất dọn dẹp dữ liệu năm {$year}!");
        $this->info("💡 Lưu ý: Dữ liệu vẫn còn trong database {$yearDb->database_name}");
        
        return 0;
    }
}
