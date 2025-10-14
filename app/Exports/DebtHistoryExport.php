<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DebtHistoryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $payments;

    public function __construct($payments)
    {
        $this->payments = $payments;
    }

    public function collection()
    {
        return $this->payments;
    }

    public function headings(): array
    {
        return [
            'Ngày TT',
            'Mã HĐ',
            'Khách hàng',
            'SĐT',
            'Tổng tiền',
            'Đã trả',
            'Còn nợ',
            'Trạng thái TT'
        ];
    }

    public function map($payment): array
    {
        // Tính số nợ còn lại SAU khi thanh toán này
        $paidUpToNow = $payment->sale->payments()
            ->where('id', '<=', $payment->id)
            ->sum('amount');
        $remainingDebt = $payment->sale->total_vnd - $paidUpToNow;

        // Tính trạng thái tại thời điểm đó
        $paidAtThisTime = $paidUpToNow;
        $totalAmount = $payment->sale->total_vnd;
        
        if ($paidAtThisTime >= $totalAmount) {
            $statusText = 'Đã Thanh Toán';
        } elseif ($paidAtThisTime > 0) {
            $statusText = 'Thanh Toán một phần';
        } else {
            $statusText = 'Chưa Thanh Toán';
        }

        return [
            $payment->payment_date->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y'),
            $payment->sale->invoice_code,
            $payment->sale->customer->name,
            $payment->sale->customer->phone ?? '-',
            number_format($payment->sale->total_vnd, 0, ',', '.') . 'đ',
            number_format($payment->amount, 0, ',', '.') . 'đ',
            number_format($remainingDebt, 0, ',', '.') . 'đ',
            $statusText
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
            'A' => 15,
            'B' => 15,
            'C' => 25,
            'D' => 15,
            'E' => 18,
            'F' => 18,
            'G' => 18,
            'H' => 25,
        ];
    }
}
