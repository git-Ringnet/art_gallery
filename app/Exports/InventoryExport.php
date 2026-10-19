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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class InventoryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected $inventory;
    protected $selectedColumns;
    protected $rowNumber = 0;

    // Định nghĩa tất cả cột có thể xuất
    protected static $columnDefinitions = [
        'stt' => ['title' => 'STT', 'width' => 8],
        'code' => ['title' => 'Mã', 'width' => 15],
        'name' => ['title' => 'Tên sản phẩm', 'width' => 30],
        'type' => ['title' => 'Loại', 'width' => 12],
        'artist' => ['title' => 'Họa sĩ', 'width' => 20],
        'material' => ['title' => 'Chất liệu', 'width' => 15],
        'dimensions' => ['title' => 'Kích thước', 'width' => 22],
        'paint_year' => ['title' => 'Năm SX', 'width' => 12],
        'price_usd' => ['title' => 'Giá (USD)', 'width' => 15],
        'price_vnd' => ['title' => 'Giá (VND)', 'width' => 18],
        'quantity' => ['title' => 'Số lượng', 'width' => 15],
        'unit' => ['title' => 'Đơn vị', 'width' => 10],
        'import_date' => ['title' => 'Ngày nhập', 'width' => 15],
        'export_date' => ['title' => 'Ngày xuất', 'width' => 15],
        'invoice_code' => ['title' => 'Số hóa đơn', 'width' => 18],
        'status' => ['title' => 'Trạng thái', 'width' => 15],
        'notes' => ['title' => 'Ghi chú', 'width' => 25],
    ];

    public function __construct($inventory, ?array $columns = null)
    {
        $this->inventory = $inventory;
        
        if (!empty($columns)) {
            // Lọc các cột hợp lệ theo danh sách yêu cầu
            $validColumns = array_keys(self::$columnDefinitions);
            $this->selectedColumns = array_values(array_intersect($columns, $validColumns));
            if (empty($this->selectedColumns)) {
                $this->selectedColumns = $validColumns;
            }
        } else {
            $this->selectedColumns = array_keys(self::$columnDefinitions);
        }
    }

    public function collection()
    {
        return $this->inventory;
    }

    public function headings(): array
    {
        $headings = [];
        foreach ($this->selectedColumns as $colKey) {
            $headings[] = self::$columnDefinitions[$colKey]['title'];
        }
        return $headings;
    }

    public function map($item): array
    {
        $row = [];
        foreach ($this->selectedColumns as $colKey) {
            switch ($colKey) {
                case 'stt':
                    $this->rowNumber++;
                    $row[] = $this->rowNumber;
                    break;
                case 'code':
                    $row[] = $item['code'] ?? '';
                    break;
                case 'name':
                    $row[] = $item['name'] ?? '';
                    break;
                case 'type':
                    $row[] = $item['type'] ?? '';
                    break;
                case 'artist':
                    $row[] = $item['artist'] ?? '';
                    break;
                case 'material':
                    $material = $item['material'] ?? '';
                    $materialLabels = [
                        'son_dau' => 'Sơn dầu',
                        'canvas' => 'Canvas',
                        'thuy_mac' => 'Thủy mặc',
                        'acrylic' => 'Acrylic',
                    ];
                    $row[] = $materialLabels[$material] ?? $material;
                    break;
                case 'dimensions':
                    $row[] = $item['dimensions'] ?? '';
                    break;
                case 'paint_year':
                    $row[] = $item['paint_year'] ?? '';
                    break;
                case 'price_usd':
                    $row[] = $item['price_usd'] ?? '';
                    break;
                case 'price_vnd':
                    $row[] = $item['price_vnd'] ?? '';
                    break;
                case 'quantity':
                    $row[] = $item['quantity'] ?? '';
                    break;
                case 'unit':
                    $row[] = $item['unit'] ?? '';
                    break;
                case 'import_date':
                    $row[] = $item['import_date'] ?? '';
                    break;
                case 'export_date':
                    $row[] = $item['export_date'] ?? '';
                    break;
                case 'invoice_code':
                    $row[] = $item['invoice_code'] ?? '';
                    break;
                case 'status':
                    $row[] = $item['status'] ?? '';
                    break;
                case 'notes':
                    $row[] = $item['notes'] ?? '';
                    break;
                default:
                    $row[] = '';
            }
        }
        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }

    public function columnWidths(): array
    {
        $widths = [];
        foreach ($this->selectedColumns as $index => $colKey) {
            $colLetter = Coordinate::stringFromColumnIndex($index + 1);
            $widths[$colLetter] = self::$columnDefinitions[$colKey]['width'];
        }
        return $widths;
    }

    public function registerEvents(): array
    {
        return [];
    }
}
