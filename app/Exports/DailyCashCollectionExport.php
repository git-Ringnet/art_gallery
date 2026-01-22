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
                $item['adjustment_usd'] != 0 ? ($item['adjustment_usd'] == floor($item['adjustment_usd']) ? number_format($item['adjustment_usd'], 0) : number_format($item['adjustment_usd'], 2)) : '',
                $item['adjustment_vnd'] != 0 ? number_format($item['adjustment_vnd'], 0) : '',
                $item['collection_usd'] > 0 ? ($item['collection_usd'] == floor($item['collection_usd']) ? number_format($item['collection_usd'], 0) : number_format($item['collection_usd'], 2)) : '',
                $item['collection_vnd'] > 0 ? number_format($item['collection_vnd'], 0) : '',
            ];
        }

        // Add totals row
        $rows[] = [
            'GRAND TOTAL',
            '',
            '',
            '',
            $this->totals['totalAdjustmentUsd'] != 0 ? '$' . ($this->totals['totalAdjustmentUsd'] == floor($this->totals['totalAdjustmentUsd']) ? number_format($this->totals['totalAdjustmentUsd'], 0) : number_format($this->totals['totalAdjustmentUsd'], 2)) : '',
            $this->totals['totalAdjustmentVnd'] != 0 ? number_format($this->totals['totalAdjustmentVnd'], 0) : '',
            '$' . ($this->totals['totalCollectionUsd'] == floor($this->totals['totalCollectionUsd']) ? number_format($this->totals['totalCollectionUsd'], 0) : number_format($this->totals['totalCollectionUsd'], 2)),
            number_format($this->totals['totalCollectionVnd'], 0),
        ];

        // Add summary
        $rows[] = [];
        $rows[] = ['Summary'];

        // Note: In the controller, if exchangeRate > 1, the cashCollectionVnd includes the converted USD.
        // We can detect if exchangeRate was used by comparing provided totals or checking if exchangeRate is in metadata/totals (if we passed it).
        // I passed 'exchangeRate' in the totals array in ReportsController.

        $exchangeRate = $this->totals['exchangeRate'] ?? 1;

        if ($exchangeRate > 1) {
            // Combined Display (VND Total which includes converted USD)
            // Cash
            $cashStr = 'VND ' . number_format($this->totals['cashCollectionVnd'], 0);
            if (isset($this->totals['cashCollectionUsd']) && $this->totals['cashCollectionUsd'] > 0) {
                $usdVal = $this->totals['cashCollectionUsd'];
                $usdStr = $usdVal == floor($usdVal) ? number_format($usdVal, 0) : number_format($usdVal, 2);
                $cashStr .= ' (incl. USD $' . $usdStr . ')';
            }
            $rows[] = ['Collection in CASH:', $cashStr];

            // Card
            $cardStr = 'VND ' . number_format($this->totals['cardCollectionVnd'], 0);
            if (isset($this->totals['cardCollectionUsd']) && $this->totals['cardCollectionUsd'] > 0) {
                $usdVal = $this->totals['cardCollectionUsd'];
                $usdStr = $usdVal == floor($usdVal) ? number_format($usdVal, 0) : number_format($usdVal, 2);
                $cardStr .= ' (incl. USD $' . $usdStr . ')';
            }
            $rows[] = ['In Credit Card + Transfer:', $cardStr];

            // Grand Total
            // The controller grandTotalVnd matches the sum of the above VND totals.
            // We can simulate the view's "Grand Total" line.
            // View: Grand Total: VND ... (x USD * Rate + y VND) - This is mostly informational text in View.
            // The important part is the big bold number.
            $rows[] = ['Grand Total:', 'VND ' . number_format($this->totals['cashCollectionVnd'] + $this->totals['cardCollectionVnd'], 0)];

        } else {
            // Separated Display (Rate = 1 or not provided)
            // Cash Collection
            $cashStr = 'VND ' . number_format($this->totals['cashCollectionVnd'], 0);
            if (isset($this->totals['cashCollectionUsd']) && $this->totals['cashCollectionUsd'] > 0) {
                $usdVal = $this->totals['cashCollectionUsd'];
                $usdStr = $usdVal == floor($usdVal) ? number_format($usdVal, 0) : number_format($usdVal, 2);
                $cashStr .= ' + USD $' . $usdStr;
            }
            $rows[] = ['Collection in CASH:', $cashStr];

            // Card/Transfer Collection
            $cardStr = 'VND ' . number_format($this->totals['cardCollectionVnd'], 0);
            if (isset($this->totals['cardCollectionUsd']) && $this->totals['cardCollectionUsd'] > 0) {
                $usdVal = $this->totals['cardCollectionUsd'];
                $usdStr = $usdVal == floor($usdVal) ? number_format($usdVal, 0) : number_format($usdVal, 2);
                $cardStr .= ' + USD $' . $usdStr;
            }
            $rows[] = ['In Credit Card + Transfer:', $cardStr];

            // Grand Total
            $totalVnd = $this->totals['cashCollectionVnd'] + $this->totals['cardCollectionVnd'];
            $totalUsd = ($this->totals['cashCollectionUsd'] ?? 0) + ($this->totals['cardCollectionUsd'] ?? 0);

            $grandTotalStr = 'VND ' . number_format($totalVnd, 0);
            if ($totalUsd > 0) {
                $usdStr = $totalUsd == floor($totalUsd) ? number_format($totalUsd, 0) : number_format($totalUsd, 2);
                $grandTotalStr .= ' + USD $' . $usdStr;
            }
            $rows[] = ['Grand Total:', $grandTotalStr];
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
        ];
    }
}
