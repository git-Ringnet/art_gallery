<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\YearDatabase;

class MarkYearOffline extends Command
{
    protected $signature = 'year:mark-offline {year : Năm cần đánh dấu offline} {--location= : Vị trí lưu trữ}';
    protected $description = 'Đánh dấu database năm cũ là offline sau khi đã chuyển ra khỏi server';

    public function handle()
    {
        $year = $this->argument('year');
        $location = $this->option('location') ?? 'NAS/External Storage';
        
        $yearDb = YearDatabase::where('year', $year)->first();
        
        if (!$yearDb) {
            $this->error("❌ Không tìm thấy thông tin năm {$year}");
            return 1;
        }
        
        if (!$yearDb->is_on_server) {
            $this->warn("⚠️  Năm {$year} đã được đánh dấu offline trước đó");
            return 0;
        }
        
        $this->warn("⚠️  CẢNH BÁO: Lệnh này sẽ đánh dấu database năm {$year} là OFFLINE");
        $this->warn("   Sau đó bạn sẽ không thể xem dữ liệu năm này cho đến khi import lại");
        
        if (!$this->confirm("Bạn đã backup và chuyển database ra khỏi server chưa?")) {
            $this->info("Vui lòng backup trước:");
            $this->info("  php artisan year:backup {$year}");
            return 0;
        }
        
        if (!$this->confirm("Bạn có chắc chắn muốn đánh dấu offline?")) {
            return 0;
        }
        
        // Cập nhật trạng thái
        $yearDb->update([
            'is_on_server' => false,
            'backup_location' => $location,
        ]);
        
        $this->info("✅ Đã đánh dấu năm {$year} là offline");
        $this->info("📁 Vị trí lưu trữ: {$location}");
        
        // Gợi ý xóa database khỏi server
        $this->info("");
        $this->info("💡 Bây giờ bạn có thể xóa database khỏi server để tiết kiệm dung lượng:");
        $this->info("  DROP DATABASE `{$yearDb->database_name}`;");
        
        return 0;
    }
}
