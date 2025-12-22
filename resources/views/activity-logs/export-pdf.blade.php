<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nhật ký hoạt động</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
        }
        h1 {
            text-align: center;
            font-size: 16px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .text-center {
            text-align: center;
        }
        .suspicious {
            background-color: #ffebee;
        }
    </style>
</head>
<body>
    <h1>NHẬT KÝ HOẠT ĐỘNG HỆ THỐNG</h1>
    <p><strong>Ngày xuất:</strong> {{ date('d/m/Y H:i:s') }}</p>
    <p><strong>Tổng số bản ghi:</strong> {{ count($logs) }}</p>

    <table>
        <thead>
            <tr>
                <th class="text-center">STT</th>
                <th>Thời gian</th>
                <th>Người dùng</th>
                <th>Loại</th>
                <th>Module</th>
                <th>Mô tả</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $index => $log)
            <tr class="{{ $log->is_suspicious ? 'suspicious' : '' }}">
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $log->user ? $log->user->name : 'Hệ thống' }}</td>
                <td>{{ $log->getActivityTypeLabel() }}</td>
                <td>{{ $log->getModuleLabel() }}</td>
                <td>{{ Str::limit($log->description, 50) }}</td>
                <td>{{ $log->ip_address }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
