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
        $currentYear = (int) date('Y');
        $newYear = $currentYear + 1;
        $force = $this->option('force');

        $this->info("╔══════════════════════════════════════════════════════════╗");
        $this->info("║         QUY TRÌNH CHUYỂN GIAO NĂM TỰ ĐỘNG                ║");
        $this->info("║         Năm cũ: {$currentYear} → Năm mới: {$newYear}                       ║");
        $this->info("╚══════════════════════════════════════════════════════════╝");

        if (!$force) {
            $this->warn("\n  CẢNH BÁO: Quy trình này sẽ:");
            $this->line("   1. Export toàn bộ dữ liệu năm {$currentYear} (SQL + ảnh)");
            $this->line("   2. Tạo và kích hoạt năm {$newYear}");
            $this->line("   3. Xóa dữ liệu giao dịch năm {$currentYear} (giữ tồn kho)");
            
            if (!$this->confirm("\nBạn có chắc chắn muốn tiếp tục?")) {
                $this->info('Đã hủy.');
                return 0;
            }
        }

        $startTime = now();
        Log::info("=== BẮT ĐẦU QUY TRÌNH CUỐI NĂM {$currentYear} ===");

        try {
            // BƯỚC 1: Export dữ liệu năm cũ
            $this->newLine();
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info(" BƯỚC 1/3: Export dữ liệu năm {$currentYear}...");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            
            $exitCode = Artisan::call('year:export', [
                'year' => $currentYear,
                '--include-images' => true,
            ]);

            if ($exitCode !== 0) {
                throw new \Exception("Lỗi khi export dữ liệu năm {$currentYear}");
            }
            
            $this->info(" Export thành công!");
            Log::info("Export năm {$currentYear} thành công");

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
            $this->info("  BƯỚC 3/3: Xóa dữ liệu giao dịch năm {$currentYear}...");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            
            $exitCode = Artisan::call('year:cleanup', [
                'year' => $currentYear,
                '--force' => true,
                '--keep-images' => false,
            ]);

            if ($exitCode !== 0) {
                throw new \Exception("Lỗi khi cleanup dữ liệu năm {$currentYear}");
            }
            
            $this->info(" Cleanup thành công!");
            Log::info("Cleanup năm {$currentYear} thành công");

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
            $this->line("   php artisan year:export {$currentYear} --include-images");
            $this->line("   php artisan year:cleanup {$currentYear} --force");
            $this->line("   php artisan year:prepare {$newYear} --force");
            
            return 1;
        }
    }
}
