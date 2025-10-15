<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $inventory;

    public function __construct($inventory)
    {
        $this->inventory = $inventory;
    }

    public function collection()
    {
        return $this->inventory;
    }

    public function headings(): array
    {
        return [
            'Mã',
            'Tên sản phẩm',
            'Loại',
            'Số lượng',
            'Đơn vị',
            'Ngày nhập',
            'Trạng thái',
            'Họa sĩ',
            'Chất liệu',
            'Giá (USD)'
        ];
    }

    public function map($item): array
    {
        return [
            $item['code'],
            $item['name'],
            $item['type'],
            $item['quantity'],
            $item['unit'] ?? '',
            $item['import_date'],
            $item['status'],
            $item['artist'] ?? '',
            $item['material'] ?? '',
            $item['price_usd'] ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,  // Mã
            'B' => 30,  // Tên sản phẩm
            'C' => 12,  // Loại
            'D' => 12,  // Số lượng
            'E' => 10,  // Đơn vị
            'F' => 15,  // Ngày nhập
            'G' => 15,  // Trạng thái
            'H' => 20,  // Họa sĩ
            'I' => 20,  // Chất liệu
            'J' => 15,  // Giá (USD)
        ];
    }
}
