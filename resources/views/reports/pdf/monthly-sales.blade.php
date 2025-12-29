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
                <th>Phone</th>
                <th class="text-right">Total USD</th>
                <th class="text-right">Total VND</th>
                <th class="text-right">Paid VND</th>
                <th class="text-right">Debt VND</th>
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
                    <td>{{ $item['customer_phone'] ?? '' }}</td>
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
                        @if(isset($item['paid_vnd']) && $item['paid_vnd'] > 0)
                            {{ number_format($item['paid_vnd'], 0) }}đ
                        @endif
                    </td>
                    <td class="text-right">
                        @if(isset($item['debt_vnd']) && $item['debt_vnd'] > 0)
                            {{ number_format($item['debt_vnd'], 0) }}đ
                        @endif
                    </td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="6">GRAND TOTAL</td>
                <td class="text-right">
                    @if($totalUsd > 0)
                        ${{ number_format($totalUsd, 2) }}
                    @endif
                </td>
                <td class="text-right">
                    @if($exchangeRate > 1)
                        {{ number_format($grandTotalVnd, 0) }}đ
                        <div style="font-size: 7px; color: #666;">
                            @if($totalUsd > 0) (${{ number_format($totalUsd, 2) }}) @endif
                            @if($totalUsd > 0 && $totalVnd > 0) + @endif
                            @if($totalVnd > 0) ({{ number_format($totalVnd, 0) }}đ) @endif
                        </div>
                    @else
                        @if($totalUsd > 0) ${{ number_format($totalUsd, 2) }} @endif
                        @if($totalUsd > 0 && $totalVnd > 0) <br> @endif
                        @if($totalVnd > 0) {{ number_format($totalVnd, 0) }}đ @endif
                    @endif
                </td>
                <td class="text-right">
                    @if($exchangeRate > 1)
                        {{ number_format($grandPaidVnd, 0) }}đ
                    @else
                        @if($totalPaidVnd > 0) {{ number_format($totalPaidVnd, 0) }}đ @endif
                    @endif
                </td>
                <td class="text-right">
                    @if($exchangeRate > 1)
                        {{ number_format($grandDebtVnd, 0) }}đ
                    @else
                        @if($totalDebtVnd > 0) {{ number_format($totalDebtVnd, 0) }}đ @endif
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    <div class="summary">
        <p><strong>Total Sales (USD):</strong> @if($totalUsd > 0) ${{ number_format($totalUsd, 2) }} @else $0.00 @endif
        </p>
        <p><strong>Total Sales (VND):</strong> @if($totalVnd > 0) {{ number_format($totalVnd, 0) }}đ @else 0đ @endif</p>
        <p style="border-top: 2px solid #000; border-bottom: 2px double #000; padding: 6px 0; margin-top: 6px;
            font-weight: bold;">
            <strong>Grand Total (VND):</strong>
            @if($exchangeRate > 1)
                {{ number_format($grandTotalVnd, 0) }}đ
            @else
                @if($totalVnd > 0) {{ number_format($totalVnd, 0) }}đ @else 0đ @endif
            @endif
        </p>
        <p><strong>Total Paid (VND):</strong>
            @if($exchangeRate > 1)
                {{ number_format($grandPaidVnd, 0) }}đ
            @else
                @if($totalPaidVnd > 0) {{ number_format($totalPaidVnd, 0) }}đ @else 0đ @endif
            @endif
        </p>
        <p><strong>Total Debt (VND):</strong>
            @if($exchangeRate > 1)
                {{ number_format($grandDebtVnd, 0) }}đ
            @else
                @if($totalDebtVnd > 0) {{ number_format($totalDebtVnd, 0) }}đ @else 0đ @endif
            @endif
        </p>
    </div>
</body>

</html>