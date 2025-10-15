<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách nhân viên</title>
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            src: url('/fonts/DejaVuSans.ttf') format('truetype');
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            margin: 0 0 5px 0;
            color: #1e40af;
        }
        .header p {
            margin: 3px 0;
            color: #666;
        }
        .info-box {
            background: #f3f4f6;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .info-box p {
            margin: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background: #1e40af;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        .status-active {
            color: #059669;
            font-weight: bold;
        }
        .status-inactive {
            color: #dc2626;
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
    <div class="header">
        <h1>DANH SÁCH NHÂN VIÊN</h1>
        <p>Ngày xuất: {{ date('d/m/Y H:i') }}</p>
    </div>

    @if($search || $status !== null || $dateFrom || $dateTo)
    <div class="info-box">
        <strong>Bộ lọc áp dụng:</strong>
        @if($search)
            <p>- Tìm kiếm: {{ $search }}</p>
        @endif
        @if($status !== null && $status !== '')
            <p>- Trạng thái: {{ $status == '1' ? 'Hoạt động' : 'Ngừng hoạt động' }}</p>
        @endif
        @if($dateFrom)
            <p>- Từ ngày: {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}</p>
        @endif
        @if($dateTo)
            <p>- Đến ngày: {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</p>
        @endif
    </div>
    @endif

    <p><strong>Tổng số: {{ $employees->count() }} nhân viên</strong></p>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">STT</th>
                <th style="width: 25%;">Tên nhân viên</th>
                <th style="width: 25%;">Email</th>
                <th style="width: 15%;">Số điện thoại</th>
                <th style="width: 15%;">Trạng thái</th>
                <th style="width: 15%;">Ngày tạo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $index => $employee)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $employee->name }}</td>
                <td>{{ $employee->email }}</td>
                <td>{{ $employee->phone ?? '-' }}</td>
                <td class="{{ $employee->is_active ? 'status-active' : 'status-inactive' }}">
                    {{ $employee->is_active ? 'Hoạt động' : 'Ngừng hoạt động' }}
                </td>
                <td>{{ $employee->created_at->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Hệ thống quản lý phòng tranh nghệ thuật</p>
    </div>
</body>
</html>
