<?php

namespace App\Imports;

use App\Models\Supply;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Illuminate\Support\Facades\Log;

class SupplyImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError
{
    use SkipsErrors;

    protected $importedCount = 0;
    protected $skippedCount = 0;
    protected $updatedCount = 0;
    protected $errors = [];
    protected $uploadedImages = [];

    public function __construct(array $uploadedImages = [])
    {
        $this->uploadedImages = $uploadedImages;
    }

    public function model(array $row)
    {
        try {
            // Normalize row keys
            $normalizedRow = $this->normalizeRow($row);
            
            // Skip if code is empty
            if (empty($normalizedRow['ma_vat_tu'])) {
                $this->skippedCount++;
                $this->errors[] = "Dòng bị bỏ qua: Thiếu mã vật tư";
                return null;
            }

            $lengthPerTree = !empty($normalizedRow['chieu_dai_moi_cay_cm']) ? (float)$normalizedRow['chieu_dai_moi_cay_cm'] : 0;
            $treeCount = !empty($normalizedRow['so_luong_cay']) ? (int)$normalizedRow['so_luong_cay'] : 1;

            // Check if supply with same code, name, and length exists
            $existingSupply = Supply::where('code', $normalizedRow['ma_vat_tu'])
                ->where('name', $normalizedRow['ten_vat_tu'])
                ->where('quantity', $lengthPerTree)
                ->first();

            if ($existingSupply) {
                // Update tree count
                $existingSupply->tree_count += $treeCount;
                $existingSupply->save();
                $this->updatedCount++;
                return null;
            }

            // Check if code exists with different specs
            if (Supply::where('code', $normalizedRow['ma_vat_tu'])->exists()) {
                $this->skippedCount++;
                $this->errors[] = "Mã vật tư '{$normalizedRow['ma_vat_tu']}' đã tồn tại với thông số khác";
                return null;
            }

            $this->importedCount++;

            // Determine image path - priority order:
            // 1. Uploaded images (by code - exact match or starts with)
            // 2. Image path from Excel column
            $imagePath = null;
            
            // Normalize code for comparison
            $normalizedCode = $this->normalizeWhitespace($normalizedRow['ma_vat_tu']);
            
            // Try exact match first
            if (isset($this->uploadedImages[$normalizedRow['ma_vat_tu']])) {
                $imagePath = $this->uploadedImages[$normalizedRow['ma_vat_tu']];
                Log::info('Found supply image by exact match', [
                    'code' => $normalizedRow['ma_vat_tu'],
                    'path' => $imagePath
                ]);
            }
            // Try exact match with normalized code
            elseif (isset($this->uploadedImages[$normalizedCode])) {
                $imagePath = $this->uploadedImages[$normalizedCode];
                Log::info('Found supply image by normalized exact match', [
                    'code' => $normalizedRow['ma_vat_tu'],
                    'normalized' => $normalizedCode,
                    'path' => $imagePath
                ]);
            }
            // Try prefix match
            else {
                foreach ($this->uploadedImages as $filename => $path) {
                    // Normalize filename for comparison
                    $normalizedFilename = $this->normalizeWhitespace($filename);
                    
                    // Check if normalized filename starts with normalized code
                    if (stripos($normalizedFilename, $normalizedCode) === 0) {
                        $imagePath = $path;
                        Log::info('Found supply image by normalized prefix match', [
                            'code' => $normalizedRow['ma_vat_tu'],
                            'normalized_code' => $normalizedCode,
                            'filename' => $filename,
                            'normalized_filename' => $normalizedFilename,
                            'path' => $imagePath
                        ]);
                        break;
                    }
                }
            }
            
            // If still no image, try path from Excel
            if (!$imagePath && !empty($normalizedRow['duong_dan_hinh_anh'])) {
                // Try to copy image from the path specified in Excel
                $imagePath = $this->copyImageFromPath($normalizedRow['duong_dan_hinh_anh'], $normalizedRow['ma_vat_tu']);
            }
            
            if ($imagePath) {
                Log::info('Using supply image', ['code' => $normalizedRow['ma_vat_tu'], 'path' => $imagePath]);
            }

            return new Supply([
                'code' => $normalizedRow['ma_vat_tu'],
                'name' => $normalizedRow['ten_vat_tu'] ?? '',
                'type' => $this->mapType($normalizedRow['loai'] ?? 'other'),
                'unit' => $normalizedRow['don_vi'] ?? 'cm',
                'quantity' => $lengthPerTree,
                'tree_count' => $treeCount,
                'min_quantity' => !empty($normalizedRow['ton_kho_toi_thieu']) ? (float)$normalizedRow['ton_kho_toi_thieu'] : 0,
                'notes' => $normalizedRow['ghi_chu'] ?? null,
                'image' => $imagePath,
            ]);
        } catch (\Exception $e) {
            $this->skippedCount++;
            $code = isset($normalizedRow['ma_vat_tu']) ? $normalizedRow['ma_vat_tu'] : 'unknown';
            $this->errors[] = "Lỗi dòng mã '{$code}': " . $e->getMessage();
            Log::error('Import supply error', ['row' => $row, 'error' => $e->getMessage()]);
            return null;
        }
    }

