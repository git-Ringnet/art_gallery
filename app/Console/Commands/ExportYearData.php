<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\YearDatabase;

class ExportYearData extends Command
{
    protected $signature = 'year:create-archive {year : Năm cần export}';
    protected $description = 'Export dữ liệu của năm chỉ định sang database riêng';

    public function handle()
    {
        $year = $this->argument('year');
        $newDbName = "art_gallery_{$year}";
        
        $this->info("🚀 Bắt đầu export dữ liệu năm {$year}...");
        
        // Bước 1: Kiểm tra năm đã được archive chưa
        if (YearDatabase::where('year', $year)->where('is_active', false)->exists()) {
            $this->error("❌ Năm {$year} đã được archive trước đó!");
            return 1;
        }
        
        // Bước 2: Tạo database mới
        $this->info("📦 Tạo database {$newDbName}...");
        try {
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$newDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->info("✓ Đã tạo database {$newDbName}");
        } catch (\Exception $e) {
            $this->error("❌ Lỗi tạo database: " . $e->getMessage());
            return 1;
        }
        
        // Bước 3: Copy cấu trúc bảng
        $this->info("📋 Copy cấu trúc bảng...");
        $tables = ['sales', 'sale_items', 'debts', 'returns', 'return_items', 'exchange_items', 
                   'payments', 'inventory_transactions', 'customers', 'showrooms', 'paintings', 'supplies'];
        
        foreach ($tables as $table) {
            try {
                $createTable = DB::select("SHOW CREATE TABLE `{$table}`")[0]->{'Create Table'};
                DB::connection()->getPdo()->exec("USE `{$newDbName}`");
                DB::connection()->getPdo()->exec($createTable);
                $this->info("  ✓ {$table}");
            } catch (\Exception $e) {
                $this->warn("  ⚠ Lỗi copy {$table}: " . $e->getMessage());
            }
        }
        
        // Switch back to main database
        DB::connection()->getPdo()->exec("USE `" . env('DB_DATABASE') . "`");
        
        // Bước 4: Export dữ liệu năm chỉ định
        $this->info("📤 Export dữ liệu năm {$year}...");
        
        // Sales và related tables
        $salesCount = $this->exportTable('sales', $newDbName, $year);
        $this->info("  ✓ Sales: {$salesCount} records");
        
        if ($salesCount > 0) {
            $saleIds = DB::table('sales')->where('year', $year)->pluck('id')->toArray();
            $saleItemsCount = $this->exportRelatedTable('sale_items', 'sale_id', $saleIds, $newDbName);
            $this->info("  ✓ Sale Items: {$saleItemsCount} records");
        }
        
        // Debts
        $debtsCount = $this->exportTable('debts', $newDbName, $year);
        $this->info("  ✓ Debts: {$debtsCount} records");
        
        // Returns và related tables
        $returnsCount = $this->exportTable('returns', $newDbName, $year);
        $this->info("  ✓ Returns: {$returnsCount} records");
        
        if ($returnsCount > 0) {
            $returnIds = DB::table('returns')->where('year', $year)->pluck('id')->toArray();
            $returnItemsCount = $this->exportRelatedTable('return_items', 'return_id', $returnIds, $newDbName);
            $exchangeItemsCount = $this->exportRelatedTable('exchange_items', 'return_id', $returnIds, $newDbName);
            $this->info("  ✓ Return Items: {$returnItemsCount} records");
            $this->info("  ✓ Exchange Items: {$exchangeItemsCount} records");
        }
        
        // Payments
        $paymentsCount = $this->exportTable('payments', $newDbName, $year);
        $this->info("  ✓ Payments: {$paymentsCount} records");
        
        // Inventory Transactions
        $inventoryCount = $this->exportTable('inventory_transactions', $newDbName, $year);
        $this->info("  ✓ Inventory Transactions: {$inventoryCount} records");
        
        // Bước 5: Copy master data (customers, showrooms)
        $this->info("📋 Copy master data...");
        $this->copyMasterData('customers', $newDbName);
        $this->copyMasterData('showrooms', $newDbName);
        
        // Bước 6: Tạo snapshot tồn kho cuối năm
        $this->info("📸 Tạo snapshot tồn kho cuối năm {$year}...");
        $this->createInventorySnapshot($newDbName, $year);
        
        // Bước 7: Cập nhật bảng year_databases
        $this->info("💾 Cập nhật year_databases...");
        YearDatabase::create([
            'year' => $year,
            'database_name' => $newDbName,
            'is_active' => false,
            'is_on_server' => true,
            'description' => "Database năm {$year} - Đã lưu trữ",
            'archived_at' => now(),
        ]);
        
        $this->info("✅ Hoàn tất export dữ liệu năm {$year}!");
        $this->info("📊 Database: {$newDbName}");
        
        return 0;
    }
    
    private function exportTable($table, $targetDb, $year)
    {
        $data = DB::table($table)->where('year', $year)->get();
        
        if ($data->isEmpty()) {
            return 0;
        }
        
        foreach ($data as $row) {
            DB::connection()->getPdo()->exec("USE `{$targetDb}`");
            DB::table($table)->insert((array) $row);
            DB::connection()->getPdo()->exec("USE `" . env('DB_DATABASE') . "`");
        }
        
        return $data->count();
    }
    
    private function exportRelatedTable($table, $foreignKey, $ids, $targetDb)
    {
        if (empty($ids)) {
            return 0;
        }
        
        $data = DB::table($table)->whereIn($foreignKey, $ids)->get();
        
        if ($data->isEmpty()) {
            return 0;
        }
        
        foreach ($data as $row) {
            DB::connection()->getPdo()->exec("USE `{$targetDb}`");
            DB::table($table)->insert((array) $row);
            DB::connection()->getPdo()->exec("USE `" . env('DB_DATABASE') . "`");
        }
        
        return $data->count();
    }
    
    private function copyMasterData($table, $targetDb)
    {
        $data = DB::table($table)->get();
        
        foreach ($data as $row) {
            DB::connection()->getPdo()->exec("USE `{$targetDb}`");
            DB::table($table)->insert((array) $row);
            DB::connection()->getPdo()->exec("USE `" . env('DB_DATABASE') . "`");
        }
        
        $this->info("  ✓ {$table}: {$data->count()} records");
    }
    
    private function createInventorySnapshot($targetDb, $year)
    {
        // Copy paintings
        $paintings = DB::table('paintings')->get();
        foreach ($paintings as $painting) {
            DB::connection()->getPdo()->exec("USE `{$targetDb}`");
            DB::table('paintings')->insert((array) $painting);
            DB::connection()->getPdo()->exec("USE `" . env('DB_DATABASE') . "`");
        }
        $this->info("  ✓ Paintings: {$paintings->count()} records");
        
        // Copy supplies
        $supplies = DB::table('supplies')->get();
        foreach ($supplies as $supply) {
            DB::connection()->getPdo()->exec("USE `{$targetDb}`");
            DB::table('supplies')->insert((array) $supply);
            DB::connection()->getPdo()->exec("USE `" . env('DB_DATABASE') . "`");
        }
        $this->info("  ✓ Supplies: {$supplies->count()} records");
    }
}
