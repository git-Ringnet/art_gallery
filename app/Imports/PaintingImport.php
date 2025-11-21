<?php

namespace App\Imports;

use App\Models\Painting;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;

class PaintingImport implements ToModel, WithHeadingRow, SkipsOnError, WithEvents
{
    use SkipsErrors;

    protected $importedCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];
    protected $embeddedImages = [];
    protected $uploadedImages = [];

    public function __construct(array $uploadedImages = [])
    {
        $this->uploadedImages = $uploadedImages;
        Log::info('PaintingImport initialized', ['uploaded_images_count' => count($uploadedImages)]);
    }

    // Map Vietnamese headers to field names
    protected $headerMap = [
        'ma_tranh' => 'ma_tranh',
        'ten_tranh' => 'ten_tranh',
        'hoa_si' => 'hoa_si',
        'chat_lieu' => 'chat_lieu',
        'chieu_rong_cm' => 'chieu_rong_cm',
        'chieu_cao_cm' => 'chieu_cao_cm',
        'nam_ve' => 'nam_ve',
        'gia_usd' => 'gia_usd',
        'ngay_nhap_kho' => 'ngay_nhap_kho',
        'ngay_xuat_kho' => 'ngay_xuat_kho',
        'duong_dan_hinh_anh' => 'duong_dan_hinh_anh',
        'ghi_chu' => 'ghi_chu',
    ];

    public function model(array $row)
    {
        try {
            // Log raw row for debugging
            Log::info('Processing row', ['raw_row' => $row]);
            
            // Normalize row keys (remove special characters, convert to lowercase with underscores)
            $normalizedRow = $this->normalizeRow($row);
            
            Log::info('Normalized row', ['normalized_row' => $normalizedRow]);
            
            // Skip if code is empty
            if (empty($normalizedRow['ma_tranh'])) {
                $this->skippedCount++;
                $this->errors[] = "Dòng bị bỏ qua: Thiếu mã tranh";
                Log::warning('Skipped row: missing code');
                return null;
            }

            // Check if painting code already exists
            $exists = Painting::where('code', $normalizedRow['ma_tranh'])->exists();
            Log::info('Checking if code exists', ['code' => $normalizedRow['ma_tranh'], 'exists' => $exists]);
            
            if ($exists) {
                $this->skippedCount++;
                $this->errors[] = "Mã tranh '{$normalizedRow['ma_tranh']}' đã tồn tại";
                Log::warning('Skipped row: code already exists', ['code' => $normalizedRow['ma_tranh']]);
                return null;
            }

            // Handle image if provided (priority order)
            $imagePath = null;
            $code = $normalizedRow['ma_tranh'];
            
            // Priority 1: Uploaded images (matched by code)
            if (isset($this->uploadedImages[$code])) {
                $imagePath = $this->uploadedImages[$code];
                Log::info('Using uploaded image', ['code' => $code, 'path' => $imagePath]);
            }
            // Priority 2: Embedded image in Excel
            elseif (isset($this->embeddedImages[$this->getCurrentRowIndex($row)])) {
                $imagePath = $this->embeddedImages[$this->getCurrentRowIndex($row)];
                Log::info('Using embedded image', ['code' => $code, 'path' => $imagePath]);
            }
            // Priority 3: Path in column
            elseif (!empty($normalizedRow['duong_dan_hinh_anh'])) {
                $imagePath = $this->handleImage($normalizedRow['duong_dan_hinh_anh']);
                Log::info('Using path from column', ['code' => $code, 'path' => $imagePath]);
            }

            $this->importedCount++;

            $paintingData = [
                'code' => $normalizedRow['ma_tranh'],
                'name' => $normalizedRow['ten_tranh'] ?? '',
                'artist' => $normalizedRow['hoa_si'] ?? '',
                'material' => $normalizedRow['chat_lieu'] ?? '',
                'width' => !empty($normalizedRow['chieu_rong_cm']) ? (float)$normalizedRow['chieu_rong_cm'] : null,
                'height' => !empty($normalizedRow['chieu_cao_cm']) ? (float)$normalizedRow['chieu_cao_cm'] : null,
                'paint_year' => $normalizedRow['nam_ve'] ?? null,
                'price_usd' => !empty($normalizedRow['gia_usd']) ? (float)$normalizedRow['gia_usd'] : 0,
                'price_vnd' => null,
                'image' => $imagePath,
                'quantity' => 1,
                'import_date' => !empty($normalizedRow['ngay_nhap_kho']) ? $this->parseDate($normalizedRow['ngay_nhap_kho']) : now(),
                'export_date' => !empty($normalizedRow['ngay_xuat_kho']) ? $this->parseDate($normalizedRow['ngay_xuat_kho']) : null,
                'notes' => $normalizedRow['ghi_chu'] ?? null,
                'status' => 'in_stock',
            ];
            
            Log::info('Creating painting', ['data' => $paintingData]);
            
            return new Painting($paintingData);
        } catch (\Exception $e) {
            $this->skippedCount++;
            $code = isset($normalizedRow['ma_tranh']) ? $normalizedRow['ma_tranh'] : 'unknown';
            $this->errors[] = "Lỗi dòng mã '{$code}': " . $e->getMessage();
            Log::error('Import painting error', [
                'row' => $row,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
                'ma_tranh' => 'ma_tranh',
                'ten_tranh' => 'ten_tranh',
                'hoa_si' => 'hoa_si',
                'chat_lieu' => 'chat_lieu',
                'chieu_rong_cm' => 'chieu_rong_cm',
                'chieu_cao_cm' => 'chieu_cao_cm',
                'nam_ve' => 'nam_ve',
                'gia_usd' => 'gia_usd',
                'ngay_nhap_kho' => 'ngay_nhap_kho',
                'ngay_xuat_kho' => 'ngay_xuat_kho',
                'duong_dan_hinh_anh' => 'duong_dan_hinh_anh',
                'ghi_chu' => 'ghi_chu',
            ];
            
            $finalKey = $keyMap[$normalizedKey] ?? $normalizedKey;
            $normalized[$finalKey] = $value;
        }
        
        return $normalized;
    }



    protected function handleImage($imagePath)
    {
        try {
            // Trim whitespace
            $imagePath = trim($imagePath);
            
            // Check if it's a URL
            if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
                // Download image from URL
                $contents = @file_get_contents($imagePath);
                if ($contents === false) {
                    return null;
                }
                
                $filename = 'paintings/' . uniqid() . '_' . basename($imagePath);
                Storage::disk('public')->put($filename, $contents);
                return $filename;
            }
            
            // Check if it's an absolute Windows path (C:\...) or Unix path (/...)
            if (file_exists($imagePath) && is_file($imagePath)) {
                // Validate it's an image
                $imageInfo = @getimagesize($imagePath);
                if ($imageInfo === false) {
                    Log::warning('File is not a valid image', ['path' => $imagePath]);
                    return null;
                }
                
                // Copy to storage
                $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
                $filename = 'paintings/' . uniqid() . '_' . time() . '.' . $extension;
                Storage::disk('public')->put($filename, file_get_contents($imagePath));
                return $filename;
            }
            
            // Check if it's a local file path in storage
            if (Storage::disk('public')->exists($imagePath)) {
                return $imagePath;
            }
            
            // Check if it's a file in public/temp folder
            $tempPath = public_path('temp/' . $imagePath);
            if (file_exists($tempPath)) {
                $filename = 'paintings/' . uniqid() . '_' . basename($imagePath);
                Storage::disk('public')->put($filename, file_get_contents($tempPath));
                return $filename;
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Image handling error', ['path' => $imagePath, 'error' => $e->getMessage()]);
            return null;
        }
    }

    protected function parseDate($date)
    {
        try {
            if (is_numeric($date)) {
                // Excel date format (days since 1900-01-01)
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $this->extractEmbeddedImagesFromSheet($event);
            },
        ];
    }
    
    protected function extractEmbeddedImagesFromSheet($event)
    {
        try {
            $sheet = $event->sheet->getDelegate();
            $drawings = $sheet->getDrawingCollection();
            
            Log::info('AfterSheet: Extracting images', ['total_drawings' => count($drawings)]);
            
            foreach ($drawings as $drawing) {
                Log::info('Processing drawing', ['type' => get_class($drawing)]);
                
                // Get the row where the image is placed
                $coordinates = $drawing->getCoordinates();
                preg_match('/(\d+)/', $coordinates, $matches);
                $rowIndex = isset($matches[1]) ? (int)$matches[1] : 0;

                Log::info('Drawing details', [
                    'coordinates' => $coordinates,
                    'rowIndex' => $rowIndex
                ]);

                // Skip header row (row 1)
                if ($rowIndex <= 1) {
                    Log::info('Skipping header row');
                    continue;
                }

                $imageContent = null;
                $extension = 'jpg';
                
                if ($drawing instanceof Drawing) {
                    // File-based drawing
                    $imagePath = $drawing->getPath();
                    Log::info('File drawing', ['path' => $imagePath, 'exists' => file_exists($imagePath)]);
                    
                    if (file_exists($imagePath)) {
                        $imageContent = file_get_contents($imagePath);
                        $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
                    }
                } elseif ($drawing instanceof MemoryDrawing) {
                    // Memory-based drawing (copied/pasted images)
                    Log::info('Memory drawing detected');
                    
                    ob_start();
                    $gdImage = $drawing->getImageResource();
                    
                    // Determine format
                    $mimeType = $drawing->getMimeType();
                    switch ($mimeType) {
                        case MemoryDrawing::MIMETYPE_PNG:
                            imagepng($gdImage);
                            $extension = 'png';
                            break;
                        case MemoryDrawing::MIMETYPE_GIF:
                            imagegif($gdImage);
                            $extension = 'gif';
                            break;
                        case MemoryDrawing::MIMETYPE_JPEG:
                        default:
                            imagejpeg($gdImage);
                            $extension = 'jpg';
                            break;
                    }
                    
                    $imageContent = ob_get_contents();
                    ob_end_clean();
                }
                
                if ($imageContent) {
                    // Save to storage
                    $filename = 'paintings/' . uniqid() . '_' . time() . '.' . $extension;
                    Storage::disk('public')->put($filename, $imageContent);
                    
                    // Store with row index (subtract 1 for header)
                    $this->embeddedImages[$rowIndex - 1] = $filename;
                    
                    Log::info('Extracted embedded image', [
                        'row' => $rowIndex,
                        'data_row' => $rowIndex - 1,
                        'filename' => $filename,
                        'size' => strlen($imageContent)
                    ]);
                }
            }
            
            Log::info('Embedded images extracted', ['images' => $this->embeddedImages]);
        } catch (\Exception $e) {
            Log::error('Error extracting embedded images from sheet', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function extractEmbeddedImages($event)
    {
        try {
            $reader = $event->getReader();
            $worksheet = $reader->getActiveSheet();
            
            // Try different methods to get drawings
            $drawings = [];
            
            // Method 1: getDrawingCollection
            try {
                $drawings = $worksheet->getDrawingCollection();
                Log::info('Method 1: getDrawingCollection', ['count' => count($drawings)]);
            } catch (\Exception $e) {
                Log::warning('Method 1 failed', ['error' => $e->getMessage()]);
            }
            
            // Method 2: Direct iteration
            if (empty($drawings)) {
                try {
                    foreach ($worksheet->getDrawingCollection() as $drawing) {
                        $drawings[] = $drawing;
                    }
                    Log::info('Method 2: Direct iteration', ['count' => count($drawings)]);
                } catch (\Exception $e) {
                    Log::warning('Method 2 failed', ['error' => $e->getMessage()]);
                }
            }

            Log::info('Extracting embedded images', ['total_drawings' => count($drawings)]);

            foreach ($drawings as $drawing) {
                Log::info('Processing drawing', ['type' => get_class($drawing)]);
                
                // Get the row where the image is placed
                $coordinates = $drawing->getCoordinates();
                preg_match('/(\d+)/', $coordinates, $matches);
                $rowIndex = isset($matches[1]) ? (int)$matches[1] : 0;

                Log::info('Drawing details', [
                    'coordinates' => $coordinates,
                    'rowIndex' => $rowIndex
                ]);

                // Skip header row (row 1)
                if ($rowIndex <= 1) {
                    Log::info('Skipping header row');
                    continue;
                }

                $imageContent = null;
                $extension = 'jpg';
                
                if ($drawing instanceof Drawing) {
                    // File-based drawing
                    $imagePath = $drawing->getPath();
                    Log::info('File drawing', ['path' => $imagePath, 'exists' => file_exists($imagePath)]);
                    
                    if (file_exists($imagePath)) {
                        $imageContent = file_get_contents($imagePath);
                        $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
                    }
                } elseif ($drawing instanceof MemoryDrawing) {
                    // Memory-based drawing (copied/pasted images)
                    Log::info('Memory drawing detected');
                    
                    ob_start();
                    $gdImage = $drawing->getImageResource();
                    
                    // Determine format
                    $mimeType = $drawing->getMimeType();
                    switch ($mimeType) {
                        case MemoryDrawing::MIMETYPE_PNG:
                            imagepng($gdImage);
                            $extension = 'png';
                            break;
                        case MemoryDrawing::MIMETYPE_GIF:
                            imagegif($gdImage);
                            $extension = 'gif';
                            break;
                        case MemoryDrawing::MIMETYPE_JPEG:
                        default:
                            imagejpeg($gdImage);
                            $extension = 'jpg';
                            break;
                    }
                    
                    $imageContent = ob_get_contents();
                    ob_end_clean();
                }
                
                if ($imageContent) {
                    // Save to storage
                    $filename = 'paintings/' . uniqid() . '_' . time() . '.' . $extension;
                    Storage::disk('public')->put($filename, $imageContent);
                    
                    // Store with row index (subtract 1 for header)
                    $this->embeddedImages[$rowIndex - 1] = $filename;
                    
                    Log::info('Extracted embedded image', [
                        'row' => $rowIndex,
                        'data_row' => $rowIndex - 1,
                        'filename' => $filename,
                        'size' => strlen($imageContent)
                    ]);
                }
            }
            
            Log::info('Embedded images extracted', ['images' => $this->embeddedImages]);
        } catch (\Exception $e) {
            Log::error('Error extracting embedded images', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function getCurrentRowIndex($row)
    {
        // Try to get row index from the data
        // This is a workaround since we can't directly get row number in ToModel
        static $currentRow = 1; // Start from 1 (after header)
        return $currentRow++;
    }
}
