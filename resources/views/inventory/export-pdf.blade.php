<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo Quản lý Kho</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            margin: 15px;
        }
        h1 {
            text-align: center;
            color: #1e40af;
            margin-bottom: 5px;
            font-size: 18px;
        }
        .info {
            text-align: center;
            margin-bottom: 15px;
            color: #666;
            font-size: 10px;
        }
        .info p {
            margin: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #1e40af;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
        }
        td {
            padding: 6px 5px;
            border-bottom: 1px solid #ddd;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .badge {
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
        }
        .badge-painting {
            background-color: #ddd6fe;
            color: #6b21a8;
        }
        .badge-supply {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-gray {
            background-color: #e5e7eb;
            color: #374151;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            color: #666;
            font-size: 9px;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <h1>BÁO CÁO QUẢN LÝ KHO</h1>
    <div class="info">
        <p>Ngày xuất: {{ date('d/m/Y H:i:s') }}</p>
        @if($search)
        <p>Tìm kiếm: <strong>{{ $search }}</strong></p>
        @endif
        @if($type)
        <p>Loại: <strong>{{ $type == 'painting' ? 'Tranh' : 'Vật tư' }}</strong></p>
        @endif
        @if($dateFrom || $dateTo)
        <p>Từ ngày: <strong>{{ $dateFrom ?? 'N/A' }}</strong> - Đến ngày: <strong>{{ $dateTo ?? 'N/A' }}</strong></p>
        @endif
        <p>Tổng số: <strong>{{ $inventory->count() }}</strong> sản phẩm</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>STT</th>
                <th>Mã</th>
                <th>Tên sản phẩm</th>
                <th>Loại</th>
                <th>Số lượng</th>
                <th>Ngày nhập</th>
                <th>Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inventory as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item['code'] }}</td>
                <td>{{ $item['name'] }}</td>
                <td>
                    <span class="badge {{ $item['type'] == 'Tranh' ? 'badge-painting' : 'badge-supply' }}">
                        {{ $item['type'] }}
                    </span>
                </td>
                <td>{{ $item['quantity'] }} {{ $item['unit'] }}</td>
                <td>{{ $item['import_date'] }}</td>
                <td>
                    <span class="badge {{ $item['status'] == 'Còn hàng' ? 'badge-success' : 'badge-gray' }}">
                        {{ $item['status'] }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px; color: #999;">
                    Không có dữ liệu
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Hệ thống Quản lý Tranh & Khung - © {{ date('Y') }}</p>
    </div>

</body>
</html>
