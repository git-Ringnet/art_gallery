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

class MonthlySalesExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
                $item['sale_date'],
                $item['invoice_code'],
                $item['id_code'],
                $item['customer_name'],
                $item['item_description'],
                $item['item_count'],
                number_format($item['total_usd'], 2),
                number_format($item['total_vnd'], 0),
                number_format($item['actual_paid_usd'], 2),
                number_format($item['actual_paid_vnd'], 0),
                number_format($item['debt_usd'], 2),
                number_format($item['debt_vnd'], 0),
                $item['showroom'],
                $item['employee'],
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
            $this->totals['totalItems'],
            '$' . number_format($this->totals['totalUsd'], 2),
            number_format($this->totals['totalVnd'], 0),
            '$' . number_format($this->totals['totalPaidUsd'] ?? 0, 2),
            number_format($this->totals['totalPaidVnd'], 0),
            '$' . number_format($this->totals['totalDebtUsd'] ?? 0, 2),
            number_format($this->totals['totalDebtVnd'], 0),
            '',
            '',
        ];

        // Add summary
        $rows[] = [];

        // Use the grand totals which are already calculated (with or without exchange rate)
        // If exchange rate > 1, grand totals are fully in VND, so we can just show them.
        // But if exchange rate was NOT applied, the controller returns the raw totals.
        // Wait, the View shows breakdown if Rate <= 1, and Converted if Rate > 1.
        // The controller's getMonthlySalesData calculates grand totals in VND if rate > 1.
        // If rate <= 1, grandTotalVnd = totalVnd (just the VND part).
        // To match the View, we should check if exchange rate was applied or handle the display accordingly.
        // But here we rely on what was passed.

        // Actually, let's keep it simple: Show what the controller computed.
        $exchangeRate = $this->metadata['exchangeRate'] ?? 1;

        if ($exchangeRate > 1) {
            // Converted to VND
            $rows[] = ['Grand Total (VND):', 'VND ' . number_format($this->totals['grandTotalVnd'], 0)];
        } else {
            // Separate currencies
            $rows[] = ['Total Revenue:', 'USD ' . number_format($this->totals['totalUsd'], 2) . ' - VND ' . number_format($this->totals['totalVnd'], 0)];
        }

        if ($exchangeRate > 1) {
            $rows[] = ['Total Paid (VND):', 'VND ' . number_format($this->totals['grandPaidVnd'], 0)];
        } else {
            $paidStr = '';
            $hasVnd = ($this->totals['totalPaidVnd'] ?? 0) > 0;
            $hasUsd = ($this->totals['totalPaidUsd'] ?? 0) > 0;
            $isZero = !$hasVnd && !$hasUsd;

            if ($hasVnd)
                $paidStr .= 'VND ' . number_format($this->totals['totalPaidVnd'], 0);
            if ($hasVnd && $hasUsd)
                $paidStr .= ' + ';
            if ($hasUsd)
                $paidStr .= 'USD ' . number_format($this->totals['totalPaidUsd'], 2);
            if ($isZero)
                $paidStr = 'VND 0';

            $rows[] = ['Total Paid:', $paidStr];
        }

        if ($exchangeRate > 1) {
            $rows[] = ['Total Debt (VND):', 'VND ' . number_format($this->totals['grandDebtVnd'], 0)];
        } else {
            $debtStr = '';
            $hasVnd = ($this->totals['totalDebtVnd'] ?? 0) > 0;
            $hasUsd = ($this->totals['totalDebtUsd'] ?? 0) > 0;
            $isZero = !$hasVnd && !$hasUsd;

            if ($hasVnd)
                $debtStr .= 'VND ' . number_format($this->totals['totalDebtVnd'], 0);
            if ($hasVnd && $hasUsd)
                $debtStr .= ' + ';
            if ($hasUsd)
                $debtStr .= 'USD ' . number_format($this->totals['totalDebtUsd'], 2);
            if ($isZero)
                $debtStr = 'VND 0';

            $rows[] = ['Total Debt:', $debtStr];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            ['Monthly Sales Report'],
            ['Period: ' . ($this->metadata['fromDate'] ?? '') . ' - ' . ($this->metadata['toDate'] ?? '')],
            ['Showroom: ' . ($this->metadata['showroom'] ?? 'All')],
            [],
            [
                'No.',
                'Sale Date',
                'Invoice',
                'ID Code',
                'Customer',
                'Description',
                'Items',
                'Total USD',
                'Total VND',
                'Paid USD',
                'Paid VND',
                'Debt USD',
                'Debt VND',
                'Showroom',
                'Employee',
            ],
        ];
    }

    public function title(): string
    {
        return 'Monthly Sales';
    }

    public function styles(Worksheet $sheet)
    {
        // Style header rows
        $sheet->mergeCells('A1:O1');
        $sheet->mergeCells('A2:O2');
        $sheet->mergeCells('A3:O3');

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
            'B' => 12,
            'C' => 15,
            'D' => 15,
            'E' => 20,
            'F' => 30,
            'G' => 10,
            'H' => 15,
            'I' => 15,
            'J' => 15,
            'K' => 15,
            'L' => 15,
            'M' => 15,
            'N' => 20,
            'O' => 20,
        ];
    }
}
