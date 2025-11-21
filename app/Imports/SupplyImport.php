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

            // Check if image exists for this code
            $imagePath = $this->uploadedImages[$normalizedRow['ma_vat_tu']] ?? null;
            if ($imagePath) {
                Log::info('Using uploaded supply image', ['code' => $normalizedRow['ma_vat_tu'], 'path' => $imagePath]);
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
            ];
            
            $finalKey = $keyMap[$normalizedKey] ?? $normalizedKey;
            $normalized[$finalKey] = $value;
        }
        
        return $normalized;
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
