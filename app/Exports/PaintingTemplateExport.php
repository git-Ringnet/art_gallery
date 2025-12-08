<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaintingTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    public function array(): array
    {
        return [
            [
                'T001',
                'Tranh mẫu 1',
                'Nguyễn Văn A',
                'Sơn dầu',
                '100',
                '80',
                '2024',
                '1000',
                '23500000',
                date('Y-m-d'),
                'Có ảnh - Insert ảnh vào dòng này'
            ],
            [
                'T002',
                'Tranh mẫu 2',
                'Trần Thị B',
                'Canvas',
                '120',
                '90',
                '2024',
                '1500',
                '35250000',
                date('Y-m-d'),
                'Không có ảnh'
            ],
            [
                'T003',
                'Tranh mẫu 3',
                'Lê Văn C',
                'Acrylic',
                '80',
                '60',
                '2023',
                '800',
                '18800000',
                date('Y-m-d'),
                'Có ảnh - Insert ảnh vào dòng này'
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'Mã tranh (*)',
            'Tên tranh (*)',
            'Họa sĩ (*)',
            'Chất liệu (*)',
            'Chiều rộng (cm)',
            'Chiều cao (cm)',
            'Năm vẽ',
            'Giá (USD) (*)',
            'Giá (VND)',
            'Ngày nhập kho (*)',
            'Ghi chú'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12], 'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2EFDA']
            ]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,  // Mã tranh
            'B' => 25,  // Tên tranh
            'C' => 20,  // Họa sĩ
            'D' => 15,  // Chất liệu
            'E' => 15,  // Chiều rộng
            'F' => 15,  // Chiều cao
            'G' => 12,  // Năm vẽ
            'H' => 15,  // Giá USD
            'I' => 18,  // Giá VND
            'J' => 18,  // Ngày nhập kho
            'K' => 35,  // Ghi chú
        ];
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Set default row height for data rows to accommodate images
                // We have 3 sample rows (row 2, 3, 4)
                $event->sheet->getDelegate()->getRowDimension(2)->setRowHeight(80);
                $event->sheet->getDelegate()->getRowDimension(3)->setRowHeight(80);
                $event->sheet->getDelegate()->getRowDimension(4)->setRowHeight(80);
            },
        ];
    }
}
