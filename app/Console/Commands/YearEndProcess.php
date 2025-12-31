<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class YearEndProcess extends Command
{
    protected $signature = 'year:end-process {--force : Bỏ qua xác nhận}';
    protected $description = 'Quy trình cuối năm tự động: Export → Cleanup → Prepare năm mới';

    public function handle()
    {
        // Lệnh này chạy vào 00:05 ngày 1/1 năm mới
        // Nên năm hiện tại (date('Y')) đã là năm mới rồi
        // Ta cần export/cleanup năm CŨ (năm trước)
        $newYear = (int) date('Y');           // Năm mới (năm hiện tại khi lệnh chạy)
        $oldYear = $newYear - 1;              // Năm cũ cần export/cleanup
        $force = $this->option('force');

        $this->info("╔══════════════════════════════════════════════════════════╗");
        $this->info("║         QUY TRÌNH CHUYỂN GIAO NĂM TỰ ĐỘNG                ║");
        $this->info("║         Năm cũ: {$oldYear} → Năm mới: {$newYear}                       ║");
        $this->info("╚══════════════════════════════════════════════════════════╝");

        if (!$force) {
            $this->warn("\n  CẢNH BÁO: Quy trình này sẽ:");
            $this->line("   1. Export toàn bộ dữ liệu năm {$oldYear} (SQL + ảnh)");
            $this->line("   2. Tạo và kích hoạt năm {$newYear}");
            $this->line("   3. Xóa dữ liệu giao dịch năm {$oldYear} (giữ tồn kho)");
            
            if (!$this->confirm("\nBạn có chắc chắn muốn tiếp tục?")) {
                $this->info('Đã hủy.');
                return 0;
            }
        }

        $startTime = now();
        Log::info("=== BẮT ĐẦU QUY TRÌNH CUỐI NĂM {$oldYear} ===");

        try {
            // BƯỚC 1: Export dữ liệu năm cũ
            $this->newLine();
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info(" BƯỚC 1/3: Export dữ liệu năm {$oldYear}...");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            
            $exitCode = Artisan::call('year:export', [
                'year' => $oldYear,
                '--include-images' => true,
            ]);

            if ($exitCode !== 0) {
                throw new \Exception("Lỗi khi export dữ liệu năm {$oldYear}");
            }
            
            $this->info(" Export thành công!");
            Log::info("Export năm {$oldYear} thành công");

            // BƯỚC 2: Chuẩn bị năm mới TRƯỚC (để năm cũ không còn là current)
            $this->newLine();
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info(" BƯỚC 2/3: Chuẩn bị năm {$newYear}...");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            
            $exitCode = Artisan::call('year:prepare', [
                'year' => $newYear,
                '--force' => true,
            ]);

            if ($exitCode !== 0) {
                throw new \Exception("Lỗi khi chuẩn bị năm {$newYear}");
            }
            
            $this->info(" Chuẩn bị năm mới thành công!");
            Log::info("Chuẩn bị năm {$newYear} thành công");

            // BƯỚC 3: Cleanup dữ liệu năm cũ (bây giờ năm cũ không còn là current nữa)
            $this->newLine();
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("  BƯỚC 3/3: Xóa dữ liệu giao dịch năm {$oldYear}...");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            
            $exitCode = Artisan::call('year:cleanup', [
                'year' => $oldYear,
                '--force' => true,
                '--keep-images' => false,
            ]);

            if ($exitCode !== 0) {
                throw new \Exception("Lỗi khi cleanup dữ liệu năm {$oldYear}");
            }
            
            $this->info(" Cleanup thành công!");
            Log::info("Cleanup năm {$oldYear} thành công");

            // HOÀN THÀNH
            $duration = now()->diffInSeconds($startTime);
            
            $this->newLine();
            $this->info("╔══════════════════════════════════════════════════════════╗");
            $this->info("║               HOÀN THÀNH QUY TRÌNH!                    ║");
            $this->info("╚══════════════════════════════════════════════════════════╝");
            $this->info("  Thời gian: {$duration} giây");
            $this->info(" Năm hiện tại: {$newYear}");
            $this->info(" File backup: storage/backups/databases/");
            
            Log::info("=== HOÀN THÀNH QUY TRÌNH CUỐI NĂM - Thời gian: {$duration}s ===");

            return 0;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error(" LỖI: " . $e->getMessage());
            Log::error("Lỗi quy trình cuối năm: " . $e->getMessage());
            
            $this->warn("\n  Quy trình bị gián đoạn. Vui lòng kiểm tra và chạy lại từng bước:");
            $this->line("   php artisan year:export {$oldYear} --include-images");
            $this->line("   php artisan year:prepare {$newYear} --force");
            $this->line("   php artisan year:cleanup {$oldYear} --force");
            
            return 1;
        }
    }
}
