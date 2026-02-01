<?php

namespace App\Console\Commands;

use App\Models\DatabaseExport;
use App\Models\Painting;
use App\Models\Sale;
use App\Models\Supply;
use App\Models\YearDatabase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use App\Services\YearDatabaseService;

class ExportYearData extends Command
{
    protected $signature = 'year:export {year} {--include-images : Bao gồm ảnh trong file export}';
    protected $description = 'Export dữ liệu của một năm ra file ZIP (SQL + ảnh)';

    public function handle(YearDatabaseService $yearService)
    {
        $year = $this->argument('year');
        $includeImages = $this->option('include-images');

        $this->info("Bắt đầu export dữ liệu năm {$year}...");

        // Kiểm tra năm có tồn tại không
        $yearDb = YearDatabase::where('year', $year)->first();
        if (!$yearDb) {
            $this->error("Năm {$year} không tồn tại trong hệ thống!");
            return 1;
        }

        try {
            // Tạo thư mục tạm
            $timestamp = now()->format('Y-m-d_His');
            $exportDir = storage_path("app/temp/export_{$year}_{$timestamp}");
            File::makeDirectory($exportDir, 0755, true);

            // 1. Export SQL
            $this->info('Đang export database...');
            $sqlFile = $this->exportSql($year, $exportDir, $yearService);

            // 2. Export ảnh nếu được yêu cầu
            $imagesDir = null;
            if ($includeImages) {
                $this->info('Đang copy ảnh...');
                $imagesDir = $this->exportImages($year, $exportDir);
            }

            // 3. Tạo file ZIP
            $this->info('Đang tạo file ZIP...');
            $zipFile = $this->createZip($year, $timestamp, $exportDir, $includeImages);

            // 4. Lưu thông tin export
            $export = DatabaseExport::create([
                'year' => $year,
                'filename' => basename($zipFile),
                'file_path' => str_replace(storage_path(), '', $zipFile),
                'file_size' => filesize($zipFile),
                'status' => 'completed',
                'description' => $includeImages ? 'Export đầy đủ (SQL + ảnh)' : 'Export SQL only',
                'exported_at' => now(),
            ]);

            // 5. Cleanup thư mục tạm
            File::deleteDirectory($exportDir);

            $this->info("✅ Export thành công!");
            $this->info("File: {$zipFile}");
            $this->info("Kích thước: " . $this->formatBytes(filesize($zipFile)));

            return 0;
        } catch (\Exception $e) {
            $this->error("Lỗi: " . $e->getMessage());
            // Cleanup nếu có lỗi
            if (isset($exportDir) && File::exists($exportDir)) {
                File::deleteDirectory($exportDir);
            }
            return 1;
        }
    }

    /**
     * Export FULL database bằng mysqldump (có thể import trực tiếp)
     */
    protected function exportSql($year, $exportDir, YearDatabaseService $yearService)
    {
        $sqlFile = "{$exportDir}/database_{$year}.sql";

        // Lấy config database
        $dbName = config('database.connections.mysql.database');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', '3306');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        // Sử dụng mysqldump để export full database
        $command = sprintf(
            '%s --host=%s --port=%s --user=%s %s --single-transaction --routines --triggers %s > %s 2>&1',
            $yearService->getMysqlExecutable('mysqldump'),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $password ? '--password=' . escapeshellarg($password) : '',
            escapeshellarg($dbName),
            escapeshellarg($sqlFile)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($sqlFile)) {
            throw new \Exception("Lỗi mysqldump: " . implode("\n", $output));
        }

        // Thêm header vào file
        $header = "-- =============================================\n";
        $header .= "-- Full Database Export - Năm {$year}\n";
        $header .= "-- Ngày export: " . now()->format('Y-m-d H:i:s') . "\n";
        $header .= "-- Database: {$dbName}\n";
        $header .= "-- =============================================\n\n";

        $content = file_get_contents($sqlFile);
        file_put_contents($sqlFile, $header . $content);

        $this->info("  - Database: {$dbName}");
        $this->info("  - Kích thước SQL: " . $this->formatBytes(filesize($sqlFile)));

        return $sqlFile;
    }

