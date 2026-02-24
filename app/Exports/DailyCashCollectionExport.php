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
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class DailyCashCollectionExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths, WithColumnFormatting
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
                $item['adjustment_usd'] != 0 ? $item['adjustment_usd'] : '',
                $item['adjustment_vnd'] != 0 ? $item['adjustment_vnd'] : '',
                $item['collection_usd'] > 0 ? $item['collection_usd'] : '',
                $item['collection_vnd'] > 0 ? $item['collection_vnd'] : '',
            ];
        }

        // Add totals row
        $exchangeRate = $this->totals['exchangeRate'] ?? 1;
        $totalCollectionVnd = $this->totals['totalCollectionVnd'] ?? 0;
        $totalCollectionUsd = $this->totals['totalCollectionUsd'] ?? 0;
        $displayGrandVnd = ($exchangeRate > 1) ? ($totalCollectionVnd + ($totalCollectionUsd * $exchangeRate)) : $totalCollectionVnd;

        $rows[] = [
            'GRAND TOTAL',
            '',
            '',
            '',
            $this->totals['totalAdjustmentUsd'] != 0 ? $this->totals['totalAdjustmentUsd'] : '',
            $this->totals['totalAdjustmentVnd'] != 0 ? $this->totals['totalAdjustmentVnd'] : '',
            $totalCollectionUsd != 0 ? $totalCollectionUsd : '',
            $displayGrandVnd != 0 ? $displayGrandVnd : '',
        ];

        // Add summary
        $rows[] = [];
        $rows[] = ['Summary'];

        $exchangeRate = $this->totals['exchangeRate'] ?? 1;

        // Use converted VND for summary when rate > 1
        $cashVnd = ($exchangeRate > 1) ? $this->totals['cashCollectionVnd'] : $this->totals['cashCollectionPureVnd'];
        $cardVnd = ($exchangeRate > 1) ? $this->totals['cardCollectionVnd'] : $this->totals['cardCollectionPureVnd'];
        $cashUsd = $this->totals['cashCollectionUsd'] ?? 0;
        $cardUsd = $this->totals['cardCollectionUsd'] ?? 0;

        // Cash
        $cashStr = 'VND ' . number_format($cashVnd, 0);
        if ($cashUsd > 0) {
            $label = ($exchangeRate > 1) ? 'incl. USD $' : '+ USD $';
            $cashStr .= ' (' . $label . ($cashUsd == floor($cashUsd) ? number_format($cashUsd, 0) : number_format($cashUsd, 2)) . ')';
        }
        $rows[] = ['Collection in CASH:', $cashStr];

        // Card
        $cardStr = 'VND ' . number_format($cardVnd, 0);
        if ($cardUsd > 0) {
            $label = ($exchangeRate > 1) ? 'incl. USD $' : '+ USD $';
            $cardStr .= ' (' . $label . ($cardUsd == floor($cardUsd) ? number_format($cardUsd, 0) : number_format($cardUsd, 2)) . ')';
        }
        $rows[] = ['In Credit Card + Transfer:', $cardStr];

        // Grand Total
        $totalVnd = $cashVnd + $cardVnd;
        $totalUsd = $cashUsd + $cardUsd;
        $grandTotalStr = 'VND ' . number_format($totalVnd, 0);
        if ($totalUsd > 0) {
            $grandTotalStr .= ' (incl. USD $' . ($totalUsd == floor($totalUsd) ? number_format($totalUsd, 0) : number_format($totalUsd, 2)) . ')';
        }
        $rows[] = ['Grand Total:', $grandTotalStr];

        if ($exchangeRate > 1) {
            $rows[] = ['', '(Ex. Rate: ' . number_format($exchangeRate, 0) . ')'];
        }

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
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');

        $styles = [
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

        // Apply colors to data rows (starting from row 6)
        $currentRow = 6;
        foreach ($this->reportData as $item) {
            $color = ($item['payment_method'] == 'cash') ? '059669' : '2563eb';
            
            // Collection USD (G) and VND (H)
            $sheet->getStyle("G{$currentRow}:H{$currentRow}")->getFont()->getColor()->setRGB($color);
            $sheet->getStyle("G{$currentRow}:H{$currentRow}")->getFont()->setBold(true);
            
            $currentRow++;
        }

        // Style the Grand Total row
        $sheet->getStyle("A{$currentRow}:H{$currentRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$currentRow}:H{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
        // Colors for grand total collection - using green as default or just bold
        $sheet->getStyle("G{$currentRow}:H{$currentRow}")->getFont()->getColor()->setRGB('059669');

        return $styles;
    }

    public function columnFormats(): array
    {
        return [
            'E' => '#,##0.00',
            'F' => '#,##0',
            'G' => '#,##0.00',
            'H' => '#,##0',
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
        ];
    }
}
