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

class DebtReportExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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

            $isUsdOnly = $item['is_usd_only'] ?? false;
            $isVndOnly = $item['is_vnd_only'] ?? false;

            // Logic to determine what to show matches the View
            $showPaidUsd = true;
            $showPaidVnd = true;
            $showDebtUsd = true;
            $showDebtVnd = true;

            if ($isUsdOnly) {
                $showPaidVnd = false;
                $showDebtVnd = false;
            } elseif ($isVndOnly) {
                $showPaidUsd = false;
                $showDebtUsd = false;
            }

            $rows[] = [
                $index++,
                $item['sale_date'],
                $item['invoice_code'],
                $item['id_code'],
                $item['customer_name'],
                $item['customer_phone'],
                $item['total_usd'] > 0 ? number_format($item['total_usd'], 2) : '',
                $item['total_vnd'] > 0 ? number_format($item['total_vnd'], 0) : '',
                ($showPaidUsd && $item['paid_usd'] > 0) ? number_format($item['paid_usd'], 2) : '',
                ($showPaidVnd && $item['paid_vnd'] > 0) ? number_format($item['paid_vnd'], 0) : '',
                ($showDebtUsd && $item['debt_usd'] > 0) ? number_format($item['debt_usd'], 2) : '',
                ($showDebtVnd && $item['debt_vnd'] > 0) ? number_format($item['debt_vnd'], 0) : '',
            ];
        }

        // Totals Row
        $rows[] = [
            'TOTAL',
            '',
            '',
            '',
            '',
            '',
            $this->totals['totalSaleUsd'] > 0 ? '$' . number_format($this->totals['totalSaleUsd'], 2) : '',
            $this->totals['totalSaleVnd'] > 0 ? number_format($this->totals['totalSaleVnd'], 0) . 'đ' : '',
            $this->totals['totalPaidUsd'] > 0 ? '$' . number_format($this->totals['totalPaidUsd'], 2) : '',
            $this->totals['totalPaidVnd'] > 0 ? number_format($this->totals['totalPaidVnd'], 0) . 'đ' : '',
            $this->totals['totalDebtUsd'] > 0 ? '$' . number_format($this->totals['totalDebtUsd'], 2) : '',
            $this->totals['totalDebtVnd'] > 0 ? number_format($this->totals['totalDebtVnd'], 0) . 'đ' : '',
        ];

        // Add summary
        $rows[] = [];

        $exchangeRate = $this->metadata['exchangeRate'] ?? 1;

        if ($exchangeRate > 1) {
            // Converted to VND
            $rows[] = ['Grand Total (VND):', 'VND ' . number_format($this->totals['grandTotalVnd'], 0)];
            $rows[] = ['Total Paid (VND):', 'VND ' . number_format($this->totals['grandPaidVnd'], 0)];
            $rows[] = ['Total Debt (VND):', 'VND ' . number_format($this->totals['grandDebtVnd'], 0)];
        } else {
            // Mixed currencies

            // Total Sales
            $totalStr = [];
            if ($this->totals['totalSaleUsd'] > 0)
                $totalStr[] = 'USD: $' . number_format($this->totals['totalSaleUsd'], 2);
            if ($this->totals['totalSaleVnd'] > 0)
                $totalStr[] = 'VND: ' . number_format($this->totals['totalSaleVnd'], 0) . 'đ';
            $rows[] = ['Total Revenue:', implode(" + ", $totalStr)];

            // Total Paid
            $paidStr = [];
            if ($this->totals['totalPaidUsd'] > 0)
                $paidStr[] = 'USD: $' . number_format($this->totals['totalPaidUsd'], 2);
            if ($this->totals['totalPaidVnd'] > 0)
                $paidStr[] = 'VND: ' . number_format($this->totals['totalPaidVnd'], 0) . 'đ';
            if (empty($paidStr))
                $paidStr[] = '0đ';
            $rows[] = ['Total Paid:', implode(" + ", $paidStr)];

            // Total Debt
            $debtStr = [];
            if ($this->totals['totalDebtUsd'] > 0)
                $debtStr[] = 'USD: $' . number_format($this->totals['totalDebtUsd'], 2);
            if ($this->totals['totalDebtVnd'] > 0)
                $debtStr[] = 'VND: ' . number_format($this->totals['totalDebtVnd'], 0) . 'đ';
            if (empty($debtStr))
                $debtStr[] = '0đ';
            $rows[] = ['Total Debt:', implode(" + ", $debtStr)];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            ['Debt Report / Báo cáo công nợ'],
            ['Type: ' . ($this->metadata['reportType'] ?? 'Cumulative') . ' | Period: ' . ($this->metadata['fromDate'] ?? '') . ' - ' . ($this->metadata['toDate'] ?? '')],
            ['Showroom: ' . ($this->metadata['showroom'] ?? 'All')],
            [],
            [
                'No.',
                'Sale Date',
                'Invoice',
                'ID Code',
                'Customer',
                'Phone',
                'Total USD',
                'Total VND',
                'Paid USD',
                'Paid VND',
                'Debt USD',
                'Debt VND',
            ],
        ];
    }

    public function title(): string
    {
        return 'Debt Report';
    }

    public function styles(Worksheet $sheet)
    {
        // Style header rows
        $sheet->mergeCells('A1:L1');
        $sheet->mergeCells('A2:L2');
        $sheet->mergeCells('A3:L3');

        // Total Rows (Header = 5 rows) + Data Items + 1 Totals Row
        // Header occupies rows 1, 2, 3, 4, 5. Data starts at 6.
        $totalsRow = 5 + count($this->reportData) + 1;
        $lastRow = $sheet->getHighestRow();

        // Summary starts after Totals Row + 1 Blank Row
        $summaryStartRow = $totalsRow + 2;

        // Bold labels for summary (Column A)
        // Ensure we don't style if there is no summary (should not happen, but safe check)
        if ($summaryStartRow <= $lastRow) {
            $sheet->getStyle('A' . $summaryStartRow . ':A' . $lastRow)->getFont()->setBold(true);
            // Align summary values left
            $sheet->getStyle('B' . $summaryStartRow . ':E' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        // Alignment for money columns (G to L)
        // From Row 6 to Totals Row
        $sheet->getStyle('G6:L' . $totalsRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Color for Paid columns (I, J) - Green
        $sheet->getStyle('I6:J' . $totalsRow)->getFont()->getColor()->setARGB('16A34A');

        // Color for Debt columns (K, L) - Red
        $sheet->getStyle('K6:L' . $totalsRow)->getFont()->getColor()->setARGB('DC2626');

        // Style Totals Row
        $sheet->getStyle('A' . $totalsRow . ':L' . $totalsRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0F0']],
        ]);

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
            'A' => 6,
            'B' => 12,
            'C' => 15,
            'D' => 15,
            'E' => 25,
            'F' => 15,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 15,
            'K' => 15,
            'L' => 15,
        ];
    }
}