    protected function normalizeRow(array $row)
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            // Remove special characters, convert to lowercase
            $normalizedKey = strtolower($key);
            $normalizedKey = preg_replace('/[^a-z0-9_]/', '_', $normalizedKey);
            $normalizedKey = preg_replace('/_+/', '_', $normalizedKey);
            $normalizedKey = trim($normalizedKey, '_');
            
            // Map common variations
            $keyMap = [
                'ma_vat_tu' => 'ma_vat_tu',
                'ten_vat_tu' => 'ten_vat_tu',
                'loai' => 'loai',
                'don_vi' => 'don_vi',
                'chieu_dai_moi_cay_cm' => 'chieu_dai_moi_cay_cm',
                'so_luong_cay' => 'so_luong_cay',
                'ton_kho_toi_thieu' => 'ton_kho_toi_thieu',
                'ghi_chu' => 'ghi_chu',
                'duong_dan_hinh_anh' => 'duong_dan_hinh_anh',
            ];
            
            $finalKey = $keyMap[$normalizedKey] ?? $normalizedKey;
            $normalized[$finalKey] = $value;
        }
        
        return $normalized;
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

    public function rules(): array
    {
        return [
            'ma_vat_tu' => 'required|string|max:50',
            'ten_vat_tu' => 'required|string|max:255',
            'loai' => 'required|string',
            'don_vi' => 'required|string|max:20',
            'chieu_dai_moi_cay_cm' => 'required|numeric|min:0',
            'so_luong_cay' => 'required|integer|min:1',
        ];
    }

    protected function mapType($type)
    {
        $typeMap = [
            'khung tranh' => 'frame',
            'khung' => 'frame',
            'frame' => 'frame',
            'canvas' => 'canvas',
            'khác' => 'other',
            'other' => 'other',
        ];

        return $typeMap[strtolower($type)] ?? 'other';
    }

    /**
     * Copy image from file path to storage
     * Supports multiple path formats:
     * 1. Absolute path: C:\path\to\image.jpg or /path/to/image.jpg
     * 2. Relative to public/temp-imports: image.jpg or subfolder/image.jpg
     * 3. Just filename: image.jpg (looks in public/temp-imports)
     * 
     * @param string $imagePath Path to the image file
     * @param string $code Supply code for naming
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
            $storagePath = 'supplies/' . $uniqueName;
            
            // Copy file to storage
            $imageContent = file_get_contents($resolvedPath);
            if ($imageContent === false) {
                Log::error('Failed to read image file', ['path' => $resolvedPath, 'code' => $code]);
                $this->errors[] = "Không thể đọc file ảnh: {$imagePath} cho mã {$code}";
                return null;
            }
            
            \Illuminate\Support\Facades\Storage::disk('public')->put($storagePath, $imageContent);
            
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

    public function getImportedCount()
    {
        return $this->importedCount;
    }

    public function getSkippedCount()
    {
        return $this->skippedCount;
    }

    public function getUpdatedCount()
    {
        return $this->updatedCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
