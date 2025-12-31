<?php

namespace App\Http\Controllers;

use App\Services\YearDatabaseService;
use App\Services\ActivityLogger;
use App\Models\YearDatabase;
use App\Models\DatabaseExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class YearDatabaseController extends Controller
{
    protected $yearService;
    protected $activityLogger;

    public function __construct(YearDatabaseService $yearService, ActivityLogger $activityLogger)
    {
        $this->yearService = $yearService;
        $this->activityLogger = $activityLogger;
    }

    /**
     * Hiển thị trang quản lý backup & restore database
     */
    public function index()
    {
        $currentYear = YearDatabase::getCurrentYear();

        // Đồng bộ file backup với database (tự động tạo record cho file chưa có trong DB)
        $this->syncBackupFiles();

        // Lấy danh sách tất cả exports (không filter theo năm)
        $exports = DatabaseExport::where('status', 'completed')
            ->orderBy('exported_at', 'desc')
            ->get();
        $exportsCount = $exports->count();

        return view('year-database.simple', compact(
            'currentYear',
            'exports',
            'exportsCount'
        ));
    }

    /**
     * Đồng bộ file backup với database
     * Tạo record cho các file tồn tại trong folder nhưng chưa có trong DB
     */
    private function syncBackupFiles()
    {
        $backupDir = storage_path('backups/databases');
        if (!is_dir($backupDir)) {
            return;
        }

        $files = glob($backupDir . '/*');
        foreach ($files as $filePath) {
            if (!is_file($filePath)) {
                continue;
            }

            $filename = basename($filePath);
            
            // Kiểm tra xem file đã có trong DB chưa
            $exists = DatabaseExport::where('filename', $filename)->exists();
            if ($exists) {
                continue;
            }

            // Parse thông tin từ tên file
            // Format: art_gallery_YYYY_YYYY-MM-DD_HHMMSS.sql hoặc .zip
            $year = date('Y');
            $isZip = str_ends_with(strtolower($filename), '.zip');
            $isImport = strpos($filename, '_full') !== false; // ZIP thường là import
            
            // Thử parse năm từ tên file
            if (preg_match('/art_gallery_(\d{4})_/', $filename, $matches)) {
                $year = (int) $matches[1];
            }

            // Tạo record
            try {
                DatabaseExport::create([
                    'year' => $year,
                    'filename' => $filename,
                    'file_path' => "backups/databases/{$filename}",
                    'file_size' => filesize($filePath),
                    'status' => 'completed',
                    'type' => $isZip ? 'import' : 'export',
                    'description' => $isZip ? 'Import (tự động phát hiện)' : 'Export (tự động phát hiện)',
                    'exported_by' => Auth::id(),
                    'exported_at' => \Carbon\Carbon::createFromTimestamp(filemtime($filePath)),
                    'is_encrypted' => !$isZip && str_ends_with(strtolower($filename), '.sql'),
                    'includes_images' => $isZip,
                ]);
                Log::info("Đã tạo record cho file backup: {$filename}");
            } catch (\Exception $e) {
                Log::error("Lỗi tạo record cho file {$filename}: " . $e->getMessage());
            }
        }
    }

    /**
     * Hiển thị trang quản lý năm (export, cleanup, prepare)
     */
    public function manage()
    {
        $currentYear = YearDatabase::getCurrentYear();
        $allYears = YearDatabase::getAllYears();
        $totalExports = DatabaseExport::count();

        return view('year-database.manage', compact(
            'currentYear',
            'allYears',
            'totalExports'
        ));
    }

    /**
     * Chuyển sang năm khác
     */
    public function switchYear(Request $request)
    {
        $year = $request->input('year');

        try {
            $this->yearService->setSelectedYear($year);

            // Log activity
            $this->activityLogger->log(
                \App\Models\ActivityLog::TYPE_UPDATE,
                \App\Models\ActivityLog::MODULE_YEAR_DATABASE,
                null,
                ['year' => $year],
                "Chuyển sang năm {$year}"
            );

            return response()->json([
                'success' => true,
                'message' => "Đã chuyển sang xem dữ liệu năm {$year}",
                'year' => $year,
                'is_archive' => $this->yearService->isViewingArchive(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reset về năm hiện tại
     */
    public function resetYear()
    {
        $this->yearService->resetToCurrentYear();

        return response()->json([
            'success' => true,
            'message' => 'Đã quay lại năm hiện tại',
            'year' => $this->yearService->getCurrentYear(),
        ]);
    }

    /**
     * Lấy thông tin năm hiện tại
     */
    public function getCurrentInfo()
    {
        return response()->json([
            'current_year' => $this->yearService->getCurrentYear(),
            'selected_year' => $this->yearService->getSelectedYear(),
            'is_viewing_archive' => $this->yearService->isViewingArchive(),
            'available_years' => $this->yearService->getAvailableYears(),
        ]);
    }

    /**
     * Export database của năm (tự động mã hóa)
     */
    public function exportDatabase(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'description' => 'nullable|string|max:500',
            'include_images' => 'nullable|boolean', // Có kèm hình ảnh không
        ]);

        $year = $request->year;
        $yearDb = YearDatabase::where('year', $year)->first();

        if (!$yearDb) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy thông tin năm {$year}",
            ], 404);
        }

        if (!$yearDb->is_on_server) {
            return response()->json([
                'success' => false,
                'message' => "Database năm {$year} không có trên server",
            ], 400);
        }

        try {
            $timestamp = now()->format('Y-m-d_His');
            $includeImages = $request->input('include_images', false);
            $isEncrypted = true; // Luôn mã hóa
            
            // Tạo thư mục backup
            $backupDir = storage_path('backups/databases');
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Export SQL
            $sqlFilename = "art_gallery_{$year}_{$timestamp}.sql";
            $sqlPath = $backupDir . DIRECTORY_SEPARATOR . $sqlFilename;

            $dbName = $yearDb->database_name;
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port', '3306');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');

            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s %s %s > %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                $password ? '--password=' . escapeshellarg($password) : '',
                escapeshellarg($dbName),
                escapeshellarg($sqlPath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($sqlPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi khi export database',
                ], 500);
            }

            // Nếu kèm hình ảnh → Tạo ZIP
            if ($includeImages) {
                $zipFilename = "art_gallery_{$year}_{$timestamp}_full.zip";
                $zipPath = $backupDir . DIRECTORY_SEPARATOR . $zipFilename;
                
                $zip = new \ZipArchive();
                if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                    unlink($sqlPath);
                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể tạo file ZIP',
                    ], 500);
                }

                // Thêm file SQL vào ZIP
                $zip->addFile($sqlPath, 'database.sql');

                // Thêm thư mục hình ảnh vào ZIP
                $storagePublicPath = storage_path('app/public');
                if (is_dir($storagePublicPath)) {
                    $this->addFolderToZip($zip, $storagePublicPath, 'storage');
                }

                $zip->close();
                
                // Xóa file SQL tạm
                unlink($sqlPath);
                
                $finalFilename = $zipFilename;
                $finalPath = $zipPath;
                $relativePath = "backups/databases/{$zipFilename}";
            } else {
                // Mã hóa file SQL nếu được yêu cầu
                if ($isEncrypted) {
                    try {
                        $encryptedPath = \App\Services\DatabaseEncryptionService::encrypt($sqlPath);
                        unlink($sqlPath);
                        rename($encryptedPath, $sqlPath);
                        Log::info("File đã được mã hóa: {$sqlPath}");
                    } catch (\Exception $e) {
                        Log::error("Lỗi mã hóa file: " . $e->getMessage());
                    }
                }
                
                $finalFilename = $sqlFilename;
                $finalPath = $sqlPath;
                $relativePath = "backups/databases/{$sqlFilename}";
            }

            // Tạo record export
            $export = DatabaseExport::create([
                'year' => $year,
                'filename' => $finalFilename,
                'file_path' => $relativePath,
                'file_size' => filesize($finalPath),
                'status' => 'completed',
                'description' => $request->description,
                'exported_by' => Auth::id(),
                'exported_at' => now(),
                'is_encrypted' => $isEncrypted && !$includeImages,
                'includes_images' => $includeImages,
            ]);

            $message = "Export database năm {$year} thành công";
            if ($includeImages) {
                $message .= ' (kèm hình ảnh)';
            } elseif ($isEncrypted) {
                $message .= ' (đã mã hóa)';
            }

            // Log activity
            $this->activityLogger->log(
                \App\Models\ActivityLog::TYPE_CREATE,
                \App\Models\ActivityLog::MODULE_YEAR_DATABASE,
                $export,
                [
                    'year' => $year,
                    'includes_images' => $includeImages,
                    'file_size' => $export->file_size,
                ],
                "Export database năm {$year}" . ($includeImages ? ' (kèm ảnh)' : '')
            );

            return response()->json([
                'success' => true,
                'message' => $message,
                'export' => [
                    'id' => $export->id,
                    'filename' => $export->filename,
                    'file_size' => $export->file_size_formatted,
                    'exported_at' => $export->exported_at->format('d/m/Y H:i:s'),
                    'is_encrypted' => $isEncrypted && !$includeImages,
                    'includes_images' => $includeImages,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Export error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download file export
     */
    public function downloadExport($id)
    {
        $export = DatabaseExport::findOrFail($id);

        if (!$export->fileExists()) {
            return back()->with('error', 'File không tồn tại');
        }

        $fullPath = storage_path($export->file_path);

        return response()->download($fullPath, $export->filename);
    }

    /**
     * Xóa file export
     */
    public function deleteExport($id)
    {
        $export = DatabaseExport::findOrFail($id);

        // Xóa file
        if ($export->fileExists()) {
            unlink(storage_path($export->file_path));
        }

        // Xóa record
        $export->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa file export',
        ]);
    }

    /**
     * Import database từ file upload
     * Nếu import SQL + thư mục ảnh riêng: không lưu vào backup (vì không đầy đủ)
     * Nếu import chỉ SQL: lưu vào backup và lịch sử
     */
    public function importDatabase(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'file' => 'required|file|max:512000', // Max 500MB
            'has_images' => 'nullable|boolean',
            'images_count' => 'nullable|integer',
        ]);

        $year = $request->year;
        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $originalFileSize = $file->getSize();
        $hasImages = filter_var($request->input('has_images', false), FILTER_VALIDATE_BOOLEAN);
        $imagesCount = (int) $request->input('images_count', 0);
        $tempSessionId = $request->input('temp_session_id'); // Session ID của ảnh đã upload trước

        // Validate file extension
        $allowedExtensions = ['sql', 'gz'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions)) {
            return response()->json([
                'success' => false,
                'message' => 'File không hợp lệ. Chỉ chấp nhận file .sql hoặc .sql.gz',
            ], 400);
        }

        $fullPath = null;
        $unzippedPath = null;
        $savedFilePath = null;
        
        // Nếu import SQL + ảnh riêng thì KHÔNG lưu vào backup (vì không đầy đủ)
        $shouldSaveToBackup = !$hasImages;

        try {
            Log::info("Bắt đầu import database năm {$year} từ file {$filename}" . ($hasImages ? " (kèm {$imagesCount} ảnh riêng - không lưu backup)" : ""));

            // Tạo thư mục temp
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            if ($shouldSaveToBackup) {
                // Lưu file vào backup folder (chỉ khi import SQL thuần)
                $backupDir = storage_path('backups/databases');
                if (!file_exists($backupDir)) {
                    mkdir($backupDir, 0755, true);
                }

                $savedFilename = $filename;
                $savedFilePath = $backupDir . DIRECTORY_SEPARATOR . $savedFilename;
                
                // Nếu file đã tồn tại, thêm timestamp
                if (file_exists($savedFilePath)) {
                    $savedFilename = pathinfo($filename, PATHINFO_FILENAME) . '_' . time() . '.' . pathinfo($filename, PATHINFO_EXTENSION);
                    $savedFilePath = $backupDir . DIRECTORY_SEPARATOR . $savedFilename;
                }

                // Copy file gốc vào backup folder
                $file->move($backupDir, $savedFilename);
                
                if (!file_exists($savedFilePath)) {
                    Log::error("Không thể lưu file vào backup: {$savedFilePath}");
                    throw new \Exception('Không thể lưu file upload');
                }

                Log::info("File đã được lưu vào backup: {$savedFilePath}");

                // Tạo bản copy để xử lý import
                $fullPath = $tempDir . DIRECTORY_SEPARATOR . time() . '_' . $savedFilename;
                copy($savedFilePath, $fullPath);
            } else {
                // Không lưu backup, chỉ lưu vào temp để import
                $uniqueFilename = time() . '_' . $filename;
                $fullPath = $tempDir . DIRECTORY_SEPARATOR . $uniqueFilename;
                $file->move($tempDir, $uniqueFilename);
                
                if (!file_exists($fullPath)) {
                    Log::error("Không thể lưu file vào temp: {$fullPath}");
                    throw new \Exception('Không thể lưu file upload');
                }
            }

            // Kiểm tra và giải mã nếu file bị mã hóa
            if (\App\Services\DatabaseEncryptionService::isEncrypted($fullPath)) {
                Log::info("File bị mã hóa, đang giải mã...");
                try {
                    $decryptedPath = $fullPath . '.decrypted';
                    \App\Services\DatabaseEncryptionService::decrypt($fullPath, $decryptedPath);
                    
                    // Xóa file encrypted, dùng file decrypted
                    unlink($fullPath);
                    rename($decryptedPath, $fullPath);
                    
                    Log::info("File đã được giải mã thành công");
                } catch (\Exception $e) {
                    Log::error("Lỗi giải mã file: " . $e->getMessage());
                    throw new \Exception('File bị mã hóa nhưng không thể giải mã. Key có thể không đúng.');
                }
            }

            // Giải nén nếu là .gz
            if (str_ends_with(strtolower($filename), '.gz')) {
                Log::info("Giải nén file .gz");
                $unzippedPath = str_replace('.gz', '', $fullPath);

                // Sử dụng 7zip trên Windows hoặc gunzip trên Linux
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    $command = "7z e -so \"{$fullPath}\" > \"{$unzippedPath}\"";
                } else {
                    $command = "gunzip -c \"{$fullPath}\" > \"{$unzippedPath}\"";
                }

                exec($command, $output, $returnCode);

                if ($returnCode !== 0 || !file_exists($unzippedPath)) {
                    throw new \Exception('Lỗi khi giải nén file. Vui lòng thử file .sql thông thường.');
                }

                $fullPath = $unzippedPath;
            }

            // Kiểm tra file SQL có hợp lệ không
            $fileContent = file_get_contents($fullPath, false, null, 0, 10240);
            $isValidSQL = (
                stripos($fileContent, '-- MySQL dump') !== false ||
                stripos($fileContent, 'CREATE') !== false ||
                stripos($fileContent, 'INSERT') !== false ||
                stripos($fileContent, 'DROP') !== false ||
                stripos($fileContent, 'USE ') !== false
            );

            if (!$isValidSQL) {
                Log::error("File không phải SQL hợp lệ. Nội dung 500 ký tự đầu: " . substr($fileContent, 0, 500));
                throw new \Exception('File SQL không hợp lệ. Vui lòng kiểm tra file có đúng định dạng SQL không.');
            }

            Log::info("File SQL hợp lệ, bắt đầu import");

            // Import vào database hiện tại
            $dbName = config('database.connections.mysql.database');
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port', '3306');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            
            Log::info("Import SQL vào database hiện tại: {$dbName} @ {$host}");

            $command = sprintf(
                'mysql --host=%s --port=%s --user=%s %s %s < "%s" 2>&1',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                $password ? '--password=' . escapeshellarg($password) : '',
                escapeshellarg($dbName),
                $fullPath
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                $errorMsg = implode("\n", $output);
                Log::error("Lỗi import SQL: {$errorMsg}");
                throw new \Exception('Lỗi khi import database. Vui lòng kiểm tra file SQL.');
            }

            Log::info("Import database thành công - Dữ liệu đã được ghi đè");

            // Chạy migrations để đảm bảo schema mới nhất
            try {
                Artisan::call('migrate', ['--force' => true]);
                Log::info("Đã chạy migrations sau import");
            } catch (\Exception $e) {
                Log::warning("Không thể chạy migrations: " . $e->getMessage());
            }

            // Copy ảnh từ thư mục temp vào storage (nếu có)
            $imagesCopied = 0;
            if ($hasImages && $tempSessionId) {
                $tempImagesDir = storage_path('app/temp/images_' . $tempSessionId);
                $targetDir = storage_path('app/public');
                
                if (is_dir($tempImagesDir)) {
                    Log::info("Bắt đầu copy ảnh từ temp: {$tempImagesDir}");
                    $imagesCopied = $this->copyDirectoryRecursive($tempImagesDir, $targetDir);
                    Log::info("Đã copy {$imagesCopied} ảnh vào storage");
                    
                    // Xóa thư mục temp
                    $this->deleteDirectory($tempImagesDir);
                    Log::info("Đã xóa thư mục temp: {$tempImagesDir}");
                } else {
                    Log::warning("Thư mục temp không tồn tại: {$tempImagesDir}");
                }
            }

            // Ghi nhận import vào database (CHỈ KHI import SQL thuần, không kèm ảnh riêng)
            $importRecordId = null;
            if ($shouldSaveToBackup && $savedFilePath) {
                try {
                    $currentYear = YearDatabase::getCurrentYear();
                    $fileSize = file_exists($savedFilePath) ? filesize($savedFilePath) : $originalFileSize;
                    
                    $importRecord = DatabaseExport::create([
                        'year' => $currentYear->year ?? date('Y'),
                        'filename' => $savedFilename,
                        'file_path' => "backups/databases/{$savedFilename}",
                        'file_size' => $fileSize,
                        'status' => 'completed',
                        'type' => 'import',
                        'description' => "Import từ file {$filename}",
                        'exported_by' => Auth::id(),
                        'exported_at' => now(),
                        'is_encrypted' => false,
                        'includes_images' => false,
                    ]);
                    $importRecordId = $importRecord->id;
                    Log::info("Đã ghi nhận import SQL: {$savedFilename}");
                } catch (\Exception $e) {
                    Log::error("Lỗi ghi nhận import: " . $e->getMessage());
                }
            }

            // Log activity
            $this->activityLogger->log(
                \App\Models\ActivityLog::TYPE_CREATE,
                \App\Models\ActivityLog::MODULE_YEAR_DATABASE,
                null,
                [
                    'year' => $year,
                    'filename' => $filename,
                    'has_images' => $hasImages,
                    'images_copied' => $imagesCopied,
                    'saved_to_backup' => $shouldSaveToBackup,
                ],
                "Import database năm {$year}" . ($hasImages ? " (kèm {$imagesCopied} ảnh)" : '')
            );

            return response()->json([
                'success' => true,
                'message' => "Import database thành công. Dữ liệu đã được khôi phục.",
                'import_record_id' => $importRecordId,
                'new_csrf_token' => csrf_token(), // Trả về CSRF token mới
            ]);
        } catch (\Exception $e) {
            Log::error("Lỗi import database: " . $e->getMessage());

            // Xóa file backup nếu import thất bại
            if ($shouldSaveToBackup && isset($savedFilePath) && file_exists($savedFilePath)) {
                @unlink($savedFilePath);
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        } finally {
            // Cleanup: Xóa file tạm
            if (isset($fullPath) && file_exists($fullPath)) {
                @unlink($fullPath);
                Log::info("Đã xóa file tạm: {$fullPath}");
            }
            if (isset($unzippedPath) && file_exists($unzippedPath) && $unzippedPath !== $fullPath) {
                @unlink($unzippedPath);
                Log::info("Đã xóa file giải nén: {$unzippedPath}");
            }
        }
    }

    /**
     * Export database với ảnh (ZIP)
     */
    public function exportWithImages(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'description' => 'nullable|string|max:500',
        ]);

        $year = $request->year;

        try {
            // Gọi command export
            $exitCode = Artisan::call('year:export', [
                'year' => $year,
                '--include-images' => true,
            ]);

            if ($exitCode !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi khi export database',
                ], 500);
            }

            // Lấy file export mới nhất
            $export = DatabaseExport::where('year', $year)
                ->orderBy('id', 'desc')
                ->first();

            // Log activity
            if ($export) {
                $this->activityLogger->log(
                    \App\Models\ActivityLog::TYPE_CREATE,
                    \App\Models\ActivityLog::MODULE_YEAR_DATABASE,
                    $export,
                    [
                        'year' => $year,
                        'includes_images' => true,
                        'file_size' => $export->file_size,
                    ],
                    "Export database năm {$year} (kèm ảnh)"
                );
            }

            return response()->json([
                'success' => true,
                'message' => "Export database năm {$year} với ảnh thành công",
                'export' => $export ? [
                    'id' => $export->id,
                    'filename' => $export->filename,
                    'file_size' => $export->file_size_formatted,
                ] : null,
            ]);
        } catch (\Exception $e) {
            Log::error("Lỗi export với ảnh: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy thống kê dữ liệu của năm (để hiển thị trước khi cleanup)
     */
    public function getYearStats($year)
    {
        $stats = [
            'sales' => \App\Models\Sale::where('year', $year)->count(),
            'debts' => \App\Models\Debt::where('year', $year)->count(),
            'payments' => \App\Models\Payment::where('year', $year)->count(),
            'returns' => \App\Models\ReturnModel::where('year', $year)->count(),
            'inventory_transactions' => \App\Models\InventoryTransaction::where('year', $year)->count(),
        ];

        // Thống kê ảnh
        $paintingIds = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.year', $year)
            ->whereNotNull('sale_items.painting_id')
            ->pluck('sale_items.painting_id')
            ->unique();

        $soldPaintings = \App\Models\Painting::whereIn('id', $paintingIds)
            ->where('quantity', 0)
            ->whereNotNull('image')
            ->count();

        $supplyIds = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.year', $year)
            ->whereNotNull('sale_items.supply_id')
            ->pluck('sale_items.supply_id')
            ->unique();

        $soldSupplies = \App\Models\Supply::whereIn('id', $supplyIds)
            ->where('quantity', 0)
            ->whereNotNull('image')
            ->count();

        $stats['images_to_delete'] = $soldPaintings + $soldSupplies;

        return response()->json([
            'success' => true,
            'year' => $year,
            'stats' => $stats,
        ]);
    }

    /**
     * Cleanup dữ liệu năm cũ
     */
    public function cleanupYear(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'keep_images' => 'nullable|boolean',
        ]);

        $year = $request->year;
        $keepImages = $request->input('keep_images', false);

        // Kiểm tra không được xóa năm hiện tại
        $currentYear = YearDatabase::getCurrentYear();
        if ($currentYear && $currentYear->year == $year) {
            return response()->json([
                'success' => false,
                'message' => "Không thể xóa dữ liệu năm hiện tại ({$year})!",
            ], 400);
        }

        try {
            // Gọi command cleanup
            $exitCode = Artisan::call('year:cleanup', [
                'year' => $year,
                '--force' => true,
                '--keep-images' => $keepImages,
            ]);

            if ($exitCode !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi khi xóa dữ liệu',
                ], 500);
            }

            // Log activity
            $this->activityLogger->log(
                \App\Models\ActivityLog::TYPE_DELETE,
                \App\Models\ActivityLog::MODULE_YEAR_DATABASE,
                null,
                [
                    'year' => $year,
                    'keep_images' => $keepImages,
                ],
                "Dọn dẹp dữ liệu năm {$year}" . ($keepImages ? ' (giữ ảnh)' : '')
            );

            return response()->json([
                'success' => true,
                'message' => "Đã xóa dữ liệu năm {$year} thành công. Tồn kho đầu kỳ được giữ lại.",
            ]);
        } catch (\Exception $e) {
            Log::error("Lỗi cleanup năm: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Chuẩn bị năm mới
     */
    public function prepareNewYear(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
        ]);

        $year = $request->year;

        try {
            // Gọi command prepare
            $exitCode = Artisan::call('year:prepare', [
                'year' => $year,
                '--force' => true,
            ]);

            if ($exitCode !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi khi chuẩn bị năm mới',
                ], 500);
            }

            // Log activity
            $this->activityLogger->log(
                \App\Models\ActivityLog::TYPE_CREATE,
                \App\Models\ActivityLog::MODULE_YEAR_DATABASE,
                null,
                ['year' => $year],
                "Chuẩn bị năm mới {$year}"
            );

            return response()->json([
                'success' => true,
                'message' => "Đã chuẩn bị năm {$year} thành công. Năm {$year} đã được set làm năm hiện tại.",
            ]);
        } catch (\Exception $e) {
            Log::error("Lỗi prepare năm mới: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: Thêm folder vào ZIP
     */
    private function addFolderToZip(\ZipArchive $zip, $folder, $zipFolder)
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $zipFolder . '/' . substr($filePath, strlen($folder) + 1);
                // Chuẩn hóa path separator cho ZIP
                $relativePath = str_replace('\\', '/', $relativePath);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    /**
     * Helper: Copy thư mục đệ quy
     */
    private function copyDirectoryRecursive($source, $dest)
    {
        $copied = 0;
        
        if (!is_dir($source)) {
            return 0;
        }

        $sourceLen = strlen($source);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            // Lấy đường dẫn tương đối bằng cách cắt bỏ phần source
            $subPath = substr($item->getRealPath(), $sourceLen + 1);
            $targetPath = $dest . DIRECTORY_SEPARATOR . $subPath;
            
            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                copy($item->getRealPath(), $targetPath);
                $copied++;
            }
        }

        return $copied;
    }

    /**
     * Helper: Xóa thư mục đệ quy
     */
    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($dir);
    }

    /**
     * Import database từ file ZIP (có hình ảnh)
     */
    public function importWithImages(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'file' => 'required|file|max:1024000', // Max 1GB cho ZIP
        ]);

        $year = $request->year;
        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());

        // Validate file extension
        if (!in_array($extension, ['zip', 'sql', 'gz'])) {
            return response()->json([
                'success' => false,
                'message' => 'File không hợp lệ. Chỉ chấp nhận file .zip, .sql hoặc .sql.gz',
            ], 400);
        }

        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $uniqueFilename = time() . '_' . $filename;
        $fullPath = $tempDir . DIRECTORY_SEPARATOR . $uniqueFilename;

        try {
            Log::info("Bắt đầu import từ file {$filename}");

            if (!$file->move($tempDir, $uniqueFilename)) {
                throw new \Exception('Không thể lưu file upload');
            }

            // Nếu là file ZIP → Giải nén và xử lý
            if ($extension === 'zip') {
                $extractDir = $tempDir . DIRECTORY_SEPARATOR . 'extract_' . time();
                mkdir($extractDir, 0755, true);

                $zip = new \ZipArchive();
                if ($zip->open($fullPath) !== true) {
                    throw new \Exception('Không thể mở file ZIP');
                }

                $zip->extractTo($extractDir);
                $zip->close();

                // Tìm file SQL trong ZIP
                $sqlFile = null;
                if (file_exists($extractDir . '/database.sql')) {
                    $sqlFile = $extractDir . '/database.sql';
                } else {
                    // Tìm file .sql đầu tiên
                    $sqlFiles = glob($extractDir . '/*.sql');
                    if (!empty($sqlFiles)) {
                        $sqlFile = $sqlFiles[0];
                    }
                }

                if (!$sqlFile) {
                    throw new \Exception('Không tìm thấy file SQL trong ZIP');
                }

                // Import SQL
                $this->importSqlFile($sqlFile);

                // Copy thư mục storage nếu có
                $storageDir = $extractDir . '/storage';
                if (is_dir($storageDir)) {
                    $targetDir = storage_path('app/public');
                    $this->copyDirectory($storageDir, $targetDir);
                    Log::info("Đã copy thư mục hình ảnh");
                }

                // Cleanup
                $this->deleteDirectory($extractDir);
                unlink($fullPath);

                // Log activity
                $this->activityLogger->log(
                    \App\Models\ActivityLog::TYPE_CREATE,
                    \App\Models\ActivityLog::MODULE_YEAR_DATABASE,
                    null,
                    [
                        'year' => $year,
                        'filename' => $filename,
                        'type' => 'zip_with_images',
                    ],
                    "Import database năm {$year} từ ZIP (kèm ảnh)"
                );

                return response()->json([
                    'success' => true,
                    'message' => "Import thành công! Database và hình ảnh đã được khôi phục.",
                ]);
            } else {
                // File SQL thông thường
                // Kiểm tra và giải mã nếu cần
                if (\App\Services\DatabaseEncryptionService::isEncrypted($fullPath)) {
                    $decryptedPath = $fullPath . '.decrypted';
                    \App\Services\DatabaseEncryptionService::decrypt($fullPath, $decryptedPath);
                    unlink($fullPath);
                    rename($decryptedPath, $fullPath);
                }

                // Giải nén nếu là .gz
                if (str_ends_with(strtolower($filename), '.gz')) {
                    $unzippedPath = str_replace('.gz', '', $fullPath);
                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        exec("7z e -so \"{$fullPath}\" > \"{$unzippedPath}\"", $output, $returnCode);
                    } else {
                        exec("gunzip -c \"{$fullPath}\" > \"{$unzippedPath}\"", $output, $returnCode);
                    }
                    unlink($fullPath);
                    $fullPath = $unzippedPath;
                }

                $this->importSqlFile($fullPath);
                unlink($fullPath);

                // Log activity
                $this->activityLogger->log(
                    \App\Models\ActivityLog::TYPE_CREATE,
                    \App\Models\ActivityLog::MODULE_YEAR_DATABASE,
                    null,
                    [
                        'year' => $year,
                        'filename' => $filename,
                        'type' => 'sql_only',
                    ],
                    "Import database năm {$year} từ file SQL"
                );

                return response()->json([
                    'success' => true,
                    'message' => "Import database thành công!",
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Lỗi import: " . $e->getMessage());
            
            // Cleanup
            if (isset($fullPath) && file_exists($fullPath)) {
                @unlink($fullPath);
            }
            if (isset($extractDir) && is_dir($extractDir)) {
                $this->deleteDirectory($extractDir);
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: Import file SQL vào database
     */
    private function importSqlFile($sqlPath)
    {
        $fileContent = file_get_contents($sqlPath, false, null, 0, 10240);
        $isValidSQL = (
            stripos($fileContent, '-- MySQL dump') !== false ||
            stripos($fileContent, 'CREATE') !== false ||
            stripos($fileContent, 'INSERT') !== false ||
            stripos($fileContent, 'DROP') !== false
        );

        if (!$isValidSQL) {
            throw new \Exception('File SQL không hợp lệ');
        }

        $dbName = config('database.connections.mysql.database');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', '3306');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $command = sprintf(
            'mysql --host=%s --port=%s --user=%s %s %s < "%s" 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $password ? '--password=' . escapeshellarg($password) : '',
            escapeshellarg($dbName),
            $sqlPath
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Lỗi khi import database: ' . implode("\n", $output));
        }

        Log::info("Import SQL thành công");
    }

    /**
     * Helper: Copy directory
     */
    private function copyDirectory($source, $dest)
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $targetPath = $dest . DIRECTORY_SEPARATOR . substr($file->getRealPath(), strlen($source) + 1);
            
            if ($file->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                copy($file->getRealPath(), $targetPath);
            }
        }
    }

    /**
     * Upload hình ảnh theo batch (để tránh timeout)
     * Mỗi request upload tối đa 10 file
     */
    public function uploadImagesBatch(Request $request)
    {
        // Log request để debug
        Log::info("uploadImagesBatch: ========== REQUEST RECEIVED ==========");
        
        try {
            $request->validate([
                'images' => 'required|array|max:20',
                'images.*' => 'file|max:10240', // Max 10MB mỗi file
                'paths' => 'required|array',
                'paths.*' => 'string',
                'session_id' => 'nullable|string',
                'save_to_temp' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("uploadImagesBatch: Validation failed: " . json_encode($e->errors()));
            throw $e;
        }

        $uploaded = 0;
        $failed = 0;
        $errors = [];

        $images = $request->file('images');
        $paths = $request->input('paths');
        $sessionId = $request->input('session_id');
        $saveToTemp = $request->input('save_to_temp') === '1';
        
        Log::info("uploadImagesBatch: Nhận " . count($images) . " files, saveToTemp=" . ($saveToTemp ? 'true' : 'false') . ", sessionId=" . $sessionId);

        // Xác định thư mục đích
        if ($saveToTemp && $sessionId) {
            $baseDir = storage_path('app/temp/images_' . $sessionId);
        } else {
            $baseDir = storage_path('app/public');
        }

        foreach ($images as $index => $image) {
            try {
                $originalPath = $paths[$index] ?? null;
                if (!$originalPath) {
                    $failed++;
                    continue;
                }

                // Đảm bảo path an toàn (không cho phép ../)
                $targetPath = str_replace(['../', '..\\'], '', $originalPath);
                
                // Chuẩn hóa path separator
                $targetPath = str_replace('\\', '/', $targetPath);
                
                // Xử lý các trường hợp path khác nhau:
                // 1. "storage/paintings/xxx.jpg" -> "paintings/xxx.jpg"
                // 2. "folder_name/storage/paintings/xxx.jpg" -> "paintings/xxx.jpg"
                // 3. "paintings/xxx.jpg" -> "paintings/xxx.jpg" (giữ nguyên)
                // 4. "folder_name/paintings/xxx.jpg" -> "paintings/xxx.jpg" (thư mục gốc bất kỳ)
                
                // Tìm vị trí "storage/" trong path
                $storagePos = strpos($targetPath, 'storage/');
                if ($storagePos !== false) {
                    $targetPath = substr($targetPath, $storagePos + 8); // Bỏ phần trước và "storage/"
                } else {
                    // Nếu không có "storage/", tìm các thư mục con hợp lệ (paintings, avatars, showrooms, supplies)
                    $validFolders = ['paintings', 'avatars', 'showrooms', 'supplies'];
                    foreach ($validFolders as $folder) {
                        $folderPos = strpos($targetPath, $folder . '/');
                        if ($folderPos !== false) {
                            $targetPath = substr($targetPath, $folderPos); // Bắt đầu từ thư mục hợp lệ
                            break;
                        }
                    }
                    
                    // Nếu vẫn không tìm thấy, bỏ thư mục gốc đầu tiên (folder được chọn)
                    // Ví dụ: "my_images/paintings/xxx.jpg" -> "paintings/xxx.jpg"
                    $parts = explode('/', $targetPath);
                    if (count($parts) > 1) {
                        // Kiểm tra xem phần tử đầu tiên có phải là thư mục con hợp lệ không
                        if (!in_array($parts[0], $validFolders)) {
                            array_shift($parts); // Bỏ thư mục gốc
                            $targetPath = implode('/', $parts);
                        }
                    }
                }
                
                Log::info("uploadImagesBatch: {$originalPath} -> {$targetPath}");
                
                // Tạo đường dẫn đầy đủ
                $fullPath = $baseDir . '/' . $targetPath;
                $dir = dirname($fullPath);
                
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                // Move file
                $image->move($dir, basename($fullPath));
                $uploaded++;
                
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "File {$index}: " . $e->getMessage();
                Log::error("Upload image error [{$index}]: " . $e->getMessage());
            }
        }

        Log::info("uploadImagesBatch: uploaded={$uploaded}, failed={$failed}");

        return response()->json([
            'success' => true,
            'uploaded' => $uploaded,
            'failed' => $failed,
            'errors' => $errors,
        ]);
    }

    /**
     * Lấy danh sách file trong thư mục storage (để client biết cần upload những gì)
     */
    public function getStorageFileList()
    {
        $storageDir = storage_path('app/public');
        $files = [];

        if (is_dir($storageDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($storageDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if (!$file->isDir()) {
                    $relativePath = str_replace('\\', '/', substr($file->getRealPath(), strlen($storageDir) + 1));
                    $files[] = [
                        'path' => $relativePath,
                        'size' => $file->getSize(),
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'total' => count($files),
            'files' => $files,
        ]);
    }

    /**
     * Giải nén ZIP và trả về danh sách file hình ảnh cần import
     * Client sẽ dùng danh sách này để upload từng batch
     */
    public function prepareImportImages(Request $request)
    {
        // Tăng timeout và memory cho file lớn
        set_time_limit(600); // 10 phút
        ini_set('memory_limit', '512M');
        
        $request->validate([
            'file' => 'required|file|max:2048000', // Max 2GB
        ]);

        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        
        Log::info("prepareImportImages: Bắt đầu xử lý file {$filename}");

        if ($extension !== 'zip') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ chấp nhận file ZIP',
            ], 400);
        }

        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $sessionId = uniqid('import_');
        $extractDir = $tempDir . DIRECTORY_SEPARATOR . $sessionId;

        try {
            Log::info("prepareImportImages: Bắt đầu lưu file ZIP");
            
            // Lưu và giải nén ZIP
            $zipPath = $tempDir . DIRECTORY_SEPARATOR . $sessionId . '.zip';
            $file->move($tempDir, $sessionId . '.zip');
            
            Log::info("prepareImportImages: Đã lưu file ZIP, bắt đầu giải nén");

            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new \Exception('Không thể mở file ZIP');
            }

            mkdir($extractDir, 0755, true);
            $zip->extractTo($extractDir);
            $zip->close();
            
            Log::info("prepareImportImages: Đã giải nén xong");

            // Lưu file ZIP vào thư mục backup (thay vì xóa)
            $backupDir = storage_path('backups/databases');
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            $savedZipFilename = $filename; // Giữ tên gốc
            $savedZipPath = $backupDir . DIRECTORY_SEPARATOR . $savedZipFilename;
            
            // Nếu file đã tồn tại, thêm timestamp
            if (file_exists($savedZipPath)) {
                $savedZipFilename = pathinfo($filename, PATHINFO_FILENAME) . '_' . time() . '.' . pathinfo($filename, PATHINFO_EXTENSION);
                $savedZipPath = $backupDir . DIRECTORY_SEPARATOR . $savedZipFilename;
            }
            
            $zipFileSize = filesize($zipPath);
            rename($zipPath, $savedZipPath);
            Log::info("prepareImportImages: Đã lưu file ZIP vào backup: {$savedZipFilename}");

            // Tìm file SQL
            $sqlFile = null;
            if (file_exists($extractDir . '/database.sql')) {
                $sqlFile = 'database.sql';
            } else {
                $sqlFiles = glob($extractDir . '/*.sql');
                if (!empty($sqlFiles)) {
                    $sqlFile = basename($sqlFiles[0]);
                }
            }
            
            Log::info("prepareImportImages: SQL file = {$sqlFile}");

            // Lấy danh sách file hình ảnh
            $imageFiles = [];
            $storageDir = $extractDir . '/storage';
            
            if (is_dir($storageDir)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($storageDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($iterator as $imgFile) {
                    if (!$imgFile->isDir()) {
                        $relativePath = str_replace('\\', '/', substr($imgFile->getRealPath(), strlen($storageDir) + 1));
                        $imageFiles[] = [
                            'path' => $relativePath,
                            'size' => $imgFile->getSize(),
                            'fullPath' => $imgFile->getRealPath(),
                        ];
                    }
                }
            }
            
            Log::info("prepareImportImages: Tìm thấy " . count($imageFiles) . " file hình ảnh");

            // Lưu session info vào file (thay vì session để tránh mất data giữa các request)
            $sessionData = [
                'extractDir' => $extractDir,
                'sqlFile' => $sqlFile,
                'totalImages' => count($imageFiles),
                'createdAt' => now()->timestamp,
                'savedZipFilename' => $savedZipFilename,
                'savedZipPath' => "backups/databases/{$savedZipFilename}",
                'zipFileSize' => $zipFileSize,
            ];
            $sessionFile = $tempDir . DIRECTORY_SEPARATOR . $sessionId . '.json';
            file_put_contents($sessionFile, json_encode($sessionData));
            
            Log::info("prepareImportImages: Hoàn thành, sessionId = {$sessionId}");

            return response()->json([
                'success' => true,
                'sessionId' => $sessionId,
                'sqlFile' => $sqlFile,
                'totalImages' => count($imageFiles),
            ]);

        } catch (\Exception $e) {
            // Cleanup on error
            if (isset($zipPath) && file_exists($zipPath)) {
                @unlink($zipPath);
            }
            if (isset($extractDir) && is_dir($extractDir)) {
                $this->deleteDirectory($extractDir);
            }

            Log::error("Prepare import error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Helper: Lấy session data từ file
     */
    private function getImportSessionData($sessionId)
    {
        $tempDir = storage_path('app/temp');
        $sessionFile = $tempDir . DIRECTORY_SEPARATOR . $sessionId . '.json';
        
        if (!file_exists($sessionFile)) {
            return null;
        }
        
        return json_decode(file_get_contents($sessionFile), true);
    }

    /**
     * Import SQL từ session đã prepare
     */
    public function importSqlFromSession(Request $request)
    {
        $request->validate([
            'sessionId' => 'required|string',
        ]);

        $sessionId = $request->input('sessionId');
        $sessionData = $this->getImportSessionData($sessionId);

        if (!$sessionData) {
            Log::error("importSqlFromSession: Session không tồn tại - sessionId = {$sessionId}");
            return response()->json([
                'success' => false,
                'message' => 'Session không tồn tại hoặc đã hết hạn',
            ], 400);
        }

        $extractDir = $sessionData['extractDir'];
        $sqlFile = $sessionData['sqlFile'];
        
        Log::info("importSqlFromSession: extractDir = {$extractDir}, sqlFile = {$sqlFile}");

        if (!$sqlFile || !file_exists($extractDir . '/' . $sqlFile)) {
            Log::error("importSqlFromSession: Không tìm thấy file SQL tại {$extractDir}/{$sqlFile}");
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy file SQL',
            ], 400);
        }

        try {
            // Lưu user ID trước khi import (vì import có thể truncate sessions table)
            $currentUserId = Auth::id();
            
            $this->importSqlFile($extractDir . '/' . $sqlFile);
            
            // Chạy migrations để đảm bảo schema đúng (vì file SQL cũ có thể thiếu columns mới)
            try {
                Artisan::call('migrate', ['--force' => true]);
                Log::info("Đã chạy migrations sau import");
            } catch (\Exception $e) {
                Log::warning("Không thể chạy migrations: " . $e->getMessage());
            }
            
            // Re-login user sau khi import (vì sessions table có thể bị truncate)
            if ($currentUserId) {
                $user = \App\Models\User::find($currentUserId);
                if ($user) {
                    Auth::login($user);
                    session()->regenerate(); // Tạo session mới
                    Log::info("Re-login user sau import: {$user->email}");
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Import database thành công!',
                'newCsrfToken' => csrf_token(), // Trả về CSRF token mới
            ]);
        } catch (\Exception $e) {
            Log::error("Import SQL error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Copy hình ảnh theo batch từ session đã prepare
     */
    public function copyImagesBatch(Request $request)
    {
        $request->validate([
            'sessionId' => 'required|string',
            'startIndex' => 'required|integer|min:0',
            'batchSize' => 'required|integer|min:1|max:50',
        ]);

        $sessionId = $request->input('sessionId');
        $startIndex = $request->input('startIndex');
        $batchSize = $request->input('batchSize');

        $sessionData = $this->getImportSessionData($sessionId);

        if (!$sessionData) {
            Log::error("copyImagesBatch: Session không tồn tại - sessionId = {$sessionId}");
            return response()->json([
                'success' => false,
                'message' => 'Session không tồn tại hoặc đã hết hạn',
            ], 400);
        }

        $extractDir = $sessionData['extractDir'];
        $storageDir = $extractDir . '/storage';
        $targetDir = storage_path('app/public');
        
        Log::info("copyImagesBatch: storageDir = {$storageDir}, startIndex = {$startIndex}");

        if (!is_dir($storageDir)) {
            Log::info("copyImagesBatch: Không có thư mục hình ảnh");
            return response()->json([
                'success' => true,
                'copied' => 0,
                'processed' => 0,
                'total' => 0,
                'isComplete' => true,
                'message' => 'Không có thư mục hình ảnh',
            ]);
        }

        // Lấy danh sách file
        $allFiles = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($storageDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                $allFiles[] = $file->getRealPath();
            }
        }

        // Lấy batch cần xử lý
        $batchFiles = array_slice($allFiles, $startIndex, $batchSize);
        $copied = 0;

        foreach ($batchFiles as $filePath) {
            try {
                $relativePath = substr($filePath, strlen($storageDir) + 1);
                $targetPath = $targetDir . DIRECTORY_SEPARATOR . $relativePath;
                $dir = dirname($targetPath);

                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                copy($filePath, $targetPath);
                $copied++;
            } catch (\Exception $e) {
                Log::error("Copy image error: " . $e->getMessage());
            }
        }

        $totalFiles = count($allFiles);
        $processed = $startIndex + $copied;
        $isComplete = $processed >= $totalFiles;
        
        Log::info("copyImagesBatch: copied = {$copied}, processed = {$processed}, total = {$totalFiles}");

        return response()->json([
            'success' => true,
            'copied' => $copied,
            'processed' => $processed,
            'total' => $totalFiles,
            'isComplete' => $isComplete,
            'progress' => $totalFiles > 0 ? round(($processed / $totalFiles) * 100, 1) : 100,
        ]);
    }

    /**
     * Cleanup session sau khi import xong
     */
    public function cleanupImportSession(Request $request)
    {
        $sessionId = $request->input('sessionId');
        $sessionData = $this->getImportSessionData($sessionId);
        
        // Lấy các tham số - xử lý cả boolean và string
        $importSuccess = filter_var($request->input('success', false), FILTER_VALIDATE_BOOLEAN);
        $filename = $request->input('filename', 'unknown.zip');
        $totalImages = (int) $request->input('totalImages', 0);
        
        Log::info("cleanupImportSession: sessionId={$sessionId}, success={$importSuccess}, filename={$filename}, totalImages={$totalImages}");

        // Ghi nhận import thành công vào database
        if ($importSuccess) {
            try {
                $currentYear = YearDatabase::getCurrentYear();
                
                // Lấy thông tin file từ session data
                $savedFilename = $sessionData['savedZipFilename'] ?? $filename;
                $savedFilePath = $sessionData['savedZipPath'] ?? '';
                $zipFileSize = $sessionData['zipFileSize'] ?? 0;
                $sessionTotalImages = $sessionData['totalImages'] ?? $totalImages;
                
                DatabaseExport::create([
                    'year' => $currentYear->year ?? date('Y'),
                    'filename' => $savedFilename,
                    'file_path' => $savedFilePath,
                    'file_size' => $zipFileSize,
                    'status' => 'completed',
                    'type' => 'import',
                    'description' => "Import từ file {$filename}" . ($sessionTotalImages > 0 ? " (kèm {$sessionTotalImages} hình ảnh)" : ''),
                    'exported_by' => Auth::id(),
                    'exported_at' => now(),
                    'is_encrypted' => false,
                    'includes_images' => $sessionTotalImages > 0,
                ]);
                Log::info("Đã ghi nhận import: {$savedFilename}, path: {$savedFilePath}");
            } catch (\Exception $e) {
                Log::error("Lỗi ghi nhận import: " . $e->getMessage());
            }
        }

        if ($sessionData && isset($sessionData['extractDir'])) {
            $this->deleteDirectory($sessionData['extractDir']);
        }

        // Xóa file session
        $tempDir = storage_path('app/temp');
        $sessionFile = $tempDir . DIRECTORY_SEPARATOR . $sessionId . '.json';
        if (file_exists($sessionFile)) {
            @unlink($sessionFile);
        }

        Log::info("cleanupImportSession: Đã dọn dẹp session {$sessionId}");

        return response()->json([
            'success' => true,
            'message' => 'Đã dọn dẹp session',
        ]);
    }
}
