<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Showroom;
use Carbon\Carbon;

class FixInvoiceCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:fix-invoice-codes 
                            {--dry-run : Show what would be changed without actually changing}
                            {--showroom= : Only fix invoices for a specific showroom code}
                            {--year= : Only fix invoices for a specific year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chuẩn hóa mã hóa đơn về định dạng đúng: SHOWROOMCODE + STT + DDMMYYYY (dựa trên sale_date)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $filterShowroom = $this->option('showroom');
        $filterYear = $this->option('year');

        $this->info('=== CHUẨN HÓA MÃ HÓA ĐƠN ===');
        $this->info('Định dạng chuẩn: SHOWROOMCODE + STT + DDMMYYYY');
        $this->info('Ví dụ: A0101022026 = A + 01 + 01/02/2026');
        $this->newLine();

        if ($isDryRun) {
            $this->warn('CHẾ ĐỘ DRY-RUN: Không thay đổi thực sự, chỉ hiển thị những gì sẽ được thay đổi.');
            $this->newLine();
        }

        // Build query
        $query = Sale::query()->with('showroom');

        if ($filterShowroom) {
            $showroomIds = Showroom::where('code', 'like', "%{$filterShowroom}%")->pluck('id');
            $query->whereIn('showroom_id', $showroomIds);
        }

        if ($filterYear) {
            $query->whereYear('sale_date', $filterYear);
        }

        // Order by sale_date and id to ensure consistent STT assignment
        $sales = $query->orderBy('sale_date', 'asc')->orderBy('id', 'asc')->get();

        $this->info("Tìm thấy {$sales->count()} hóa đơn cần kiểm tra.");
        $this->newLine();

        $fixed = 0;
        $skipped = 0;
        $errors = 0;

        // Group sales by showroom_id + sale_date to calculate correct STT
        $groupedSales = $sales->groupBy(function ($sale) {
            $showroomCode = $sale->showroom ? strtoupper($sale->showroom->code) : 'XX';
            $date = Carbon::parse($sale->sale_date)->format('dmY');
            return $showroomCode . '_' . $date;
        });

        foreach ($groupedSales as $key => $salesInGroup) {
            $parts = explode('_', $key);
            $showroomCode = $parts[0];
            $datePattern = $parts[1]; // DDMMYYYY

            $counter = 1;
            foreach ($salesInGroup as $sale) {
                // Build correct invoice code: SHOWROOMCODE + STT (2 digits) + DDMMYYYY
                $correctCode = $showroomCode . str_pad($counter, 2, '0', STR_PAD_LEFT) . $datePattern;

                // Check if current code matches correct format
                if ($sale->invoice_code === $correctCode) {
                    $skipped++;
                } else {
                    $oldCode = $sale->invoice_code ?: '(trống)';

                    // Check if new code already exists (conflict with another sale)
                    $existingWithNewCode = Sale::where('invoice_code', $correctCode)
                        ->where('id', '!=', $sale->id)
                        ->first();

                    if ($existingWithNewCode) {
                        $this->error("❌ Conflict: ID {$sale->id} ({$oldCode}) → {$correctCode}");
                        $this->error("   Mã {$correctCode} đã được dùng cho sale ID #{$existingWithNewCode->id}");
                        $errors++;
                    } else {
                        if (!$isDryRun) {
                            try {
                                $sale->invoice_code = $correctCode;
                                $sale->save();
                                $this->line("✅ {$oldCode} → {$correctCode} (ID: {$sale->id}, Ngày: {$sale->sale_date})");
                                $fixed++;
                            } catch (\Exception $e) {
                                $this->error("❌ Lỗi khi cập nhật ID {$sale->id}: " . $e->getMessage());
                                $errors++;
                            }
                        } else {
                            $this->line("🔄 [DRY-RUN] {$oldCode} → {$correctCode} (ID: {$sale->id}, Ngày: {$sale->sale_date})");
                            $fixed++;
                        }
                    }
                }

                $counter++;
            }
        }

        $this->newLine();
        $this->info('=== KẾT QUẢ ===');
        $this->info("Đã cập nhật: {$fixed}");
        $this->info("Bỏ qua (đã đúng): {$skipped}");
        if ($errors > 0) {
            $this->error("Lỗi/Conflict: {$errors}");
        }

        if ($isDryRun && $fixed > 0) {
            $this->newLine();
            $this->warn("Để thực hiện thay đổi thực sự, chạy lại lệnh không có --dry-run:");
            $this->warn("php artisan sales:fix-invoice-codes");
        }

        return Command::SUCCESS;
    }
}
