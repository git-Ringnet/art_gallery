<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\YearDatabase;

class PrepareNewYear extends Command
{
    protected $signature = 'year:prepare {year : Năm mới cần chuẩn bị}';
    protected $description = 'Chuẩn bị database cho năm mới';

    public function handle()
    {
        $newYear = $this->argument('year');
        $oldYear = $newYear - 1;
        
        $this->info("🎊 Chuẩn bị database cho năm {$newYear}...");
        
        // Bước 1: Kiểm tra năm cũ đã được cleanup chưa
        $oldYearDb = YearDatabase::where('year', $oldYear)->where('is_active', false)->first();
        if (!$oldYearDb) {
            $this->warn("⚠️  Năm {$oldYear} chưa được archive. Khuyến nghị chạy:");
            $this->warn("  1. php artisan year:create-archive {$oldYear}");
            $this->warn("  2. php artisan year:cleanup {$oldYear}");
            
            if (!$this->confirm("Bạn có muốn tiếp tục không?")) {
                return 0;
            }
        }
        
        // Bước 2: Cập nhật year_databases
        $this->info("📝 Cập nhật year_databases...");
        
        // Set năm cũ thành inactive
        YearDatabase::where('is_active', true)->update(['is_active' => false]);
        
        // Tạo hoặc update năm mới
        YearDatabase::updateOrCreate(
            ['year' => $newYear],
            [
                'database_name' => env('DB_DATABASE', 'art_gallery'),
                'is_active' => true,
                'is_on_server' => true,
                'description' => "Database năm {$newYear} - Năm hiện tại",
            ]
        );
        
        $this->info("  ✓ Đã set năm {$newYear} là năm hiện tại");
        
        // Bước 3: Kiểm tra tồn kho
        $paintingsCount = DB::table('paintings')->count();
        $suppliesCount = DB::table('supplies')->count();
        
        $this->info("📦 Tồn kho đầu kỳ năm {$newYear}:");
        $this->info("  • Tranh: {$paintingsCount}");
        $this->info("  • Vật tư: {$suppliesCount}");
        
        // Bước 4: Reset các bảng thống kê nếu cần
        $this->info("🔄 Kiểm tra dữ liệu...");
        
        $salesThisYear = DB::table('sales')->where('year', $newYear)->count();
        $debtsThisYear = DB::table('debts')->where('year', $newYear)->count();
        
        $this->info("  • Sales năm {$newYear}: {$salesThisYear}");
        $this->info("  • Debts năm {$newYear}: {$debtsThisYear}");
        
        if ($salesThisYear == 0 && $debtsThisYear == 0) {
            $this->info("  ✓ Database sạch, sẵn sàng cho năm mới!");
        }
        
        // Bước 5: Tạo backup trước khi bắt đầu năm mới
        $this->info("💾 Khuyến nghị backup database trước khi bắt đầu năm mới:");
        $this->info("  php artisan year:backup");
        
        $this->info("✅ Hoàn tất chuẩn bị cho năm {$newYear}!");
        $this->info("🎉 Chúc mừng năm mới!");
        
        return 0;
    }
}
