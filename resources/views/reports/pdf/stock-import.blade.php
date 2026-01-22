<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Stock Import Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
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
            padding: 3px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 7px;
        }

        td {
            font-size: 7px;
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
            <strong>Ben Thanh Art Gallery</strong><br>
            07 Nguyen Thiep - Dist.1, HCMC<br>
            Tel: (84-8) 3823 3001 - 3823 8101
        </div>
        <div class="header-right">
            <strong>Page 1</strong><br>
            Date: {{ now()->format('d/m/Y') }}
        </div>
    </div>

    <div class="title">
        <h2>Stock Import Report</h2>
        <div>{{ $fromDate->format('d/m/Y') }} - {{ $toDate->format('d/m/Y') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Import Date</th>
                <th>Code</th>
                <th>Name</th>
                <th>Artist</th>
                <th>Material</th>
                <th>Dimensions</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Price (USD)</th>
                <th class="text-right">Price (VND)</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item['import_date'] }}</td>
                    <td>{{ $item['code'] }}</td>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['artist'] }}</td>
                    <td>{{ $item['material'] }}</td>
                    <td>{{ $item['dimensions'] }}</td>
                    <td class="text-center">{{ $item['quantity'] }}</td>
                    <td class="text-right">{{ number_format($item['price_usd'], 2) }}</td>
                    <td class="text-right">{{ number_format($item['price_vnd'], 0) }}</td>
                    <td class="text-center">{{ $item['status'] }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="7">GRAND TOTAL</td>
                <td class="text-center">{{ $totalQuantity }}</td>
                <td class="text-right">{{ number_format($totalPriceUsd, 2) }}</td>
                <td class="text-right">{{ number_format($totalPriceVnd, 0) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="summary">
        <p><strong>Total Paintings:</strong> {{ $totalQuantity }}</p>
        @if($totalPriceUsd > 0 && $exchangeRate <= 1)
            <p><strong>Total Value (USD):</strong> ${{ number_format($totalPriceUsd, 2) }}</p>
        @endif
        <p
            style="border-top: 2px solid #000; border-bottom: 2px double #000; padding: 6px 0; margin-top: 6px; font-weight: bold;">
            <strong>TOTAL VALUE (VND):</strong> {{ number_format($grandTotalVnd, 0) }}đ
        </p>
    </div>
</body>

</html>