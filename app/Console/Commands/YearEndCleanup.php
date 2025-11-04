<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class YearEndCleanup extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'year:cleanup {--force}';

    /**
     * The console command description.
     */
    protected $description = 'Xóa dữ liệu giao dịch, chỉ giữ lại số đầu kỳ khi sang năm mới';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            $this->warn('⚠️  CẢNH BÁO: Command này sẽ XÓA dữ liệu giao dịch!');
            $this->warn('Chỉ giữ lại:');
            $this->warn('- Số dư đầu kỳ (customers, inventory)');
            $this->warn('- Danh mục (showrooms, employees, etc.)');
            $this->warn('');
            $this->warn('Sẽ XÓA:');
            $this->warn('- Tất cả phiếu bán hàng (sales)');
            $this->warn('- Tất cả phiếu đổi trả (returns)');
            $this->warn('- Tất cả giao dịch thanh toán (payments)');
            $this->warn('- Tất cả lịch sử công nợ (debts)');
            $this->warn('- Tất cả giao dịch kho (inventory_transactions)');
            $this->warn('');
            
            if (!$this->confirm('Bạn có chắc chắn muốn tiếp tục?')) {
                $this->info('Đã hủy.');
                return 0;
            }
        }

        $this->info('Bắt đầu cleanup dữ liệu cuối năm...');

        try {
            // Lưu ý: TRUNCATE tự động commit, không cần transaction
            // Tắt foreign key checks để có thể truncate
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            $this->info('Đã tắt foreign key checks');

            // Đếm số lượng trước khi xóa
            $saleItemsCount = DB::table('sale_items')->count();
            $salesCount = DB::table('sales')->count();
            $returnItemsCount = DB::table('return_items')->count();
            $returnsCount = DB::table('returns')->count();
            $paymentsCount = DB::table('payments')->count();
            $debtsCount = DB::table('debts')->count();
            $inventoryTransCount = DB::table('inventory_transactions')->count();

            // 1. Xóa chi tiết phiếu bán
            DB::table('sale_items')->truncate();
            $this->info("✓ Đã xóa {$saleItemsCount} chi tiết phiếu bán");

            // 2. Xóa phiếu bán
            DB::table('sales')->truncate();
            $this->info("✓ Đã xóa {$salesCount} phiếu bán hàng");

            // 3. Xóa chi tiết phiếu đổi trả
            DB::table('return_items')->truncate();
            $this->info("✓ Đã xóa {$returnItemsCount} chi tiết phiếu đổi trả");

            // 4. Xóa phiếu đổi trả
            DB::table('returns')->truncate();
            $this->info("✓ Đã xóa {$returnsCount} phiếu đổi trả");

            // 5. Xóa thanh toán
            DB::table('payments')->truncate();
            $this->info("✓ Đã xóa {$paymentsCount} giao dịch thanh toán");

            // 6. Xóa lịch sử công nợ
            DB::table('debts')->truncate();
            $this->info("✓ Đã xóa {$debtsCount} lịch sử công nợ");

            // 7. Xóa giao dịch kho
            DB::table('inventory_transactions')->truncate();
            $this->info("✓ Đã xóa {$inventoryTransCount} giao dịch kho");

            // 8. Reset số dư khách hàng về 0 (hoặc giữ nguyên nếu muốn)
            // Tùy chọn: Có thể giữ số dư cuối kỳ làm đầu kỳ năm mới
            // DB::table('customers')->update(['debt_balance' => 0]);
            // $this->info("✓ Đã reset số dư khách hàng");

            // Bật lại foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->info('Đã bật lại foreign key checks');

            $this->info('');
            $this->info('✅ Cleanup hoàn tất!');
            $this->info('Dữ liệu đã được xóa, chỉ giữ lại số đầu kỳ.');
            
            Log::info('Year-end cleanup completed successfully', [
                'sales' => $salesCount,
                'returns' => $returnsCount,
                'payments' => $paymentsCount,
                'debts' => $debtsCount,
                'inventory_transactions' => $inventoryTransCount,
            ]);

            return 0;

        } catch (\Exception $e) {
            // Đảm bảo bật lại foreign key checks ngay cả khi có lỗi
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
                $this->info('Đã bật lại foreign key checks sau lỗi');
            } catch (\Exception $fkException) {
                // Ignore nếu không thể bật lại
            }
            
            $this->error("Lỗi: " . $e->getMessage());
            Log::error("Year-end cleanup error: " . $e->getMessage());
            return 1;
        }
    }
}
