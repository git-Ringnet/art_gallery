<?php

namespace App\Console\Commands;

use App\Models\Debt;
use App\Models\InventoryTransaction;
use App\Models\Painting;
use App\Models\Payment;
use App\Models\ReturnModel;
use App\Models\Sale;
use App\Models\Supply;
use App\Models\YearDatabase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CleanupYearData extends Command
{
    protected $signature = 'year:cleanup {year} {--force : Bỏ qua xác nhận} {--keep-images : Giữ lại ảnh}';
    protected $description = 'Xóa dữ liệu giao dịch của năm cũ, giữ lại tồn đầu kỳ';

    protected $deletedImages = 0;
    protected $deletedRecords = [];

    public function handle()
    {
        $year = $this->argument('year');
        $force = $this->option('force');
        $keepImages = $this->option('keep-images');

        // Kiểm tra không được xóa năm hiện tại
        $currentYear = YearDatabase::getCurrentYear();
        if ($currentYear && $currentYear->year == $year) {
            $this->error("Không thể xóa dữ liệu năm hiện tại ({$year})!");
            return 1;
        }

        // Thống kê dữ liệu sẽ bị xóa
        $this->info("📊 Thống kê dữ liệu năm {$year}:");
        $stats = $this->getYearStats($year);
        
        $this->table(
            ['Loại dữ liệu', 'Số lượng'],
            collect($stats)->map(fn($count, $type) => [$type, number_format($count)])->toArray()
        );

        // Thống kê ảnh sẽ bị xóa
        if (!$keepImages) {
            $imageStats = $this->getImageStats($year);
            $this->info("\n🖼️ Ảnh sẽ bị xóa:");
            $this->table(
                ['Loại', 'Số lượng', 'Dung lượng'],
                [
                    ['Paintings', $imageStats['paintings_count'], $this->formatBytes($imageStats['paintings_size'])],
                    ['Supplies', $imageStats['supplies_count'], $this->formatBytes($imageStats['supplies_size'])],
                ]
            );
        }

        // Xác nhận
        if (!$force) {
            $this->warn("\n⚠️ CẢNH BÁO: Thao tác này không thể hoàn tác!");
            $this->warn("Hãy chắc chắn đã export backup trước khi tiếp tục.");
            
            if (!$this->confirm("Bạn có chắc chắn muốn xóa dữ liệu năm {$year}?")) {
                $this->info('Đã hủy.');
                return 0;
            }
        }

        $this->info("\n🗑️ Bắt đầu xóa dữ liệu năm {$year}...");

        DB::beginTransaction();
        try {
            // 1. Xóa ảnh của sản phẩm đã bán hết (nếu không giữ lại)
            if (!$keepImages) {
                $this->info('Đang xóa ảnh...');
                $this->deleteImages($year);
            }

            // 2. Xóa dữ liệu giao dịch
            $this->info('Đang xóa dữ liệu giao dịch...');
            $this->deleteTransactionData($year);

            // 3. Xóa sản phẩm đã bán hết (quantity = 0)
            $this->info('Đang xóa sản phẩm đã bán hết...');
            $this->deleteSoldOutProducts($year);

            DB::commit();

            $this->info("\n✅ Xóa dữ liệu năm {$year} thành công!");
            $this->info("📊 Kết quả:");
            foreach ($this->deletedRecords as $type => $count) {
                $this->info("  - {$type}: {$count} records");
            }
            if (!$keepImages) {
                $this->info("  - Ảnh đã xóa: {$this->deletedImages} files");
            }

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Lỗi: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Thống kê dữ liệu của năm
     */
    protected function getYearStats($year)
    {
        return [
            'Sales' => Sale::where('year', $year)->count(),
            'Sale Items' => DB::table('sale_items')
                ->whereIn('sale_id', Sale::where('year', $year)->pluck('id'))
                ->count(),
            'Debts' => Debt::where('year', $year)->count(),
            'Payments' => Payment::where('year', $year)->count(),
            'Returns' => ReturnModel::where('year', $year)->count(),
            'Inventory Transactions' => InventoryTransaction::where('year', $year)->count(),
        ];
    }

    /**
     * Thống kê ảnh sẽ bị xóa
     */
    protected function getImageStats($year)
    {
        $stats = [
            'paintings_count' => 0,
            'paintings_size' => 0,
            'supplies_count' => 0,
            'supplies_size' => 0,
        ];

        // Paintings đã bán hết trong năm đó
        $soldPaintings = $this->getSoldOutPaintings($year);
        foreach ($soldPaintings as $painting) {
            if ($painting->image) {
                $path = storage_path("app/public/{$painting->image}");
                if (File::exists($path)) {
                    $stats['paintings_count']++;
                    $stats['paintings_size'] += filesize($path);
                }
            }
        }

        // Supplies đã hết trong năm đó
        $soldSupplies = $this->getSoldOutSupplies($year);
        foreach ($soldSupplies as $supply) {
            if ($supply->image) {
                $path = storage_path("app/public/{$supply->image}");
                if (File::exists($path)) {
                    $stats['supplies_count']++;
                    $stats['supplies_size'] += filesize($path);
                }
            }
        }

        return $stats;
    }

    /**
     * Lấy danh sách paintings đã bán hết
     */
    protected function getSoldOutPaintings($year)
    {
        // Paintings có trong giao dịch năm đó và hiện tại quantity = 0
        $paintingIds = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.year', $year)
            ->whereNotNull('sale_items.painting_id')
            ->pluck('sale_items.painting_id')
            ->unique();

        return Painting::whereIn('id', $paintingIds)
            ->where('quantity', 0)
            ->get();
    }

    /**
     * Lấy danh sách supplies đã hết
     */
    protected function getSoldOutSupplies($year)
    {
        $supplyIds = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.year', $year)
            ->whereNotNull('sale_items.supply_id')
            ->pluck('sale_items.supply_id')
            ->unique();

        return Supply::whereIn('id', $supplyIds)
            ->where('quantity', 0)
            ->get();
    }

    /**
     * Xóa ảnh của sản phẩm đã bán hết
     */
    protected function deleteImages($year)
    {
        // Xóa ảnh paintings đã bán hết
        $soldPaintings = $this->getSoldOutPaintings($year);
        foreach ($soldPaintings as $painting) {
            if ($painting->image) {
                $path = storage_path("app/public/{$painting->image}");
                if (File::exists($path)) {
                    File::delete($path);
                    $this->deletedImages++;
                }
            }
        }

        // Xóa ảnh supplies đã hết
        $soldSupplies = $this->getSoldOutSupplies($year);
        foreach ($soldSupplies as $supply) {
            if ($supply->image) {
                $path = storage_path("app/public/{$supply->image}");
                if (File::exists($path)) {
                    File::delete($path);
                    $this->deletedImages++;
                }
            }
        }
    }

    /**
     * Xóa dữ liệu giao dịch của năm
     */
    protected function deleteTransactionData($year)
    {
        // Lấy IDs
        $saleIds = Sale::where('year', $year)->pluck('id');
        $returnIds = ReturnModel::where('year', $year)->pluck('id');

        // Xóa exchange_items
        $count = DB::table('exchange_items')->whereIn('return_id', $returnIds)->delete();
        $this->deletedRecords['Exchange Items'] = $count;

        // Xóa return_items
        $count = DB::table('return_items')->whereIn('return_id', $returnIds)->delete();
        $this->deletedRecords['Return Items'] = $count;

        // Xóa returns
        $count = ReturnModel::where('year', $year)->delete();
        $this->deletedRecords['Returns'] = $count;

        // Xóa sale_items
        $count = DB::table('sale_items')->whereIn('sale_id', $saleIds)->delete();
        $this->deletedRecords['Sale Items'] = $count;

        // Xóa payments
        $count = Payment::where('year', $year)->delete();
        $this->deletedRecords['Payments'] = $count;

        // Xóa debts
        $count = Debt::where('year', $year)->delete();
        $this->deletedRecords['Debts'] = $count;

        // Xóa sales
        $count = Sale::where('year', $year)->delete();
        $this->deletedRecords['Sales'] = $count;

        // Xóa inventory_transactions
        $count = InventoryTransaction::where('year', $year)->delete();
        $this->deletedRecords['Inventory Transactions'] = $count;

        // Xóa frames của năm đó
        $frameIds = DB::table('frames')
            ->where('created_at', '>=', "{$year}-01-01")
            ->where('created_at', '<', ($year + 1) . "-01-01")
            ->pluck('id');
        
        $count = DB::table('frame_items')->whereIn('frame_id', $frameIds)->delete();
        $this->deletedRecords['Frame Items'] = $count;

        $count = DB::table('frames')
            ->where('created_at', '>=', "{$year}-01-01")
            ->where('created_at', '<', ($year + 1) . "-01-01")
            ->delete();
        $this->deletedRecords['Frames'] = $count;
    }

    /**
     * Xóa sản phẩm đã bán hết
     */
    protected function deleteSoldOutProducts($year)
    {
        // Xóa paintings đã bán hết
        $soldPaintings = $this->getSoldOutPaintings($year);
        $count = Painting::whereIn('id', $soldPaintings->pluck('id'))->delete();
        $this->deletedRecords['Paintings (sold out)'] = $count;

        // Xóa supplies đã hết
        $soldSupplies = $this->getSoldOutSupplies($year);
        $count = Supply::whereIn('id', $soldSupplies->pluck('id'))->delete();
        $this->deletedRecords['Supplies (sold out)'] = $count;
    }

    protected function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
