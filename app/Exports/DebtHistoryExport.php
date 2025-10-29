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
            'Giờ TT',
            'Mã HĐ',
            'Khách hàng',
            'SĐT',
            'Tổng tiền',
            'Đã trả',
            'PT Thanh toán',
            'Loại giao dịch',
            'Còn nợ',
            'Trạng thái TT'
        ];
    }

    public function map($payment): array
    {
        // Kiểm tra nếu sale đã bị hủy
        $isCancelled = $payment->sale->sale_status === 'cancelled';
        
        if ($isCancelled) {
            $remainingDebt = 0;
            $statusText = 'Đã hủy';
        } else {
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
        }

        // Payment method text
        $paymentMethodText = match($payment->payment_method) {
            'cash' => 'Tiền mặt',
            'bank_transfer' => 'Chuyển khoản',
            'card' => 'Thẻ',
            default => 'Khác'
        };

        // Transaction type text
        $transactionType = $payment->transaction_type ?? 'sale_payment';
        $transactionTypeText = match($transactionType) {
            'sale_payment' => 'Thanh toán bán hàng',
            'return' => 'Trả hàng',
            'exchange' => 'Đổi hàng',
            default => 'Thanh toán bán hàng'
        };

        $paymentDateTime = $payment->payment_date->timezone('Asia/Ho_Chi_Minh');
        $timeStr = $paymentDateTime->format('H:i:s');
        // Chỉ hiển thị giờ nếu không phải 00:00:00 hoặc 07:00:00 (data cũ từ UTC)
        $hasTime = $timeStr !== '00:00:00' && $timeStr !== '07:00:00';
        
        return [
            $paymentDateTime->format('d/m/Y'),
            $hasTime ? $paymentDateTime->format('H:i') : '-',
            $payment->sale->invoice_code,
            $payment->sale->customer->name,
            $payment->sale->customer->phone ?? '-',
            number_format($payment->sale->total_vnd, 0, ',', '.') . 'đ',
            number_format($payment->amount, 0, ',', '.') . 'đ',
            $paymentMethodText,
            $transactionTypeText,
            $isCancelled ? '(Đã hủy)' : number_format($remainingDebt, 0, ',', '.') . 'đ',
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
            'A' => 12,  // Ngày TT
            'B' => 8,   // Giờ TT
            'C' => 15,  // Mã HĐ
            'D' => 25,  // Khách hàng
            'E' => 15,  // SĐT
            'F' => 18,  // Tổng tiền
            'G' => 18,  // Đã trả
            'H' => 15,  // PT Thanh toán
            'I' => 22,  // Loại giao dịch
            'J' => 18,  // Còn nợ
            'K' => 25,  // Trạng thái TT
        ];
    }
}
