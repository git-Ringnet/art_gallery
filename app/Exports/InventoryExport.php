<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class InventoryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
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
            'Giá (USD)',
            'Hình ảnh'
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
            '', // Placeholder for image - actual image inserted via AfterSheet event
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
            'K' => 18,  // Hình ảnh
        ];
    }

    public function registerEvents(): array
    {
        $inventory = $this->inventory;

        return [
            AfterSheet::class => function (AfterSheet $event) use ($inventory) {
                $sheet = $event->sheet->getDelegate();
                $row = 2; // Start after header row

                foreach ($inventory as $item) {
                    $imagePath = $item['image_path'] ?? null;

                    if ($imagePath && file_exists($imagePath)) {
                        try {
                            $drawing = new Drawing();
                            $drawing->setName($item['name'] ?? 'Image');
                            $drawing->setDescription($item['name'] ?? 'Image');
                            $drawing->setPath($imagePath);
                            $drawing->setHeight(60);
                            $drawing->setCoordinates('K' . $row);
                            $drawing->setOffsetX(5);
                            $drawing->setOffsetY(5);
                            $drawing->setWorksheet($sheet);

                            // Set row height to fit the image
                            $sheet->getRowDimension($row)->setRowHeight(50);
                        } catch (\Exception $e) {
                            // If image fails, just leave the cell empty
                        }
                    }

                    $row++;
                }
            },
        ];
    }
}
