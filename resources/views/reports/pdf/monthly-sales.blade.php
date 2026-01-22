<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Monthly Sales Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 15px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 8px;
        }

        .header-left strong {
            font-size: 10px;
        }

        .title {
            text-align: center;
            margin-bottom: 15px;
        }

        .title h2 {
            font-size: 14px;
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 8px;
        }

        td {
            font-size: 8px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary {
            margin-top: 10px;
            font-size: 9px;
        }

        .summary p {
            margin: 3px 0;
        }

        .total-row {
            background-color: #e0e0e0;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="header-left">
            <strong>{{ $selectedShowroom ? $selectedShowroom->name : 'All Showrooms' }}</strong><br>
            @if($selectedShowroom)
                {{ $selectedShowroom->address }}<br>
                Tel: {{ $selectedShowroom->phone }}
            @endif
        </div>
        <div class="header-right">
            <strong>Page 1</strong><br>
            Date: {{ now()->format('d/m/Y') }}
        </div>
    </div>

    <div class="title">
        <h2>Monthly Sales Report</h2>
        <div>{{ $fromDate->format('d/m/Y') }} - {{ $toDate->format('d/m/Y') }}</div>
        @if($selectedShowroom)
            <div>Showroom: {{ $selectedShowroom->name }}</div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Date</th>
                <th>Invoice</th>
                <th>ID Code</th>
                <th>Customer</th>
                <th class="text-right">Total USD</th>
                <th class="text-right">Total VND</th>
                <th class="text-right">Paid</th>
                <th class="text-right">Debt</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item['sale_date'] }}</td>
                    <td>{{ $item['invoice_code'] }}</td>
                    <td>{{ $item['id_code'] }}</td>
                    <td>{{ $item['customer_name'] }}</td>
                    <td class="text-right">
                        @if($item['total_usd'] > 0)
                            ${{ number_format($item['total_usd'], 2) }}
                        @endif
                    </td>
                    <td class="text-right">
                        @if($item['total_vnd'] > 0)
                            {{ number_format($item['total_vnd'], 0) }}đ
                        @endif
                    </td>
                    <td class="text-right">
                        @if(($item['actual_paid_vnd'] ?? 0) > 0)
                            {{ number_format($item['actual_paid_vnd'], 0) }}đ
                        @endif
                        @if(($item['actual_paid_vnd'] ?? 0) > 0 && ($item['actual_paid_usd'] ?? 0) > 0)
                            +
                        @endif
                        @if(($item['actual_paid_usd'] ?? 0) > 0)
                            ${{ number_format($item['actual_paid_usd'], 2) }}
                        @endif
                        @if(($item['actual_paid_vnd'] ?? 0) <= 0 && ($item['actual_paid_usd'] ?? 0) <= 0)
                            @if(($item['is_usd_only'] ?? false) && ($item['paid_usd'] ?? 0) > 0)
                                ${{ number_format($item['paid_usd'], 2) }}
                            @elseif(($item['paid_vnd'] ?? 0) > 0)
                                {{ number_format($item['paid_vnd'], 0) }}đ
                            @endif
                        @endif
                    </td>
                    <td class="text-right">
                        @if(($item['debt_vnd'] ?? 0) > 0)
                            {{ number_format($item['debt_vnd'], 0) }}đ
                        @endif
                        @if(($item['debt_vnd'] ?? 0) > 0 && ($item['debt_usd'] ?? 0) > 0)
                            +
                        @endif
                        @if(($item['debt_usd'] ?? 0) > 0)
                            ${{ number_format($item['debt_usd'], 2) }}
                        @endif
                    </td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5">GRAND TOTAL</td>
                <td class="text-right">
                    @if($totalUsd > 0)
                        ${{ number_format($totalUsd, 2) }}
                    @endif
                </td>
                <td class="text-right">
                    @if($totalVnd > 0)
                        {{ number_format($totalVnd, 0) }}đ
                    @endif
                </td>
                <td class="text-right">
                    @if($exchangeRate > 1)
                        {{ number_format($grandPaidVnd, 0) }}đ
                    @else
                        @if(($totalPaidVnd ?? 0) > 0)
                            {{ number_format($totalPaidVnd, 0) }}đ
                        @endif
                        @if(($totalPaidVnd ?? 0) > 0 && ($totalPaidUsd ?? 0) > 0)
                            +
                        @endif
                        @if(($totalPaidUsd ?? 0) > 0)
                            ${{ number_format($totalPaidUsd, 2) }}
                        @endif
                        @if(($totalPaidVnd ?? 0) <= 0 && ($totalPaidUsd ?? 0) <= 0)
                            0đ
                        @endif
                    @endif
                </td>
                <td class="text-right">
                    @if($exchangeRate > 1)
                        {{ number_format($grandDebtVnd, 0) }}đ
                    @else
                        @if(($totalDebtVnd ?? 0) > 0)
                            {{ number_format($totalDebtVnd, 0) }}đ
                        @endif
                        @if(($totalDebtVnd ?? 0) > 0 && ($totalDebtUsd ?? 0) > 0)
                            +
                        @endif
                        @if(($totalDebtUsd ?? 0) > 0)
                            ${{ number_format($totalDebtUsd, 2) }}
                        @endif
                        @if(($totalDebtVnd ?? 0) <= 0 && ($totalDebtUsd ?? 0) <= 0)
                            0đ
                        @endif
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    <div class="summary">
        <div class="flex justify-between items-center py-2 border-b border-gray-200">
            <span class="text-gray-700 font-medium">Tổng doanh thu:</span>
            <span class="text-lg font-bold text-blue-700 text-right">
                @if($exchangeRate > 1)
                    VND: {{ number_format($grandTotalVnd, 0) }}
                @else
                    @if($totalUsd > 0)
                        USD: ${{ number_format($totalUsd, 2) }}
                    @endif
                    @if($totalUsd > 0 && $totalVnd > 0) - @endif
                    @if($totalVnd > 0)
                        VND: {{ number_format($totalVnd, 0) }}đ
                    @endif
                @endif
            </span>
        </div>
        <div class="flex justify-between items-center py-2 border-b border-gray-200">
            <span class="text-gray-700 font-medium">Đã thu:</span>
            <span class="text-lg font-bold text-green-700">
                @if($exchangeRate > 1)
                    VND {{ number_format($grandPaidVnd, 0) }}
                @else
                    @if(($totalPaidVnd ?? 0) > 0)
                        VND {{ number_format($totalPaidVnd, 0) }}
                    @endif
                    @if(($totalPaidVnd ?? 0) > 0 && ($totalPaidUsd ?? 0) > 0)
                        +
                    @endif
                    @if(($totalPaidUsd ?? 0) > 0)
                        USD {{ number_format($totalPaidUsd, 2) }}
                    @endif
                    @if(($totalPaidVnd ?? 0) <= 0 && ($totalPaidUsd ?? 0) <= 0)
                        VND 0
                    @endif
                @endif
            </span>
        </div>
        <div class="flex justify-between items-center py-3 bg-gradient-to-r from-red-100 to-orange-100 rounded-lg px-4">
            <span class="text-gray-800 font-bold text-lg">Còn nợ:</span>
            <span class="text-xl font-bold text-red-700">
                @if($exchangeRate > 1)
                    VND {{ number_format($grandDebtVnd, 0) }}
                @else
                    @if(($totalDebtVnd ?? 0) > 0)
                        VND {{ number_format($totalDebtVnd, 0) }}
                    @endif
                    @if(($totalDebtVnd ?? 0) > 0 && ($totalDebtUsd ?? 0) > 0)
                        +
                    @endif
                    @if(($totalDebtUsd ?? 0) > 0)
                        USD {{ number_format($totalDebtUsd, 2) }}
                    @endif
                    @if(($totalDebtVnd ?? 0) <= 0 && ($totalDebtUsd ?? 0) <= 0)
                        VND 0
                    @endif
                @endif
            </span>
        </div>
    </div>
</body>

</html>