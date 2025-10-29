<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\YearDatabase;

class ImportYearDatabase extends Command
{
    protected $signature = 'year:import {file : Đường dẫn file SQL} {year : Năm của database}';
    protected $description = 'Import database năm cũ từ file backup';

    public function handle()
    {
        $file = $this->argument('file');
        $year = $this->argument('year');
        
        // Kiểm tra file tồn tại
        if (!file_exists($file)) {
            $this->error("❌ File không tồn tại: {$file}");
            return 1;
        }
        
        $this->info("📥 Bắt đầu import database năm {$year}...");
        
        // Kiểm tra năm đã tồn tại chưa
        $yearDb = YearDatabase::where('year', $year)->first();
        
        if ($yearDb && $yearDb->is_on_server) {
            $this->warn("⚠️  Database năm {$year} đã tồn tại trên server!");
            if (!$this->confirm("Bạn có muốn ghi đè không?")) {
                return 0;
            }
        }
        
        $dbName = "art_gallery_{$year}";
        
        // Tạo database
        $this->info("📦 Tạo database {$dbName}...");
        try {
            DB::statement("DROP DATABASE IF EXISTS `{$dbName}`");
            DB::statement("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->info("  ✓ Đã tạo database");
        } catch (\Exception $e) {
            $this->error("❌ Lỗi tạo database: " . $e->getMessage());
            return 1;
        }
        
        // Import dữ liệu
        $this->info("📥 Import dữ liệu...");
        
        $host = env('DB_HOST', '127.0.0.1');
        $port = env('DB_PORT', '3306');
        $username = env('DB_USERNAME', 'root');
        $password = env('DB_PASSWORD', '');
        
        // Giải nén nếu là file .gz
        if (str_ends_with($file, '.gz')) {
            $this->info("📦 Giải nén file...");
            $unzippedFile = str_replace('.gz', '', $file);
            exec("gunzip -c {$file} > {$unzippedFile}", $output, $returnCode);
            
            if ($returnCode !== 0) {
                $this->error("❌ Lỗi giải nén file");
                return 1;
            }
            
            $file = $unzippedFile;
        }
        
        // Import SQL
        $command = sprintf(
            'mysql --host=%s --port=%s --user=%s %s %s < %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $password ? '--password=' . escapeshellarg($password) : '',
            escapeshellarg($dbName),
            escapeshellarg($file)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            $this->error("❌ Lỗi import database");
            return 1;
        }
        
        // Cập nhật year_databases
        $this->info("💾 Cập nhật year_databases...");
        YearDatabase::updateOrCreate(
            ['year' => $year],
            [
                'database_name' => $dbName,
                'is_active' => false,
                'is_on_server' => true,
                'description' => "Database năm {$year} - Đã import từ backup",
                'backup_location' => $file,
            ]
        );
        
        $this->info("✅ Import thành công!");
        $this->info("📊 Database: {$dbName}");
        $this->info("💡 Bây giờ bạn có thể chọn năm {$year} để xem dữ liệu");
        
        return 0;
    }
}
