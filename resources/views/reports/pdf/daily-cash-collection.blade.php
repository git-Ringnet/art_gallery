<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Daily Cash Collection Report</title>
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
        <h2>Daily Cash Collection Report</h2>
        <div>{{ $fromDate->format('d/m/Y') }} - {{ $toDate->format('d/m/Y') }}</div>
        @if($selectedShowroom)
            <div>Showroom: {{ $selectedShowroom->name }}</div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Invoice</th>
                <th>ID Code</th>
                <th>Customer</th>
                <th class="text-right">Adj. USD</th>
                <th class="text-right">Adj. VND</th>
                <th class="text-right">Collect USD</th>
                <th class="text-right">Collect VND</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item['invoice_code'] }}</td>
                    <td>{{ $item['id_code'] }}</td>
                    <td>{{ $item['customer_name'] }}</td>
                    <td class="text-right">
                        @if($item['adjustment_usd'] != 0)
                            @php
                                $val = $item['adjustment_usd'];
                                $formatted = $val == floor($val) ? number_format($val, 0) : number_format($val, 2);
                            @endphp
                            ${{ $formatted }}
                        @endif
                    </td>
                    <td class="text-right">
                        @if($item['adjustment_vnd'] != 0)
                            {{ number_format($item['adjustment_vnd'], 0) }}đ
                        @endif
                    </td>
                    <td class="text-right">
                        @if($item['collection_usd'] > 0)
                            @php
                                $val = $item['collection_usd'];
                                $formatted = $val == floor($val) ? number_format($val, 0) : number_format($val, 2);
                            @endphp
                            ${{ $formatted }}
                        @endif
                    </td>
                    <td class="text-right">
                        @if($item['collection_vnd'] > 0)
                            {{ number_format($item['collection_vnd'], 0) }}đ
                        @endif
                    </td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4">GRAND TOTAL</td>
                <td class="text-right">
                    @if($totalAdjustmentUsd != 0)
                        @php
                            $val = $totalAdjustmentUsd;
                            $formatted = $val == floor($val) ? number_format($val, 0) : number_format($val, 2);
                        @endphp
                        ${{ $formatted }}
                    @endif
                </td>
                <td class="text-right">
                    @if($totalAdjustmentVnd != 0)
                        {{ number_format($totalAdjustmentVnd, 0) }}đ
                    @endif
                </td>
                <td class="text-right">
                    @if($totalCollectionUsd > 0)
                        @php
                            $val = $totalCollectionUsd;
                            $formatted = $val == floor($val) ? number_format($val, 0) : number_format($val, 2);
                        @endphp
                        ${{ $formatted }}
                    @endif
                </td>
                <td class="text-right">
                    @if($totalCollectionVnd > 0)
                        {{ number_format($totalCollectionVnd, 0) }}đ
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    <div class="summary">
        <p><strong>Summary:</strong></p>
        @if(isset($exchangeRate) && $exchangeRate > 1)
            {{-- Combined Display (Rate > 1) --}}
            <p>
                Collection in CASH: {{ number_format($cashCollectionVnd, 0) }}đ
                @if(isset($cashCollectionUsd) && $cashCollectionUsd > 0)
                    @php
                        $val = $cashCollectionUsd;
                        $formatted = $val == floor($val) ? number_format($val, 0) : number_format($val, 2);
                    @endphp
                    (incl. USD ${{ $formatted }})
                @endif
            </p>
            <p>
                In Credit Card + Transfer: {{ number_format($cardCollectionVnd, 0) }}đ
                @if(isset($cardCollectionUsd) && $cardCollectionUsd > 0)
                    @php
                        $val = $cardCollectionUsd;
                        $formatted = $val == floor($val) ? number_format($val, 0) : number_format($val, 2);
                    @endphp
                    (incl. USD ${{ $formatted }})
                @endif
            </p>
            <p
                style="border-top: 2px solid #000; border-bottom: 2px double #000; padding: 6px 0; margin-top: 6px; font-weight: bold;">
                {{-- In Rate > 1 mode, cash/cardCollectionVnd already includes converted USD, so simple sum is correct --}}
                <strong>Grand Total: {{ number_format($cashCollectionVnd + $cardCollectionVnd, 0) }}đ</strong>
            </p>
        @else
            {{-- Separated Display (Rate = 1 or not provided) --}}
            <p>
                Collection in CASH: {{ number_format($cashCollectionVnd, 0) }}đ
                @if(isset($cashCollectionUsd) && $cashCollectionUsd > 0)
                    @php
                        $val = $cashCollectionUsd;
                        $formatted = $val == floor($val) ? number_format($val, 0) : number_format($val, 2);
                    @endphp
                    + USD ${{ $formatted }}
                @endif
            </p>
            <p>
                In Credit Card + Transfer: {{ number_format($cardCollectionVnd, 0) }}đ
                @if(isset($cardCollectionUsd) && $cardCollectionUsd > 0)
                    @php
                        $val = $cardCollectionUsd;
                        $formatted = $val == floor($val) ? number_format($val, 0) : number_format($val, 2);
                    @endphp
                    + USD ${{ $formatted }}
                @endif
            </p>

            <p
                style="border-top: 2px solid #000; border-bottom: 2px double #000; padding: 6px 0; margin-top: 6px; font-weight: bold;">
                @php
                    $totalVnd = $cashCollectionVnd + $cardCollectionVnd;
                    $totalUsd = ($cashCollectionUsd ?? 0) + ($cardCollectionUsd ?? 0);

                    $usdPart = '';
                    if ($totalUsd > 0) {
                        $val = $totalUsd;
                        $formatted = $val == floor($val) ? number_format($val, 0) : number_format($val, 2);
                        $usdPart = ' + USD $' . $formatted;
                    }
                @endphp
                <strong>Grand Total: {{ number_format($totalVnd, 0) }}đ{{ $usdPart }}</strong>
            </p>
        @endif
    </div>
</body>

</html>