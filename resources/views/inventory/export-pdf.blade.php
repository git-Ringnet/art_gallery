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
            font-size: 10px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2563eb;
        }
        .header h1 {
            font-size: 18px;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .header .subtitle {
            font-size: 11px;
            color: #6b7280;
        }
        .filters {
            margin-bottom: 10px;
            font-size: 9px;
            color: #6b7280;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background-color: #2563eb;
            color: white;
            padding: 6px 4px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
        }
        td {
            padding: 4px 4px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
            vertical-align: middle;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .type-painting {
            background-color: #f3e8ff;
            color: #7c3aed;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .type-supply {
            background-color: #dbeafe;
            color: #2563eb;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
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
        .item-image {
            width: 35px;
            height: 35px;
            object-fit: cover;
            border-radius: 3px;
        }
        .footer {
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
            margin-top: 10px;
            padding-top: 8px;
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
                <th style="width: 3%">STT</th>
                <th style="width: 10%">Mã</th>
                <th style="width: 24%">Tên sản phẩm</th>
                <th style="width: 7%">Loại</th>
                <th style="width: 18%">Số lượng</th>
                <th style="width: 10%">Ngày nhập</th>
                <th style="width: 10%">Trạng thái</th>
                <th style="width: 8%">Ảnh</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inventory as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item['code'] }}</td>
                    <td>{{ $item['name'] }}</td>
                    <td class="text-center">
                        <span class="{{ $item['type'] === 'Tranh' ? 'type-painting' : 'type-supply' }}">
                            {{ $item['type'] }}
                        </span>
                    </td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>{{ $item['import_date'] ?? '-' }}</td>
                    <td>
                        <span class="{{ str_contains($item['status'], 'Còn') ? 'status-available' : 'status-sold' }}">
                            {{ $item['status'] }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if(!empty($item['image_base64']))
                            <img src="{{ $item['image_base64'] }}" class="item-image" alt="">
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px; color: #9ca3af;">
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
