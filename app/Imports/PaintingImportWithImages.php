<?php

namespace App\Imports;

use App\Models\Painting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;

class PaintingImportWithImages implements ToCollection
{
    protected $importedCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];
    protected $excelImages = [];
    protected $uploadedImages = [];

    public function __construct(array $uploadedImages = [], array $excelImages = [])
    {
        $this->uploadedImages = $uploadedImages;
        $this->excelImages = $excelImages;
    }

    public function collection(Collection $rows)
    {
        Log::info('Processing Excel rows', ['total_rows' => count($rows)]);
        
        foreach ($rows as $index => $row) {
            // Index 0 is Header (Row 1 in Excel)
            // Index 1 is Data (Row 2 in Excel)
            $excelRow = $index + 1;

            // Skip header
            if ($index === 0) continue;

            try {
                // Validate có đủ cột không
                if (count($row) < 4) {
                    Log::warning("Row {$excelRow} has insufficient columns", ['columns' => count($row)]);
                    $this->skippedCount++;
                    $this->errors[] = "Dòng {$excelRow}: Không đủ cột dữ liệu (cần ít nhất 4 cột)";
                    continue;
                }
                
                $code = isset($row[0]) ? trim((string)$row[0]) : null;
                // Clean code: remove extra spaces
                $code = preg_replace('/\s+/', ' ', $code);
                $code = trim($code);
                
                if (empty($code)) {
                    continue;
                }

                // Check if exists
                if (Painting::where('code', $code)->exists()) {
                    $this->skippedCount++;
                    $this->errors[] = "Dòng {$excelRow}: Mã tranh '{$code}' đã tồn tại";
                    continue;
                }
                
                // Validate required fields and clean data
                $name = isset($row[1]) ? trim((string)$row[1]) : '';
                $artist = isset($row[2]) ? trim((string)$row[2]) : '';
                $material = isset($row[3]) ? trim((string)$row[3]) : '';
                
                // Clean duplicate text (e.g., "Tranh mẫu 1Tranh mẫu 1" -> "Tranh mẫu 1")
                $name = $this->cleanDuplicateText($name);
                $artist = $this->cleanDuplicateText($artist);
                $material = $this->cleanDuplicateText($material);
                
                // Validate and truncate if too long
                if (empty($name)) {
                    $this->skippedCount++;
                    $this->errors[] = "Dòng {$excelRow}: Thiếu tên tranh";
                    continue;
                }
                
                if (mb_strlen($name) > 500) {
                    $this->skippedCount++;
                    $this->errors[] = "Dòng {$excelRow}: Tên tranh quá dài (tối đa 500 ký tự, hiện tại: " . mb_strlen($name) . " ký tự)";
                    continue;
                }
                
                if (empty($artist)) {
                    $this->skippedCount++;
                    $this->errors[] = "Dòng {$excelRow}: Thiếu tên họa sĩ";
                    continue;
                }
                
                if (mb_strlen($artist) > 255) {
                    $artist = mb_substr($artist, 0, 255);
                    Log::warning("Artist name truncated", ['row' => $excelRow, 'original_length' => mb_strlen($artist)]);
                }
                
                if (empty($material)) {
                    $this->skippedCount++;
                    $this->errors[] = "Dòng {$excelRow}: Thiếu chất liệu";
                    continue;
                }
                
                if (mb_strlen($material) > 100) {
                    $material = mb_substr($material, 0, 100);
                }

                // Get image for this row - Priority order:
                // 1. Uploaded images (by code - exact match or starts with)
                // 2. Embedded images (by row)
                // 3. Image path from Excel column (index 11)
                $imagePath = null;
                
                // Normalize code for comparison (remove extra spaces)
                $normalizedCode = $this->normalizeWhitespace($code);
                
                // Try exact match first
                if (isset($this->uploadedImages[$code])) {
                    $imagePath = $this->uploadedImages[$code];
                    Log::info('Found image by exact match', ['code' => $code, 'path' => $imagePath]);
                }
                // Try exact match with normalized code
                elseif (isset($this->uploadedImages[$normalizedCode])) {
                    $imagePath = $this->uploadedImages[$normalizedCode];
                    Log::info('Found image by normalized exact match', ['code' => $code, 'normalized' => $normalizedCode, 'path' => $imagePath]);
                }
                // Try prefix match (e.g., "DMH 470" matches "DMH 470_80x80cm_1700$_resize")
                else {
                    foreach ($this->uploadedImages as $filename => $path) {
                        // Normalize filename for comparison
                        $normalizedFilename = $this->normalizeWhitespace($filename);
                        
                        // Check if normalized filename starts with normalized code
                        if (stripos($normalizedFilename, $normalizedCode) === 0) {
                            $imagePath = $path;
                            Log::info('Found image by normalized prefix match', [
                                'code' => $code,
                                'normalized_code' => $normalizedCode,
                                'filename' => $filename,
                                'normalized_filename' => $normalizedFilename,
                                'path' => $imagePath
                            ]);
                            break;
                        }
                    }
                }
                
                // If still no image, try embedded images
                if (!$imagePath && isset($this->excelImages[$excelRow])) {
                    $imagePath = $this->excelImages[$excelRow];
                    Log::info('Using embedded image', ['row' => $excelRow, 'path' => $imagePath]);
                }
                
                // If still no image, try path from Excel
                if (!$imagePath && isset($row[11]) && !empty(trim((string)$row[11]))) {
                    // Try to copy image from the path specified in Excel
                    $imagePath = $this->copyImageFromPath(trim((string)$row[11]), $code);
                }
                
                // Parse numeric fields safely
                $width = null;
                if (isset($row[4]) && !empty($row[4])) {
                    $width = is_numeric($row[4]) ? (float)$row[4] : null;
                }
                
                $height = null;
                if (isset($row[5]) && !empty($row[5])) {
                    $height = is_numeric($row[5]) ? (float)$row[5] : null;
                }
                
                $priceUsd = 0;
                if (isset($row[7]) && !empty($row[7])) {
                    $priceUsd = is_numeric($row[7]) ? (float)$row[7] : 0;
                }
                
                $priceVnd = null;
                if (isset($row[8]) && !empty($row[8])) {
                    $priceVnd = is_numeric($row[8]) ? (float)$row[8] : null;
                }
                
                // Clean notes
                $notes = isset($row[12]) ? trim((string)$row[12]) : null;
                if ($notes && mb_strlen($notes) > 1000) {
                    $notes = mb_substr($notes, 0, 1000);
                }

                // Create painting
                $painting = Painting::create([
                    'code' => $code,
                    'name' => $name,
                    'artist' => $artist,
                    'material' => $material,
                    'width' => $width,
                    'height' => $height,
                    'paint_year' => $row[6] ?? null,
                    'price_usd' => $priceUsd,
                    'price_vnd' => $priceVnd,
                    'image' => $imagePath,
                    'quantity' => 1,
                    'import_date' => !empty($row[9]) ? $this->parseDate($row[9]) : now(),
                    'export_date' => !empty($row[10]) ? $this->parseDate($row[10]) : null,
                    'notes' => $notes,
                    'status' => 'in_stock',
                ]);

                $this->importedCount++;
                Log::info("Successfully imported painting", [
                    'row' => $excelRow,
                    'code' => $code,
                    'name' => $name
                ]);

            } catch (\Illuminate\Database\QueryException $e) {
                $this->skippedCount++;
                // User-friendly error message without SQL details
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    $this->errors[] = "Dòng {$excelRow}: Mã tranh đã tồn tại trong hệ thống";
                } elseif (str_contains($e->getMessage(), 'Data too long')) {
                    $this->errors[] = "Dòng {$excelRow}: Dữ liệu quá dài (vui lòng kiểm tra lại tên tranh, họa sĩ)";
                } else {
                    $this->errors[] = "Dòng {$excelRow}: Lỗi cơ sở dữ liệu - vui lòng kiểm tra lại dữ liệu";
                }
                Log::error('Database error importing row', [
                    'row' => $excelRow,
                    'error' => $e->getMessage(),
                    'code' => $code ?? 'unknown'
                ]);
            } catch (\Exception $e) {
                $this->skippedCount++;
                $this->errors[] = "Dòng {$excelRow}: " . $e->getMessage();
                Log::error('Error importing row', [
                    'row' => $excelRow,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        Log::info('Finished processing Excel rows', [
            'imported' => $this->importedCount,
            'skipped' => $this->skippedCount,
            'errors' => count($this->errors)
        ]);
    }

    /**
     * Normalize whitespace in a string
     * Converts multiple spaces to single space and trims
     * 
     * @param string $text
     * @return string
     */
    protected function normalizeWhitespace($text)
    {
        if (empty($text)) {
            return $text;
        }
        
        // Replace multiple spaces with single space
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Trim leading/trailing spaces
        return trim($text);
    }

    /**
     * Clean duplicate text patterns
     * Example: "Tranh mẫu 1Tranh mẫu 1Tranh mẫu 1" -> "Tranh mẫu 1"
     * 
     * @param string $text
     * @return string
     */
    protected function cleanDuplicateText($text)
    {
        if (empty($text)) {
            return $text;
        }
        
        $text = trim($text);
        $originalLength = mb_strlen($text);
        
        // Try to detect repeating patterns
        // Check if text is repeated 2+ times
        for ($len = 1; $len <= mb_strlen($text) / 2; $len++) {
            $pattern = mb_substr($text, 0, $len);
            $repeated = str_repeat($pattern, (int)(mb_strlen($text) / $len));
            
            if ($repeated === $text) {
                Log::info('Detected repeated pattern', [
                    'original' => $text,
                    'pattern' => $pattern,
                    'times' => (int)(mb_strlen($text) / $len)
                ]);
                return $pattern;
            }
        }
        
        return $text;
    }

    /**
     * Copy image from file path to storage
     * Supports multiple path formats:
     * 1. Absolute path: C:\path\to\image.jpg or /path/to/image.jpg
     * 2. Relative to public/temp-imports: image.jpg or subfolder/image.jpg
     * 3. Just filename: image.jpg (looks in public/temp-imports)
     * 
     * @param string $imagePath Path to the image file
     * @param string $code Painting code for naming
     * @return string|null Stored image path or null if failed
     */
    protected function copyImageFromPath($imagePath, $code)
    {
        try {
            // Clean up the path
            $imagePath = trim($imagePath);
            
            // Try to resolve the actual file path
            $resolvedPath = $this->resolveImagePath($imagePath);
            
            if (!$resolvedPath) {
                Log::warning('Image file not found after trying all paths', [
                    'original_path' => $imagePath, 
                    'code' => $code
                ]);
                $this->errors[] = "Không tìm thấy file ảnh: {$imagePath} cho mã {$code}";
                return null;
            }
            
            // Validate it's an image file
            $imageInfo = @getimagesize($resolvedPath);
            if ($imageInfo === false) {
                Log::warning('Invalid image file', ['path' => $resolvedPath, 'code' => $code]);
                $this->errors[] = "File không phải là ảnh hợp lệ: {$imagePath} cho mã {$code}";
                return null;
            }
            
            // Get file extension
            $extension = pathinfo($resolvedPath, PATHINFO_EXTENSION);
            if (empty($extension)) {
                // Determine extension from mime type
                $mimeToExt = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp',
                ];
                $extension = $mimeToExt[$imageInfo['mime']] ?? 'jpg';
            }
            
            // Generate unique filename
            $uniqueName = uniqid() . '_' . time() . '.' . $extension;
            $storagePath = 'paintings/' . $uniqueName;
            
            // Copy file to storage
            $imageContent = file_get_contents($resolvedPath);
            if ($imageContent === false) {
                Log::error('Failed to read image file', ['path' => $resolvedPath, 'code' => $code]);
                $this->errors[] = "Không thể đọc file ảnh: {$imagePath} cho mã {$code}";
                return null;
            }
            
            Storage::disk('public')->put($storagePath, $imageContent);
            
            Log::info('Copied image from path', [
                'original_path' => $imagePath,
                'resolved_path' => $resolvedPath,
                'destination' => $storagePath,
                'code' => $code
            ]);
            
            return $storagePath;
            
        } catch (\Exception $e) {
            Log::error('Error copying image from path', [
                'path' => $imagePath,
                'code' => $code,
                'error' => $e->getMessage()
            ]);
            $this->errors[] = "Lỗi khi copy ảnh {$imagePath} cho mã {$code}: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Resolve image path from various formats
     * 
     * @param string $path Original path from Excel
     * @return string|null Resolved absolute path or null if not found
     */
    protected function resolveImagePath($path)
    {
        // Try 1: Direct absolute path (as-is)
        if (file_exists($path)) {
            return $path;
        }
        
        // Try 2: Relative to public/temp-imports
        $tempImportsPath = public_path('temp-imports/' . $path);
        if (file_exists($tempImportsPath)) {
            return $tempImportsPath;
        }
        
        // Try 3: Just filename in public/temp-imports
        $filename = basename($path);
        $tempImportsFilePath = public_path('temp-imports/' . $filename);
        if (file_exists($tempImportsFilePath)) {
            return $tempImportsFilePath;
        }
        
        // Try 4: Relative to storage/app/public/temp-imports
        $storageTempPath = storage_path('app/public/temp-imports/' . $path);
        if (file_exists($storageTempPath)) {
            return $storageTempPath;
        }
        
        // Try 5: Just filename in storage/app/public/temp-imports
        $storageTempFilePath = storage_path('app/public/temp-imports/' . $filename);
        if (file_exists($storageTempFilePath)) {
            return $storageTempFilePath;
        }
        
        return null;
    }

    protected function parseDate($date)
    {
        try {
            if (empty($date)) {
                return now()->format('Y-m-d');
            }
            
            // If it's a numeric value, it's likely an Excel serial date
            if (is_numeric($date)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('Y-m-d');
            }
            
            // Convert to string and trim
            $date = trim((string)$date);
            
            // Try to parse various date formats
            // Format: dd.mm.yyyy (European format with dots)
            if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $date, $matches)) {
                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $year = $matches[3];
                return "{$year}-{$month}-{$day}";
            }
            
            // Format: dd/mm/yyyy
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $matches)) {
                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $year = $matches[3];
                return "{$year}-{$month}-{$day}";
            }
            
            // Format: dd-mm-yyyy
            if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $date, $matches)) {
                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $year = $matches[3];
                return "{$year}-{$month}-{$day}";
            }
            
            // Try strtotime for other formats (yyyy-mm-dd, etc.)
            $timestamp = strtotime($date);
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }
            
            // If all else fails, return current date
            Log::warning('Could not parse date, using current date', ['date' => $date]);
            return now()->format('Y-m-d');
            
        } catch (\Exception $e) {
            Log::error('Error parsing date', ['date' => $date, 'error' => $e->getMessage()]);
            return now()->format('Y-m-d');
        }
    }

    public function getImportedCount()
    {
        return $this->importedCount;
    }

    public function getSkippedCount()
    {
        return $this->skippedCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