    /**
     * Export snapshot tồn kho
     */
    protected function exportInventorySnapshot($year)
    {
        $sql = "";

        // Paintings snapshot
        $paintings = Painting::where('quantity', '>', 0)->get();
        if ($paintings->count() > 0) {
            $sql .= "-- Paintings snapshot (tồn kho cuối năm {$year})\n";
            $sql .= "-- Total: {$paintings->count()} items\n";
            foreach ($paintings as $p) {
                $sql .= "-- {$p->code}: {$p->name} - SL: {$p->quantity}\n";
            }
            $sql .= "\n";
        }

        // Supplies snapshot
        $supplies = Supply::where('quantity', '>', 0)->get();
        if ($supplies->count() > 0) {
            $sql .= "-- Supplies snapshot (tồn kho cuối năm {$year})\n";
            $sql .= "-- Total: {$supplies->count()} items\n";
            foreach ($supplies as $s) {
                $sql .= "-- {$s->code}: {$s->name} - SL: {$s->quantity} {$s->unit}\n";
            }
            $sql .= "\n";
        }

        return $sql;
    }

    /**
     * Export TOÀN BỘ thư mục storage/app/public (tất cả ảnh)
     */
    protected function exportImages($year, $exportDir)
    {
        $storagePublicPath = storage_path('app/public');

        if (!File::exists($storagePublicPath)) {
            $this->warn("Thư mục storage/app/public không tồn tại!");
            return null;
        }

        // Copy toàn bộ thư mục storage/app/public vào export
        $storageDir = "{$exportDir}/storage";
        File::makeDirectory($storageDir, 0755, true);

        // Đếm số file
        $totalFiles = 0;
        $totalSize = 0;

        // Copy tất cả files và folders
        $this->copyDirectory($storagePublicPath, $storageDir, $totalFiles, $totalSize);

        $this->info("  - Tổng số file: {$totalFiles}");
        $this->info("  - Tổng dung lượng: " . $this->formatBytes($totalSize));

        return $storageDir;
    }

    /**
     * Copy toàn bộ thư mục đệ quy
     */
    protected function copyDirectory($source, $dest, &$totalFiles, &$totalSize)
    {
        if (!File::exists($dest)) {
            File::makeDirectory($dest, 0755, true);
        }

        $items = File::allFiles($source);
        foreach ($items as $item) {
            $relativePath = $item->getRelativePathname();
            $destPath = $dest . DIRECTORY_SEPARATOR . $relativePath;

            // Tạo thư mục cha nếu chưa có
            $destDir = dirname($destPath);
            if (!File::exists($destDir)) {
                File::makeDirectory($destDir, 0755, true);
            }

            File::copy($item->getRealPath(), $destPath);
            $totalFiles++;
            $totalSize += $item->getSize();
        }
    }

    /**
     * Tạo file ZIP
     */
    protected function createZip($year, $timestamp, $exportDir, $includeImages)
    {
        $zipFileName = "art_gallery_{$year}_{$timestamp}" . ($includeImages ? '_full' : '_sql') . ".zip";
        $zipPath = storage_path("backups/databases/{$zipFileName}");

        // Tạo thư mục nếu chưa có
        File::makeDirectory(dirname($zipPath), 0755, true, true);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Không thể tạo file ZIP");
        }

        // Thêm file SQL
        $sqlFile = "{$exportDir}/database_{$year}.sql";
        if (File::exists($sqlFile)) {
            $zip->addFile($sqlFile, "database_{$year}.sql");
        }

        // Thêm thư mục storage nếu có
        if ($includeImages) {
            $storageDir = "{$exportDir}/storage";
            if (File::exists($storageDir)) {
                $this->addDirectoryToZip($zip, $storageDir, 'storage');
            }
        }

        $zip->close();
        return $zipPath;
    }

    /**
     * Thêm thư mục vào ZIP
     */
    protected function addDirectoryToZip($zip, $dir, $zipPath)
    {
        $files = File::allFiles($dir);
        foreach ($files as $file) {
            // Chuẩn hóa path separator thành / cho ZIP (cross-platform)
            $relativePath = $zipPath . '/' . str_replace('\\', '/', $file->getRelativePathname());
            $zip->addFile($file->getRealPath(), $relativePath);
        }
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
