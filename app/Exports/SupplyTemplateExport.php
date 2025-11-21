<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SupplyTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function array(): array
    {
        return [
            [
                'VT001',
                'Khung gỗ sồi',
                'Khung tranh',
                'cm',
                '200',
                '10',
                '50',
                'Ghi chú mẫu'
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'Mã vật tư (*)',
            'Tên vật tư (*)',
            'Loại (*)',
            'Đơn vị (*)',
            'Chiều dài mỗi cây (cm) (*)',
            'Số lượng cây (*)',
            'Tồn kho tối thiểu',
            'Ghi chú'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12], 'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF2CC']
            ]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 25,
            'C' => 15,
            'D' => 12,
            'E' => 25,
            'F' => 18,
            'G' => 20,
            'H' => 25,
        ];
    }
}
