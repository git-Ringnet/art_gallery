<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lịch sử Công nợ</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #3b82f6;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .status-paid {
            background-color: #d1fae5;
            color: #065f46;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        .status-partial {
            background-color: #fef3c7;
            color: #92400e;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        .status-unpaid {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <h2>LỊCH SỬ CÔNG NỢ</h2>
    <p style="text-align: center; margin-bottom: 20px;">Ngày xuất: {{ date('d/m/Y H:i:s') }}</p>
    
    <table>
        <thead>
            <tr>
                <th>Ngày TT</th>
                <th>Giờ</th>
                <th>Mã HĐ</th>
                <th>Khách hàng</th>
                <th>SĐT</th>
                <th class="text-right">Tổng tiền</th>
                <th class="text-right">Đã trả</th>
                <th class="text-center">PT TT</th>
                <th class="text-right">Còn nợ</th>
                <th class="text-center">Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            @php
                $paymentDateTime = $payment->payment_date->timezone('Asia/Ho_Chi_Minh');
                $timeStr = $paymentDateTime->format('H:i:s');
                // Chỉ hiển thị giờ nếu không phải 00:00:00 hoặc 07:00:00 (data cũ từ UTC)
                $hasTime = $timeStr !== '00:00:00' && $timeStr !== '07:00:00';
            @endphp
            <tr>
                <td>{{ $paymentDateTime->format('d/m/Y') }}</td>
                <td>{{ $hasTime ? $paymentDateTime->format('H:i') : '-' }}</td>
                <td>{{ $payment->sale->invoice_code }}</td>
                <td>{{ $payment->sale->customer->name }}</td>
                <td>{{ $payment->sale->customer->phone ?? '-' }}</td>
                <td class="text-right">{{ number_format($payment->sale->total_vnd, 0, ',', '.') }}đ</td>
                <td class="text-right">{{ number_format($payment->amount, 0, ',', '.') }}đ</td>
                <td class="text-center">
                    @if($payment->payment_method === 'cash')
                        TM
                    @elseif($payment->payment_method === 'bank_transfer')
                        CK
                    @else
                        Thẻ
                    @endif
                </td>
                <td class="text-right">
                    @php
                        $paidUpToNow = $payment->sale->payments()
                            ->where('id', '<=', $payment->id)
                            ->sum('amount');
                        $remainingDebt = $payment->sale->total_vnd - $paidUpToNow;
                    @endphp
                    {{ number_format($remainingDebt, 0, ',', '.') }}đ
                </td>
                <td class="text-center">
                    @php
                        $paidAtThisTime = $paidUpToNow;
                        $totalAmount = $payment->sale->total_vnd;
                        
                        if ($paidAtThisTime >= $totalAmount) {
                            $statusClass = 'status-paid';
                            $statusText = 'Đã TT';
                        } elseif ($paidAtThisTime > 0) {
                            $statusClass = 'status-partial';
                            $statusText = 'TT 1 phần';
                        } else {
                            $statusClass = 'status-unpaid';
                            $statusText = 'Chưa TT';
                        }
                    @endphp
                    <span class="{{ $statusClass }}">{{ $statusText }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        <p>Tổng số giao dịch: {{ $payments->count() }}</p>
    </div>
</body>
</html>
