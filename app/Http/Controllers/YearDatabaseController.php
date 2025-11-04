<?php

namespace App\Http\Controllers;

use App\Services\YearDatabaseService;
use App\Models\YearDatabase;
use App\Models\DatabaseExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class YearDatabaseController extends Controller
{
    protected $yearService;

    public function __construct(YearDatabaseService $yearService)
    {
        $this->yearService = $yearService;
    }

    /**
     * Hiển thị trang quản lý backup & restore database
     */
    public function index()
    {
        $currentYear = YearDatabase::getCurrentYear();

        // Lấy danh sách exports của năm hiện tại
        $exports = DatabaseExport::getByYear($currentYear->year);
        $exportsCount = $exports->count();

        return view('year-database.simple', compact(
            'currentYear',
            'exports',
            'exportsCount'
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
     * Export database của năm
     */
    public function exportDatabase(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'description' => 'nullable|string|max:500',
            'encrypt' => 'nullable|boolean', // Có mã hóa không
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
                'description' => $request->description,
                'exported_by' => Auth::id(),
                'exported_at' => now(),
            ]);

            // Chạy backup - Dùng config từ database.php (hoạt động trên cả local và server)
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
                escapeshellarg($fullPath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($fullPath)) {
                $export->update(['status' => 'failed']);
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi khi export database',
                ], 500);
            }

            // Mã hóa file nếu được yêu cầu
            $isEncrypted = $request->input('encrypt', false);
            if ($isEncrypted) {
                try {
                    $encryptedPath = \App\Services\DatabaseEncryptionService::encrypt($fullPath);
                    
                    // Xóa file SQL gốc, chỉ giữ file encrypted
                    unlink($fullPath);
                    
                    // Đổi tên file encrypted thành tên gốc
                    rename($encryptedPath, $fullPath);
                    
                    Log::info("File đã được mã hóa: {$fullPath}");
                } catch (\Exception $e) {
                    Log::error("Lỗi mã hóa file: " . $e->getMessage());
                    // Tiếp tục với file không mã hóa
                }
            }

            // Cập nhật kích thước file
            $fileSize = filesize($fullPath);
            $export->update([
                'file_size' => $fileSize,
                'status' => 'completed',
                'is_encrypted' => $isEncrypted,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Export database năm {$year} thành công" . ($isEncrypted ? ' (đã mã hóa)' : ''),
                'export' => [
                    'id' => $export->id,
                    'filename' => $export->filename,
                    'file_size' => $export->file_size_formatted,
                    'exported_at' => $export->exported_at->format('d/m/Y H:i:s'),
                    'is_encrypted' => $isEncrypted,
                ],
            ]);
        } catch (\Exception $e) {
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
     */
    public function importDatabase(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'file' => 'required|file|max:512000', // Max 500MB
        ]);

        $year = $request->year;
        $file = $request->file('file');
        $filename = $file->getClientOriginalName();

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

        try {
            Log::info("Bắt đầu import database năm {$year} từ file {$filename}");

            // Tạo tên file unique để tránh trùng lặp
            $uniqueFilename = time() . '_' . $filename;

            // Tạo thư mục temp nếu chưa có
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Lưu file trực tiếp vào thư mục temp
            $fullPath = $tempDir . DIRECTORY_SEPARATOR . $uniqueFilename;

            if (!$file->move($tempDir, $uniqueFilename)) {
                Log::error("Không thể move file upload đến: {$fullPath}");
                throw new \Exception('Không thể lưu file upload. Kiểm tra quyền thư mục storage/app/temp');
            }

            if (!file_exists($fullPath)) {
                Log::error("File không tồn tại sau khi upload: {$fullPath}");
                throw new \Exception('Không thể lưu file upload');
            }

            Log::info("File đã được lưu tại: {$fullPath}");

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
                    // Windows: sử dụng 7zip nếu có
                    $command = "7z e -so \"{$fullPath}\" > \"{$unzippedPath}\"";
                } else {
                    // Linux/Mac
                    $command = "gunzip -c \"{$fullPath}\" > \"{$unzippedPath}\"";
                }

                exec($command, $output, $returnCode);

                if ($returnCode !== 0 || !file_exists($unzippedPath)) {
                    throw new \Exception('Lỗi khi giải nén file. Vui lòng thử file .sql thông thường.');
                }

                $fullPath = $unzippedPath;
            }

            // Kiểm tra file SQL có hợp lệ không
            // Đọc 10KB đầu để kiểm tra (mysqldump có nhiều comment ở đầu)
            $fileContent = file_get_contents($fullPath, false, null, 0, 10240);

            // Kiểm tra có phải file SQL không (có comment mysqldump hoặc có lệnh SQL)
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

            // Import vào database hiện tại (ghi đè)
            // Dùng config từ database.php (hoạt động trên cả local và server)
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

            return response()->json([
                'success' => true,
                'message' => "Import database thành công. Dữ liệu đã được khôi phục.",
            ]);
        } catch (\Exception $e) {
            Log::error("Lỗi import database: " . $e->getMessage());

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
}
