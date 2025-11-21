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
        foreach ($rows as $index => $row) {
            // Index 0 is Header (Row 1 in Excel)
            // Index 1 is Data (Row 2 in Excel)
            $excelRow = $index + 1;

            // Skip header
            if ($index === 0) continue;

            try {
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

                // Get image for this row
                $imagePath = null;
                
                // Priority 1: Uploaded images
                if (isset($this->uploadedImages[$code])) {
                    $imagePath = $this->uploadedImages[$code];
                }
                // Priority 2: Embedded images
                elseif (isset($this->excelImages[$excelRow])) {
                    $imagePath = $this->excelImages[$excelRow];
                }

                // Create painting
                Painting::create([
                    'code' => $code,
                    'name' => $row[1] ?? '',
                    'artist' => $row[2] ?? '',
                    'material' => $row[3] ?? '',
                    'width' => !empty($row[4]) ? (float)$row[4] : null,
                    'height' => !empty($row[5]) ? (float)$row[5] : null,
                    'paint_year' => $row[6] ?? null,
                    'price_usd' => !empty($row[7]) ? (float)$row[7] : 0,
                    'price_vnd' => !empty($row[8]) ? (float)$row[8] : null,
                    'image' => $imagePath,
                    'quantity' => 1,
                    'import_date' => !empty($row[9]) ? $this->parseDate($row[9]) : now(),
                    'export_date' => !empty($row[10]) ? $this->parseDate($row[10]) : null,
                    'notes' => $row[12] ?? null,
                    'status' => 'in_stock',
                ]);

                $this->importedCount++;

            } catch (\Exception $e) {
                $this->skippedCount++;
                $this->errors[] = "Dòng {$excelRow}: " . $e->getMessage();
            }
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
