<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    protected $sales;
    protected $title;

    public function __construct($sales, $title = 'Danh sách bán hàng')
    {
        $this->sales = $sales;
        $this->title = $title;
    }

    public function collection()
    {
        return $this->sales;
    }

    public function headings(): array
    {
        return [
            'STT',
            'Mã HĐ',
            'Showroom',
            'Khách hàng',
            'Ngày bán',
            'Tổng tiền (USD)',
            'Tổng tiền (VND)',
            'Đã trả (USD)',
            'Đã trả (VND)',
            'Còn nợ (USD)',
            'Còn nợ (VND)',
            'Trạng thái TT',
            'Trạng thái HĐ',
            'Người bán',
        ];
    }

    public function map($sale): array
    {
        static $index = 0;
        $index++;

        return [
            $index,
            $sale->invoice_code,
            $sale->showroom->name ?? '',
            $sale->customer->name ?? 'Khách lẻ',
            $sale->sale_date->format('d/m/Y'),
            number_format($sale->total_usd, 2),
            number_format($sale->total_vnd, 0),
            number_format($sale->paid_usd, 2),
            number_format($sale->paid_amount, 0),
            number_format($sale->debt_usd, 2),
            number_format($sale->debt_amount, 0),
            $this->getPaymentStatus($sale->payment_status),
            $this->getSaleStatus($sale->sale_status),
            $sale->user->name ?? '',
        ];
    }

    public function title(): string
    {
        return $this->title;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function getPaymentStatus($status)
    {
        return match($status) {
            'paid' => 'Đã thanh toán',
            'partial' => 'Thanh toán 1 phần',
            'unpaid' => 'Chưa thanh toán',
            default => $status,
        };
    }

    private function getSaleStatus($status)
    {
        return match($status) {
            'pending' => 'Chờ duyệt',
            'completed' => 'Đã duyệt',
            'cancelled' => 'Đã hủy',
            default => $status,
        };
    }
}
