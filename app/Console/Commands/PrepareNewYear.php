<?php

namespace App\Console\Commands;

use App\Models\Painting;
use App\Models\Supply;
use App\Models\YearDatabase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PrepareNewYear extends Command
{
    protected $signature = 'year:prepare {year} {--force : Bỏ qua xác nhận}';
    protected $description = 'Chuẩn bị database cho năm mới (tạo record năm mới, set active)';

    public function handle()
    {
        $year = (int) $this->argument('year');
        $force = $this->option('force');

        $this->info("🎉 Chuẩn bị database cho năm {$year}...");

        // Kiểm tra năm đã tồn tại chưa
        $existingYear = YearDatabase::where('year', $year)->first();
        if ($existingYear) {
            $this->warn("Năm {$year} đã tồn tại trong hệ thống.");
            
            if (!$existingYear->is_active) {
                if ($force || $this->confirm("Bạn có muốn set năm {$year} thành năm hiện tại?")) {
                    $this->setActiveYear($year);
                    $this->info("✅ Đã set năm {$year} thành năm hiện tại.");
                }
            }
            return 0;
        }

        // Thống kê tồn kho hiện tại
        $this->info("\n📊 Tồn kho hiện tại (sẽ là tồn đầu kỳ năm {$year}):");
        $this->showInventoryStats();

        // Xác nhận
        if (!$force) {
            if (!$this->confirm("\nBạn có muốn tạo năm {$year} và set làm năm hiện tại?")) {
                $this->info('Đã hủy.');
                return 0;
            }
        }

        DB::beginTransaction();
        try {
            // 1. Tạo record năm mới
            $this->info('Đang tạo record năm mới...');
            YearDatabase::create([
                'year' => $year,
                'database_name' => config('database.connections.mysql.database'),
                'is_active' => false,
                'is_on_server' => true,
                'description' => "Database năm {$year}",
            ]);

            // 2. Set năm mới thành active
            $this->setActiveYear($year);

            DB::commit();

            $this->info("\n✅ Chuẩn bị năm {$year} thành công!");
            $this->info("📌 Năm {$year} đã được set làm năm hiện tại.");
            $this->info("📦 Tồn kho hiện tại sẽ là tồn đầu kỳ của năm {$year}.");

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Lỗi: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Set năm active
     */
    protected function setActiveYear($year)
    {
        // Bỏ active tất cả năm khác
        YearDatabase::where('is_active', true)->update(['is_active' => false]);
        
        // Set năm mới thành active
        YearDatabase::where('year', $year)->update(['is_active' => true]);
    }

    /**
     * Hiển thị thống kê tồn kho
     */
    protected function showInventoryStats()
    {
        $paintingsCount = Painting::where('quantity', '>', 0)->count();
        $paintingsTotal = Painting::where('quantity', '>', 0)->sum('quantity');
        
        $suppliesCount = Supply::where('quantity', '>', 0)->count();
        
        $this->table(
            ['Loại', 'Số mặt hàng', 'Tổng số lượng'],
            [
                ['Tranh', $paintingsCount, $paintingsTotal],
                ['Vật tư', $suppliesCount, '-'],
            ]
        );
    }
}
