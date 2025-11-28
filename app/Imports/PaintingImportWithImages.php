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
                
                if (empty($code)) {
                    continue;
                }

                // Check if exists
                if (Painting::where('code', $code)->exists()) {
                    $this->skippedCount++;
                    $this->errors[] = "Dòng {$excelRow}: Mã tranh '{$code}' đã tồn tại";
                    continue;
                }
                
                // Validate required fields
                $name = isset($row[1]) ? trim((string)$row[1]) : '';
                $artist = isset($row[2]) ? trim((string)$row[2]) : '';
                $material = isset($row[3]) ? trim((string)$row[3]) : '';
                
                if (empty($name)) {
                    $this->skippedCount++;
                    $this->errors[] = "Dòng {$excelRow}: Thiếu tên tranh";
                    continue;
                }
                
                if (empty($artist)) {
                    $this->skippedCount++;
                    $this->errors[] = "Dòng {$excelRow}: Thiếu tên họa sĩ";
                    continue;
                }
                
                if (empty($material)) {
                    $this->skippedCount++;
                    $this->errors[] = "Dòng {$excelRow}: Thiếu chất liệu";
                    continue;
                }

                // Get image for this row - Priority order:
                // 1. Uploaded images (by code)
                // 2. Embedded images (by row)
                // 3. Image path from Excel column (index 11)
                $imagePath = null;
                
                if (isset($this->uploadedImages[$code])) {
                    $imagePath = $this->uploadedImages[$code];
                }
                elseif (isset($this->excelImages[$excelRow])) {
                    $imagePath = $this->excelImages[$excelRow];
                }
                elseif (isset($row[11]) && !empty(trim((string)$row[11]))) {
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
                    'notes' => $row[12] ?? null,
                    'status' => 'in_stock',
                ]);

                $this->importedCount++;
                Log::info("Successfully imported painting", [
                    'row' => $excelRow,
                    'code' => $code,
                    'name' => $name
                ]);

            } catch (\Exception $e) {
                $this->skippedCount++;
                $errorMsg = "Dòng {$excelRow}: " . $e->getMessage();
                $this->errors[] = $errorMsg;
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
     * Copy image from file path to storage
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
            
            // Check if file exists
            if (!file_exists($imagePath)) {
                Log::warning('Image file not found', ['path' => $imagePath, 'code' => $code]);
                $this->errors[] = "Không tìm thấy file ảnh: {$imagePath} cho mã {$code}";
                return null;
            }
            
            // Validate it's an image file
            $imageInfo = @getimagesize($imagePath);
            if ($imageInfo === false) {
                Log::warning('Invalid image file', ['path' => $imagePath, 'code' => $code]);
                $this->errors[] = "File không phải là ảnh hợp lệ: {$imagePath} cho mã {$code}";
                return null;
            }
            
            // Get file extension
            $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
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
            $imageContent = file_get_contents($imagePath);
            if ($imageContent === false) {
                Log::error('Failed to read image file', ['path' => $imagePath, 'code' => $code]);
                $this->errors[] = "Không thể đọc file ảnh: {$imagePath} cho mã {$code}";
                return null;
            }
            
            Storage::disk('public')->put($storagePath, $imageContent);
            
            Log::info('Copied image from path', [
                'source' => $imagePath,
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

    protected function parseDate($date)
    {
        try {
            if (is_numeric($date)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('Y-m-d');
            }
            return date('Y-m-d', strtotime($date));
        } catch (\Exception $e) {
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
