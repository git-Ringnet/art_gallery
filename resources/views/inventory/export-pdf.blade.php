<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo Quản lý Kho</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #333;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #2563eb;
        }
        .header h1 {
            font-size: 16px;
            color: #1e40af;
            margin-bottom: 4px;
        }
        .header .subtitle {
            font-size: 10px;
            color: #6b7280;
        }
        .filters {
            margin-bottom: 8px;
            font-size: 8px;
            color: #6b7280;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        th {
            background-color: #2563eb;
            color: white;
            padding: 5px 3px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
        }
        td {
            padding: 4px 3px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 7.5px;
            vertical-align: middle;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .type-painting {
            background-color: #f3e8ff;
            color: #7c3aed;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 7px;
            font-weight: bold;
        }
        .type-supply {
            background-color: #dbeafe;
            color: #2563eb;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 7px;
            font-weight: bold;
        }
        .status-available {
            color: #059669;
            font-weight: bold;
        }
        .status-sold {
            color: #dc2626;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px solid #e5e7eb;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>BÁO CÁO QUẢN LÝ KHO</h1>
        <div class="subtitle">
            Ngày xuất: {{ date('d/m/Y H:i') }}
            @if($scope === 'current')
                | Trang hiện tại
            @else
                | Tất cả ({{ $inventory->count() }} sản phẩm)
            @endif
        </div>
    </div>

    @if($search || $type || $dateFrom || $dateTo)
        <div class="filters">
            <strong>Bộ lọc:</strong>
            @if($search) Tìm kiếm: "{{ $search }}" | @endif
            @if($type) Loại: {{ $type === 'painting' ? 'Tranh' : 'Vật tư' }} | @endif
            @if($dateFrom) Từ: {{ $dateFrom }} | @endif
            @if($dateTo) Đến: {{ $dateTo }} @endif
        </div>
    @endif

    <table>
        <thead>
            <tr>
                @foreach($selectedColumns as $col)
                    <th class="{{ in_array($col, ['stt', 'image', 'type', 'import_date', 'export_date', 'status', 'paint_year']) ? 'text-center' : (in_array($col, ['price_usd', 'price_vnd']) ? 'text-right' : 'text-left') }}">
                        {{ $columnTitles[$col] ?? $col }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($inventory as $index => $item)
                <tr>
                    @foreach($selectedColumns as $col)
                        @switch($col)
                            @case('stt')
                                <td class="text-center">{{ $index + 1 }}</td>
                                @break
                            @case('image')
                                <td class="text-center" style="width: 45px; padding: 2px;">
                                    @if(!empty($item['image_base64']))
                                        <img src="{{ $item['image_base64'] }}" style="width: 38px; height: 38px; object-fit: cover; border-radius: 2px;" alt="">
                                    @else
                                        -
                                    @endif
                                </td>
                                @break
                            @case('code')
                                <td style="font-weight: bold; color: #4f46e5;">{{ $item['code'] }}</td>
                                @break
                            @case('name')
                                <td>{{ $item['name'] }}</td>
                                @break
                            @case('type')
                                <td class="text-center">
                                    <span class="{{ $item['type'] === 'Tranh' ? 'type-painting' : 'type-supply' }}">
                                        {{ $item['type'] }}
                                    </span>
                                </td>
                                @break
                            @case('artist')
                                <td>{{ $item['artist'] ?? '-' }}</td>
                                @break
                            @case('material')
                                <td>
                                    @php
                                        $material = $item['material'] ?? '';
                                        $materialLabels = [
                                            'son_dau' => 'Sơn dầu',
                                            'canvas' => 'Canvas',
                                            'thuy_mac' => 'Thủy mặc',
                                            'acrylic' => 'Acrylic',
                                        ];
                                        echo $materialLabels[$material] ?? ($material ?: '-');
                                    @endphp
                                </td>
                                @break
                            @case('dimensions')
                                <td>{{ $item['dimensions'] ?? '-' }}</td>
                                @break
                            @case('paint_year')
                                <td class="text-center">{{ $item['paint_year'] ?? '-' }}</td>
                                @break
                            @case('price_usd')
                                <td class="text-right" style="color: #2563eb; font-weight: bold;">
                                    {{ !empty($item['price_usd']) ? '$' . number_format($item['price_usd'], 2) : '-' }}
                                </td>
                                @break
                            @case('price_vnd')
                                <td class="text-right" style="color: #059669; font-weight: bold;">
                                    {{ !empty($item['price_vnd']) ? number_format($item['price_vnd']) . 'đ' : '-' }}
                                </td>
                                @break
                            @case('quantity')
                                <td>{{ $item['quantity'] }}</td>
                                @break
                            @case('import_date')
                                <td class="text-center">{{ $item['import_date'] ?? '-' }}</td>
                                @break
                            @case('export_date')
                                <td class="text-center">{{ $item['export_date'] ?? '-' }}</td>
                                @break
                            @case('invoice_code')
                                <td>{{ $item['invoice_code'] ?? '-' }}</td>
                                @break
                            @case('status')
                                <td class="text-center">
                                    <span class="{{ str_contains($item['status'], 'Còn') ? 'status-available' : 'status-sold' }}">
                                        {{ $item['status'] }}
                                    </span>
                                </td>
                                @break
                            @default
                                <td>{{ $item[$col] ?? '-' }}</td>
                        @endswitch
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($selectedColumns) }}" class="text-center" style="padding: 20px; color: #9ca3af;">
                        Không có dữ liệu
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Tổng: {{ $inventory->count() }} sản phẩm | Art Gallery - Hệ thống quản lý kho
    </div>
</body>
</html>
