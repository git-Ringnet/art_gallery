<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class StockImportExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $reportData;
    protected $totals;
    protected $metadata;

    public function __construct(array $reportData, array $totals, array $metadata = [])
    {
        $this->reportData = $reportData;
        $this->totals = $totals;
        $this->metadata = $metadata;
    }

    public function array(): array
    {
        $rows = [];
        $index = 1;

        foreach ($this->reportData as $item) {
            $rows[] = [
                $index++,
                $item['import_date'],
                $item['code'],
                $item['name'],
                $item['artist'],
                $item['material'],
                $item['dimensions'],
                $item['quantity'],
                number_format($item['price_usd'], 2),
                number_format($item['price_vnd'], 0),
                $item['status'],
            ];
        }

        // Add totals row
        $rows[] = [
            'TOTAL',
            '',
            '',
            '',
            '',
            '',
            '',
            $this->totals['totalQuantity'],
            '$' . number_format($this->totals['totalPriceUsd'], 2),
            number_format($this->totals['totalPriceVnd'], 0),
            '',
        ];

        // Add summary
        $rows[] = [];

        // Only show USD total if no exchange rate is applied (to avoid confusion with converted total)
        // and if there is actually a USD amount
        $exchangeRate = $this->metadata['exchangeRate'] ?? 1;
        if ($exchangeRate <= 1 && $this->totals['totalPriceUsd'] > 0) {
            $rows[] = ['Total Value (USD):', '$' . number_format($this->totals['totalPriceUsd'], 2)];
        }

        $rows[] = ['Grand Total (VND):', 'VND ' . number_format($this->totals['grandTotalVnd'], 0)];

        return $rows;
    }

    public function headings(): array
    {
        return [
            ['Stock Import Report / Báo cáo nhập stock'],
            ['Period: ' . ($this->metadata['fromDate'] ?? '') . ' - ' . ($this->metadata['toDate'] ?? '')],
            [],
            [
                'No.',
                'Import Date',
                'Code',
                'Name',
                'Artist',
                'Material',
                'Dimensions',
                'Quantity',
                'Price USD',
                'Price VND',
                'Status',
            ],
        ];
    }

    public function title(): string
    {
        return 'Stock Import';
    }

    public function styles(Worksheet $sheet)
    {
        // Style header rows
        $sheet->mergeCells('A1:K1');
        $sheet->mergeCells('A2:K2');

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            4 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 12,
            'C' => 15,
            'D' => 30,
            'E' => 20,
            'F' => 15,
            'G' => 15,
            'H' => 10,
            'I' => 12,
            'J' => 15,
            'K' => 15,
        ];
    }
}
