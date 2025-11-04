<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\YearDatabase;
use App\Models\DatabaseExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class YearEndBackup extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'year:backup {--description=}';

    /**
     * The console command description.
     */
    protected $description = 'Tự động backup database cuối năm';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Bắt đầu backup database cuối năm...');

        $currentYear = YearDatabase::getCurrentYear();
        $year = $currentYear->year ?? date('Y');
        $description = $this->option('description') ?: "Backup tự động cuối năm {$year}";

        try {
            // Tạo tên file
            $timestamp = now()->format('Y-m-d_His');
            $filename = "art_gallery_{$year}_{$timestamp}.sql";
            $relativePath = "backups/databases/{$filename}";
            $fullPath = storage_path($relativePath);

            // Tạo thư mục nếu chưa có
            $dir = dirname($fullPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            // Tạo record export
            $export = DatabaseExport::create([
                'year' => $year,
                'filename' => $filename,
                'file_path' => $relativePath,
                'file_size' => 0,
                'status' => 'processing',
                'description' => $description,
                'exported_by' => 1, // System user
                'exported_at' => now(),
            ]);

            $this->info("Đang export database {$currentYear->database_name}...");

            // Lấy config database từ .env (hoạt động trên cả local và server)
            $dbName = config('database.connections.mysql.database');
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port', '3306');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');

            $this->info("Database: {$dbName}");
            $this->info("Host: {$host}:{$port}");

            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s %s %s > %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                $password ? '--password=' . escapeshellarg($password) : '',
                escapeshellarg($dbName),
                escapeshellarg($fullPath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($fullPath)) {
                $export->update(['status' => 'failed']);
                $this->error('Lỗi khi export database');
                Log::error("Year-end backup failed for year {$year}");
                return 1;
            }

            // Cập nhật kích thước file
            $fileSize = filesize($fullPath);
            $export->update([
                'file_size' => $fileSize,
                'status' => 'completed',
            ]);

            $this->info("✅ Backup thành công!");
            $this->info("File: {$filename}");
            $this->info("Kích thước: " . $this->formatBytes($fileSize));
            
            Log::info("Year-end backup completed successfully", [
                'year' => $year,
                'filename' => $filename,
                'size' => $fileSize,
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error("Lỗi: " . $e->getMessage());
            Log::error("Year-end backup error: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
