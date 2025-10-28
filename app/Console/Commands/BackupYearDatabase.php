<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\YearDatabase;

class BackupYearDatabase extends Command
{
    protected $signature = 'year:backup {year? : Năm cần backup (mặc định là năm hiện tại)} {--path= : Đường dẫn lưu backup}';
    protected $description = 'Backup database của năm chỉ định';

    public function handle()
    {
        $year = $this->argument('year') ?? YearDatabase::getCurrentYear()?->year ?? date('Y');
        $yearDb = YearDatabase::where('year', $year)->first();
        
        if (!$yearDb) {
            $this->error("❌ Không tìm thấy thông tin năm {$year}");
            return 1;
        }
        
        if (!$yearDb->is_on_server) {
            $this->error("❌ Database năm {$year} không có trên server");
            return 1;
        }
        
        $this->info("💾 Bắt đầu backup database năm {$year}...");
        
        $dbName = $yearDb->database_name;
        $timestamp = now()->format('Y-m-d_His');
        $filename = "{$dbName}_{$timestamp}.sql";
        
        // Đường dẫn backup
        $backupPath = $this->option('path') ?? storage_path('backups/databases');
        
        // Tạo thư mục nếu chưa có
        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        
        $fullPath = "{$backupPath}/{$filename}";
        
        // Lấy thông tin database từ env
        $host = env('DB_HOST', '127.0.0.1');
        $port = env('DB_PORT', '3306');
        $username = env('DB_USERNAME', 'root');
        $password = env('DB_PASSWORD', '');
        
        // Tạo lệnh mysqldump
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s %s %s > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $password ? '--password=' . escapeshellarg($password) : '',
            escapeshellarg($dbName),
            escapeshellarg($fullPath)
        );
        
        // Thực thi backup
        $this->info("📤 Đang export database...");
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            $this->error("❌ Lỗi khi backup database");
            return 1;
        }
        
        // Kiểm tra file đã tạo
        if (!file_exists($fullPath)) {
            $this->error("❌ File backup không được tạo");
            return 1;
        }
        
        $fileSize = filesize($fullPath);
        $fileSizeMB = round($fileSize / 1024 / 1024, 2);
        
        // Cập nhật thông tin backup
        $yearDb->update([
            'backup_location' => $fullPath,
        ]);
        
        $this->info("✅ Backup thành công!");
        $this->info("📁 File: {$fullPath}");
        $this->info("📊 Kích thước: {$fileSizeMB} MB");
        
        // Gợi ý nén file
        $this->info("");
        $this->info("💡 Gợi ý: Nén file để tiết kiệm dung lượng:");
        $this->info("  gzip {$fullPath}");
        
        return 0;
    }
}
