<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class EndOfYearProcess extends Command
{
    protected $signature = 'year:end-of-year {old_year} {new_year} {--skip-cleanup : Bỏ qua bước cleanup}';
    protected $description = 'Chạy toàn bộ quy trình cuối năm (archive, backup, cleanup, prepare)';

    public function handle()
    {
        $oldYear = $this->argument('old_year');
        $newYear = $this->argument('new_year');
        $skipCleanup = $this->option('skip-cleanup');
        
        $this->info("╔════════════════════════════════════════════════════════╗");
        $this->info("║     QUY TRÌNH CUỐI NĂM - CHUYỂN SANG NĂM MỚI          ║");
        $this->info("╚════════════════════════════════════════════════════════╝");
        $this->info("");
        $this->info("Năm cũ: {$oldYear}");
        $this->info("Năm mới: {$newYear}");
        $this->info("");
        
        // Xác nhận
        if (!$this->confirm("Bạn có chắc chắn muốn bắt đầu quy trình cuối năm?")) {
            $this->info("Đã hủy.");
            return 0;
        }
        
        // Bước 1: Backup năm cũ
        $this->info("");
        $this->info("═══════════════════════════════════════════════════════");
        $this->info("BƯỚC 1/5: BACKUP DATABASE NĂM {$oldYear}");
        $this->info("═══════════════════════════════════════════════════════");
        
        $exitCode = Artisan::call('year:backup', ['year' => $oldYear]);
        $this->line(Artisan::output());
        
        if ($exitCode !== 0) {
            $this->error("❌ Lỗi khi backup! Dừng quy trình.");
            return 1;
        }
        
        if (!$this->confirm("Backup thành công. Tiếp tục?")) {
            return 0;
        }
        
        // Bước 2: Archive năm cũ
        $this->info("");
        $this->info("═══════════════════════════════════════════════════════");
        $this->info("BƯỚC 2/5: ARCHIVE DỮ LIỆU NĂM {$oldYear}");
        $this->info("═══════════════════════════════════════════════════════");
        
        $exitCode = Artisan::call('year:create-archive', ['year' => $oldYear]);
        $this->line(Artisan::output());
        
        if ($exitCode !== 0) {
            $this->error("❌ Lỗi khi archive! Dừng quy trình.");
            return 1;
        }
        
        if (!$this->confirm("Archive thành công. Tiếp tục?")) {
            return 0;
        }
        
        // Bước 3: Backup database mới tạo
        $this->info("");
        $this->info("═══════════════════════════════════════════════════════");
        $this->info("BƯỚC 3/5: BACKUP DATABASE MỚI TẠO");
        $this->info("═══════════════════════════════════════════════════════");
        
        $exitCode = Artisan::call('year:backup', ['year' => $oldYear]);
        $this->line(Artisan::output());
        
        if ($exitCode !== 0) {
            $this->warn("⚠️  Cảnh báo: Không backup được database mới tạo");
        }
        
        // Bước 4: Cleanup (tùy chọn)
        if (!$skipCleanup) {
            $this->info("");
            $this->info("═══════════════════════════════════════════════════════");
            $this->info("BƯỚC 4/5: DỌN DẸP DATABASE CHÍNH");
            $this->info("═══════════════════════════════════════════════════════");
            
            $this->warn("⚠️  CẢNH BÁO: Bước này sẽ XÓA dữ liệu năm {$oldYear} khỏi database chính!");
            
            if ($this->confirm("Bạn có chắc chắn muốn cleanup?")) {
                $exitCode = Artisan::call('year:cleanup', [
                    'year' => $oldYear,
                    '--force' => true
                ]);
                $this->line(Artisan::output());
                
                if ($exitCode !== 0) {
                    $this->error("❌ Lỗi khi cleanup!");
                    $this->warn("Dữ liệu vẫn còn trong database chính và database archive");
                }
            } else {
                $this->info("Bỏ qua cleanup. Bạn có thể chạy sau: php artisan year:cleanup {$oldYear}");
            }
        } else {
            $this->info("");
            $this->info("═══════════════════════════════════════════════════════");
            $this->info("BƯỚC 4/5: BỎ QUA CLEANUP (--skip-cleanup)");
            $this->info("═══════════════════════════════════════════════════════");
        }
        
        // Bước 5: Chuẩn bị năm mới
        $this->info("");
        $this->info("═══════════════════════════════════════════════════════");
        $this->info("BƯỚC 5/5: CHUẨN BỊ NĂM MỚI {$newYear}");
        $this->info("═══════════════════════════════════════════════════════");
        
        $exitCode = Artisan::call('year:prepare', ['year' => $newYear]);
        $this->line(Artisan::output());
        
        if ($exitCode !== 0) {
            $this->error("❌ Lỗi khi chuẩn bị năm mới!");
            return 1;
        }
        
        // Tổng kết
        $this->info("");
        $this->info("╔════════════════════════════════════════════════════════╗");
        $this->info("║              HOÀN TẤT QUY TRÌNH CUỐI NĂM              ║");
        $this->info("╚════════════════════════════════════════════════════════╝");
        $this->info("");
        $this->info("✅ Đã hoàn tất các bước:");
        $this->info("   1. ✓ Backup năm {$oldYear}");
        $this->info("   2. ✓ Archive dữ liệu năm {$oldYear}");
        $this->info("   3. ✓ Backup database archive");
        
        if (!$skipCleanup) {
            $this->info("   4. ✓ Cleanup database chính");
        } else {
            $this->warn("   4. ⊘ Bỏ qua cleanup (chạy thủ công nếu cần)");
        }
        
        $this->info("   5. ✓ Chuẩn bị năm {$newYear}");
        $this->info("");
        $this->info("📁 File backup: storage/backups/databases/");
        $this->info("📊 Database archive: art_gallery_{$oldYear}");
        $this->info("");
        $this->info("💡 Gợi ý tiếp theo:");
        $this->info("   • Kiểm tra tồn kho đầu kỳ năm {$newYear}");
        $this->info("   • Nén file backup: gzip storage/backups/databases/*.sql");
        $this->info("   • Chuyển backup ra NAS/External storage");
        
        if (!$skipCleanup) {
            $this->info("   • Có thể đánh dấu offline: php artisan year:mark-offline {$oldYear}");
        } else {
            $this->warn("   • Nhớ cleanup sau: php artisan year:cleanup {$oldYear}");
        }
        
        $this->info("");
        $this->info("🎉 Chúc mừng năm mới {$newYear}!");
        
        return 0;
    }
}
