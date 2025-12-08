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
                'cm',
                '200',
                '10',
                'Ghi chú mẫu'
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'Mã vật tư (*)',
            'Tên vật tư (*)',
            'Đơn vị (*)',
            'Chiều dài mỗi cây (cm) (*)',
            'Số lượng cây (*)',
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
            'C' => 12,
            'D' => 25,
            'E' => 18,
            'F' => 25,
        ];
    }
}
