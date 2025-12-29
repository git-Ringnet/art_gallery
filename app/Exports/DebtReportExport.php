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
            $rows[] = [
                $index++,
                $item['sale_date'],
                $item['invoice_code'],
                $item['id_code'],
                $item['customer_name'],
                $item['customer_phone'],
                number_format($item['total_usd'], 2),
                number_format($item['total_vnd'], 0),
                number_format($item['paid_usd'], 2),
                number_format($item['paid_vnd'], 0),
                number_format($item['debt_usd'], 2),
                number_format($item['debt_vnd'], 0),
                $item['showroom'],
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
            '$' . number_format($this->totals['totalSaleUsd'], 2),
            number_format($this->totals['totalSaleVnd'], 0),
            '$' . number_format($this->totals['totalPaidUsd'], 2),
            number_format($this->totals['totalPaidVnd'], 0),
            '$' . number_format($this->totals['totalDebtUsd'], 2),
            number_format($this->totals['totalDebtVnd'], 0),
            '',
        ];

        // Add summary
        $rows[] = [];
        $rows[] = ['Grand Total (VND):', 'VND ' . number_format($this->totals['grandTotalVnd'], 0)];
        $rows[] = ['Total Paid (VND):', 'VND ' . number_format($this->totals['grandPaidVnd'], 0)];
        $rows[] = ['Total Debt (VND):', 'VND ' . number_format($this->totals['grandDebtVnd'], 0)];

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
                'Showroom',
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
        $sheet->mergeCells('A1:M1');
        $sheet->mergeCells('A2:M2');
        $sheet->mergeCells('A3:M3');

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
            'F' => 15,
            'G' => 12,
            'H' => 15,
            'I' => 12,
            'J' => 15,
            'K' => 12,
            'L' => 15,
            'M' => 20,
        ];
    }
}
