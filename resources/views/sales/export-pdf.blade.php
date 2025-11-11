<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 15mm;
            size: A4 landscape;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        h1 {
            font-size: 16px;
            margin: 0 0 10px 0;
            font-weight: bold;
        }
        .info {
            font-size: 9px;
            margin: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #333;
            padding: 5px 4px;
            text-align: left;
            font-size: 8px;
        }
        th {
            background-color: #e0e0e0;
            font-weight: bold;
            font-size: 8px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .summary {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .status-completed {
            color: #059669;
        }
        .status-pending {
            color: #d97706;
        }
        .status-cancelled {
            color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <div class="info">Ngày xuất: {{ date('d/m/Y H:i') }} | Tổng số: {{ $sales->count() }} hóa đơn</div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 3%;">STT</th>
                <th style="width: 10%;">Mã HĐ</th>
                <th style="width: 12%;">Showroom</th>
                <th style="width: 15%;">Khách hàng</th>
                <th class="text-center" style="width: 8%;">Ngày bán</th>
                <th class="text-right" style="width: 10%;">Tổng USD</th>
                <th class="text-right" style="width: 11%;">Tổng VND</th>
                <th class="text-right" style="width: 11%;">Đã trả</th>
                <th class="text-right" style="width: 11%;">Còn nợ</th>
                <th class="text-center" style="width: 9%;">TT Thanh toán</th>
                <th class="text-center" style="width: 8%;">TT Hóa đơn</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $index => $sale)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $sale->invoice_code }}</td>
                <td>{{ $sale->showroom->name ?? '' }}</td>
                <td>{{ $sale->customer->name ?? 'Khách lẻ' }}</td>
                <td class="text-center">{{ $sale->sale_date->format('d/m/Y') }}</td>
                <td class="text-right">${{ number_format($sale->total_usd, 2) }}</td>
                <td class="text-right">{{ number_format($sale->total_vnd) }}đ</td>
                <td class="text-right">{{ number_format($sale->paid_amount) }}đ</td>
                <td class="text-right">{{ number_format($sale->debt_amount) }}đ</td>
                <td class="text-center">
                    @if($sale->payment_status === 'paid') 
                        <span class="status-completed">Đã TT</span>
                    @elseif($sale->payment_status === 'partial') 
                        <span class="status-pending">TT 1 phần</span>
                    @else 
                        <span class="status-cancelled">Chưa TT</span>
                    @endif
                </td>
                <td class="text-center">
                    @if($sale->sale_status === 'completed') 
                        <span class="status-completed">Đã duyệt</span>
                    @elseif($sale->sale_status === 'pending') 
                        <span class="status-pending">Chờ duyệt</span>
                    @else 
                        <span class="status-cancelled">Đã hủy</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="summary">
                <td colspan="5" class="text-right">Tổng cộng:</td>
                <td class="text-right">${{ number_format($sales->sum('total_usd'), 2) }}</td>
                <td class="text-right">{{ number_format($sales->sum('total_vnd')) }}đ</td>
                <td class="text-right">{{ number_format($sales->sum('paid_amount')) }}đ</td>
                <td class="text-right">{{ number_format($sales->sum('debt_amount')) }}đ</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
