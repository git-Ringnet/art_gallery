<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Debt Report</title>
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
            <strong>{{ $selectedShowroom ? $selectedShowroom->name : 'Ben Thanh Art Gallery' }}</strong><br>
            @if($selectedShowroom)
                {{ $selectedShowroom->address }}<br>
                Tel: {{ $selectedShowroom->phone }}
            @else
                07 Nguyen Thiep - Dist.1, HCMC<br>
                Tel: (84-8) 3823 3001 - 3823 8101
            @endif
        </div>
        <div class="header-right">
            <strong>Page 1</strong><br>
            Date: {{ now()->format('d/m/Y') }}
        </div>
    </div>

    <div class="title">
        <h2>Debt Report</h2>
        @if($reportType == 'cumulative')
            <div>Cumulative to {{ $toDate->format('d/m/Y') }}</div>
        @else
            <div>{{ $fromDate->format('d/m/Y') }} - {{ $toDate->format('d/m/Y') }}</div>
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
                <th>Phone</th>
                <th class="text-right">Total</th>
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
                    <td>{{ $item['customer_phone'] }}</td>
                    <td class="text-right">
                        @if($item['total_usd'] > 0) ${{ number_format($item['total_usd'], 2) }} @endif
                        @if($item['total_vnd'] > 0) {{ number_format($item['total_vnd'], 0) }}đ @endif
                    </td>
                    <td class="text-right">
                        @if($item['is_usd_only'] ?? false)
                            ${{ number_format($item['paid_usd'], 2) }}
                        @elseif($item['is_vnd_only'] ?? false)
                            {{ number_format($item['paid_vnd'], 0) }}đ
                        @else
                            @if($item['paid_usd'] > 0) ${{ number_format($item['paid_usd'], 2) }} @endif
                            @if($item['paid_vnd'] > 0) {{ number_format($item['paid_vnd'], 0) }}đ @endif
                        @endif
                    </td>
                    <td class="text-right">
                        @if($item['is_usd_only'] ?? false)
                            ${{ number_format($item['debt_usd'], 2) }}
                        @elseif($item['is_vnd_only'] ?? false)
                            {{ number_format($item['debt_vnd'], 0) }}đ
                        @else
                            @if($item['debt_usd'] > 0) ${{ number_format($item['debt_usd'], 2) }} @endif
                            @if($item['debt_vnd'] > 0) {{ number_format($item['debt_vnd'], 0) }}đ @endif
                        @endif
                    </td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="6">GRAND TOTAL</td>
                <td class="text-right">
                    @if($totalSaleUsd > 0) ${{ number_format($totalSaleUsd, 2) }} @endif
                    @if($totalSaleVnd > 0) {{ number_format($totalSaleVnd, 0) }}đ @endif
                </td>
                <td class="text-right">
                    @if($totalPaidUsd > 0) ${{ number_format($totalPaidUsd, 2) }} @endif
                    @if($totalPaidVnd > 0) {{ number_format($totalPaidVnd, 0) }}đ @endif
                    @if($totalPaidUsd == 0 && $totalPaidVnd == 0) $0.00 @endif
                </td>
                <td class="text-right">
                    @if($totalDebtUsd > 0) ${{ number_format($totalDebtUsd, 2) }} @endif
                    @if($totalDebtVnd > 0) {{ number_format($totalDebtVnd, 0) }}đ @endif
                </td>
            </tr>
        </tbody>
    </table>

    <div class="summary">
        <p><strong>Total Sales:</strong>
            @if($totalSaleUsd > 0) ${{ number_format($totalSaleUsd, 2) }} @endif
            @if($totalSaleUsd > 0 && $totalSaleVnd > 0) + @endif
            @if($totalSaleVnd > 0) {{ number_format($totalSaleVnd, 0) }}đ @endif
        </p>
        <p><strong>Total Paid:</strong>
            @if($totalPaidUsd > 0) ${{ number_format($totalPaidUsd, 2) }} @endif
            @if($totalPaidUsd > 0 && $totalPaidVnd > 0) + @endif
            @if($totalPaidVnd > 0) {{ number_format($totalPaidVnd, 0) }}đ @endif
            @if($totalPaidUsd == 0 && $totalPaidVnd == 0) $0.00 @endif
        </p>
        <p
            style="border-top: 2px solid #000; border-bottom: 2px double #000; padding: 6px 0; margin-top: 6px; font-weight: bold;">
            <strong>TOTAL DEBT:</strong>
            @if($totalDebtUsd > 0) ${{ number_format($totalDebtUsd, 2) }} @endif
            @if($totalDebtUsd > 0 && $totalDebtVnd > 0) + @endif
            @if($totalDebtVnd > 0) {{ number_format($totalDebtVnd, 0) }}đ @endif
        </p>
    </div>
</body>

</html>