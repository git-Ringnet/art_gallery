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

class DailyCashCollectionExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
                $item['invoice_code'],
                $item['id_code'],
                $item['customer_name'],
                $item['adjustment_usd'] != 0 ? number_format($item['adjustment_usd'], 2) : '',
                $item['adjustment_vnd'] != 0 ? number_format($item['adjustment_vnd'], 0) : '',
                $item['collection_usd'] > 0 ? number_format($item['collection_usd'], 2) : '',
                $item['collection_vnd'] > 0 ? number_format($item['collection_vnd'], 0) : '',
                isset($item['collection_adjustment_usd']) && $item['collection_adjustment_usd'] != 0 ? number_format($item['collection_adjustment_usd'], 2) : '',
                isset($item['collection_adjustment_vnd']) && $item['collection_adjustment_vnd'] != 0 ? number_format($item['collection_adjustment_vnd'], 0) : '',
            ];
        }

        // Add totals row
        $rows[] = [
            'GRAND TOTAL',
            '',
            '',
            '',
            $this->totals['totalAdjustmentUsd'] != 0 ? '$' . number_format($this->totals['totalAdjustmentUsd'], 2) : '',
            $this->totals['totalAdjustmentVnd'] != 0 ? number_format($this->totals['totalAdjustmentVnd'], 0) : '',
            '$' . number_format($this->totals['totalCollectionUsd'], 2),
            number_format($this->totals['totalCollectionVnd'], 0),
            $this->totals['totalCollectionAdjustmentUsd'] != 0 ? '$' . number_format($this->totals['totalCollectionAdjustmentUsd'], 2) : '',
            $this->totals['totalCollectionAdjustmentVnd'] != 0 ? number_format($this->totals['totalCollectionAdjustmentVnd'], 0) : '',
        ];

        // Add summary
        $rows[] = [];
        $rows[] = ['Summary'];
        $rows[] = ['Collection in CASH:', 'VND ' . number_format($this->totals['cashCollectionVnd'], 0)];
        $rows[] = ['In Credit Card + Transfer:', 'VND ' . number_format($this->totals['cardCollectionVnd'], 0)];
        $rows[] = ['Grand Total:', 'VND ' . number_format($this->totals['cashCollectionVnd'] + $this->totals['cardCollectionVnd'], 0)];

        return $rows;
    }

    public function headings(): array
    {
        return [
            ['Daily Cash Collection Report'],
            ['Period: ' . ($this->metadata['fromDate'] ?? '') . ' - ' . ($this->metadata['toDate'] ?? '')],
            ['Showroom: ' . ($this->metadata['showroom'] ?? 'All')],
            [],
            [
                'No.',
                'Invoice',
                'ID Code',
                'Customer name',
                'Adjustment USD',
                'Adjustment VND',
                'Collection USD',
                'Collection VND',
                'Adj. USD',
                'Adj. VND',
            ],
        ];
    }

    public function title(): string
    {
        return 'Cash Collection';
    }

    public function styles(Worksheet $sheet)
    {
        // Style header rows
        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');
        $sheet->mergeCells('A3:J3');

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            3 => [
                'font' => ['size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            5 => [
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
            'B' => 15,
            'C' => 15,
            'D' => 25,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 15,
            'I' => 12,
            'J' => 12,
        ];
    }
}
