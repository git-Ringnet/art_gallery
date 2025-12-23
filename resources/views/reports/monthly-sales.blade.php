@extends('layouts.app')

@section('title', 'Báo cáo Bán hàng')
@section('page-title', 'Báo cáo')
@section('page-description', 'Monthly Sales Report')

@section('content')
<x-alert />

<!-- Filter Form -->
<div class="bg-white rounded-xl shadow-md p-6 mb-6 no-print">
    <form method="GET" action="{{ route('reports.monthly-sales') }}" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            @if($canFilterByDate)
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-calendar-day mr-2 text-blue-500"></i>Từ ngày
                </label>
                <input type="date" name="from_date" value="{{ request('from_date', $fromDate->format('Y-m-d')) }}" 
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-calendar-day mr-2 text-red-500"></i>Đến ngày
                </label>
                <input type="date" name="to_date" value="{{ request('to_date', $toDate->format('Y-m-d')) }}" 
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            @endif
            
            @if($canFilterByShowroom)
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-store mr-2 text-green-500"></i>Showroom
                </label>
                <select name="showroom_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Tất cả --</option>
                    @foreach($showrooms as $showroom)
                        <option value="{{ $showroom->id }}" {{ $showroomId == $showroom->id ? 'selected' : '' }}>{{ $showroom->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            
            @if($canFilterByUser)
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-user-tie mr-2 text-indigo-500"></i>Nhân viên
                </label>
                <select name="employee_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Tất cả --</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ $employeeId == $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-exchange-alt mr-2 text-purple-500"></i>Tỷ giá
                </label>
                <input type="text" name="exchange_rate" value="{{ request('exchange_rate', '') }}" 
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                       placeholder="VD: 25000">
            </div>
        </div>
        
        <div class="flex gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-semibold">
                <i class="fas fa-search mr-2"></i>Xem báo cáo
            </button>
            <a href="{{ route('reports.monthly-sales') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2.5 rounded-lg font-semibold">
                <i class="fas fa-times-circle mr-2"></i>Xóa bộ lọc
            </a>
            @if($canPrint)
            <button type="button" onclick="window.print()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 rounded-lg font-semibold">
                <i class="fas fa-print mr-2"></i>In báo cáo
            </button>
            @endif
        </div>
    </form>
    
    <!-- Quick Filters -->
    <div class="mt-4 flex gap-2 flex-wrap">
        <span class="text-sm text-gray-600 font-semibold">Lọc nhanh:</span>
        <button onclick="setDateRange('month')" class="px-3 py-1 text-xs bg-purple-100 hover:bg-purple-200 text-purple-700 rounded-lg">
            <i class="fas fa-calendar-alt mr-1"></i>Tháng này
        </button>
        <button onclick="setDateRange('lastmonth')" class="px-3 py-1 text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg">
            <i class="fas fa-calendar mr-1"></i>Tháng trước
        </button>
        <button onclick="setDateRange('quarter')" class="px-3 py-1 text-xs bg-green-100 hover:bg-green-200 text-green-700 rounded-lg">
            <i class="fas fa-calendar-week mr-1"></i>Quý này
        </button>
        <button onclick="setDateRange('year')" class="px-3 py-1 text-xs bg-orange-100 hover:bg-orange-200 text-orange-700 rounded-lg">
            <i class="fas fa-calendar mr-1"></i>Năm nay
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 no-print">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-5 text-white">
        <p class="text-sm opacity-90">Tổng doanh thu</p>
        @if($totalUsd > 0 && $exchangeRate <= 1)
            <p class="text-lg font-bold text-yellow-300">Cần nhập tỷ giá</p>
            <p class="text-xs opacity-75">${{ number_format($totalUsd, 2) }} + {{ number_format($totalVnd, 0) }}đ</p>
        @else
            <p class="text-2xl font-bold">{{ number_format($grandTotalVnd, 0) }} đ</p>
        @endif
    </div>
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-5 text-white">
        <p class="text-sm opacity-90">Đã thu</p>
        <p class="text-2xl font-bold">{{ number_format($grandPaidVnd, 0) }} đ</p>
    </div>
    <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-5 text-white">
        <p class="text-sm opacity-90">Còn nợ</p>
        <p class="text-2xl font-bold">{{ number_format($grandDebtVnd, 0) }} đ</p>
    </div>
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-5 text-white">
        <p class="text-sm opacity-90">Số đơn hàng</p>
        <p class="text-2xl font-bold">{{ count($reportData) }}</p>
        <p class="text-xs opacity-75">{{ $totalItems }} sản phẩm</p>
    </div>
</div>

<!-- Report Table (Screen) -->
<div id="screen-view" class="bg-white rounded-xl shadow-lg overflow-hidden no-print">
    <div class="p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-table mr-2 text-blue-500"></i>Chi tiết bán hàng
                <span class="text-sm font-normal text-gray-600 ml-2">({{ $fromDate->format('d/m/Y') }} - {{ $toDate->format('d/m/Y') }})</span>
            </h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">No.</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">Ngày</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">Mã HĐ</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">ID Code</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">Khách hàng</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-700">Tổng USD</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-700">Tổng VND</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-700">Đã trả</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-700">Còn nợ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData as $index => $item)
                    <tr class="border-b border-gray-100 hover:bg-blue-50">
                        <td class="px-3 py-2">{{ $index + 1 }}</td>
                        <td class="px-3 py-2">{{ $item['sale_date'] }}</td>
                        <td class="px-3 py-2 font-medium text-blue-600">{{ $item['invoice_code'] }}</td>
                        <td class="px-3 py-2">{{ $item['id_code'] }}</td>
                        <td class="px-3 py-2">{{ $item['customer_name'] }}</td>
                        <td class="px-3 py-2 text-right">{{ $item['total_usd'] > 0 ? '$' . number_format($item['total_usd'], 2) : '' }}</td>
                        <td class="px-3 py-2 text-right">{{ $item['total_vnd'] > 0 ? number_format($item['total_vnd'], 0) : '' }}</td>
                        <td class="px-3 py-2 text-right text-green-600">
                            {{ number_format($item['paid_vnd'], 0) }}đ
                        </td>
                        <td class="px-3 py-2 text-right text-red-600">
                            {{ number_format($item['debt_vnd'], 0) }}đ
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-3 py-8 text-center text-gray-500">Không có dữ liệu</td></tr>
                    @endforelse
                    
                    @if(count($reportData) > 0)
                    <tr class="bg-gradient-to-r from-gray-100 to-gray-200 font-bold border-t-2">
                        <td colspan="5" class="px-3 py-3">TỔNG CỘNG</td>
                        <td class="px-3 py-3 text-right">${{ number_format($totalUsd, 2) }}</td>
                        <td class="px-3 py-3 text-right">{{ number_format($totalVnd, 0) }}</td>
                        <td class="px-3 py-3 text-right text-green-600">{{ number_format($totalPaidVnd, 0) }}đ</td>
                        <td class="px-3 py-3 text-right text-red-600">{{ number_format($totalDebtVnd, 0) }}đ</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        
        <!-- Summary -->
        @if(count($reportData) > 0)
        <div class="mt-6 bg-gradient-to-r from-gray-50 to-blue-50 rounded-lg p-6 border-l-4 border-blue-500">
            <h4 class="text-lg font-bold text-gray-800 mb-4"><i class="fas fa-calculator mr-2 text-blue-600"></i>Tổng kết</h4>
            <div class="space-y-3">
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                    <span class="text-gray-700 font-medium">Tổng doanh thu:</span>
                    <span class="text-lg font-bold text-blue-700">
                        @if($totalUsd > 0 && $exchangeRate <= 1)
                            ${{ number_format($totalUsd, 2) }} + {{ number_format($totalVnd, 0) }}đ
                        @else
                            VND {{ number_format($grandTotalVnd, 0) }}
                        @endif
                    </span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                    <span class="text-gray-700 font-medium">Đã thu:</span>
                    <span class="text-lg font-bold text-green-700">VND {{ number_format($totalPaidVnd, 0) }}</span>
                </div>
                <div class="flex justify-between items-center py-3 bg-gradient-to-r from-red-100 to-orange-100 rounded-lg px-4">
                    <span class="text-gray-800 font-bold text-lg">Còn nợ:</span>
                    <span class="text-xl font-bold text-red-700">VND {{ number_format($totalDebtVnd, 0) }}</span>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Print View -->
<div id="print-view" class="print-only" style="display: none;">
    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 10px;">
        <div>
            <strong style="font-size: 12px;">{{ $selectedShowroom ? $selectedShowroom->name : 'Ben Thanh Art Gallery' }}</strong><br>
            @if($selectedShowroom)
            {{ $selectedShowroom->address }}<br>Tel: {{ $selectedShowroom->phone }}
            @else
            07 Nguyen Thiep - Dist.1, HCMC<br>Tel: (84-8) 3823 3001 - 3823 8101
            @endif
        </div>
        <div style="text-align: right;">
            <strong>Page 1</strong><br>Date: {{ now()->format('d/m/Y') }}
        </div>
    </div>
    
    <div class="text-center mb-4">
        <h2 class="text-base font-bold mt-2">Monthly Sales Report<br>{{ $fromDate->format('d/m/Y') }} - {{ $toDate->format('d/m/Y') }}</h2>
    </div>

    <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; font-size: 9px;">
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th style="border: 1px solid #000; padding: 4px;">No.</th>
                <th style="border: 1px solid #000; padding: 4px;">Date</th>
                <th style="border: 1px solid #000; padding: 4px;">Invoice</th>
                <th style="border: 1px solid #000; padding: 4px;">ID Code</th>
                <th style="border: 1px solid #000; padding: 4px;">Customer</th>
                <th style="border: 1px solid #000; padding: 4px;">Total USD</th>
                <th style="border: 1px solid #000; padding: 4px;">Total VND</th>
                <th style="border: 1px solid #000; padding: 4px;">Paid</th>
                <th style="border: 1px solid #000; padding: 4px;">Debt</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $index => $item)
            <tr>
                <td style="border: 1px solid #000; padding: 3px;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000; padding: 3px;">{{ $item['sale_date'] }}</td>
                <td style="border: 1px solid #000; padding: 3px;">{{ $item['invoice_code'] }}</td>
                <td style="border: 1px solid #000; padding: 3px;">{{ $item['id_code'] }}</td>
                <td style="border: 1px solid #000; padding: 3px;">{{ $item['customer_name'] }}</td>
                <td style="border: 1px solid #000; padding: 3px; text-align: right;">{{ $item['total_usd'] > 0 ? number_format($item['total_usd'], 2) : '' }}</td>
                <td style="border: 1px solid #000; padding: 3px; text-align: right;">{{ $item['total_vnd'] > 0 ? number_format($item['total_vnd'], 0) : '' }}</td>
                <td style="border: 1px solid #000; padding: 3px; text-align: right;">{{ $item['paid_vnd'] > 0 ? number_format($item['paid_vnd'], 0) : '' }}</td>
                <td style="border: 1px solid #000; padding: 3px; text-align: right;">{{ $item['debt_vnd'] > 0 ? number_format($item['debt_vnd'], 0) : '' }}</td>
            </tr>
            @endforeach
            <tr style="background-color: #e0e0e0; font-weight: bold;">
                <td colspan="5" style="border: 1px solid #000; padding: 5px;">GRAND TOTAL</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: right;">{{ number_format($totalUsd, 2) }}</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: right;">{{ number_format($totalVnd, 0) }}</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: right;">{{ number_format($totalPaidVnd, 0) }}</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: right;">{{ number_format($totalDebtVnd, 0) }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 15px; font-size: 10px;">
        <p style="margin: 3px 0;"><strong>Total Revenue:</strong> VND {{ number_format($grandTotalVnd, 0) }}</p>
        <p style="margin: 3px 0;"><strong>Total Paid:</strong> VND {{ number_format($totalPaidVnd, 0) }}</p>
        <p style="border-top: 2px solid #000; border-bottom: 2px double #000; padding: 6px 0; margin-top: 6px; font-weight: bold;">
            <strong>TOTAL DEBT:</strong> VND {{ number_format($totalDebtVnd, 0) }}
        </p>
    </div>
</div>

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        #screen-view { display: none !important; }
        #print-view { display: block !important; }
        @page { margin: 0.8cm; }
    }
    .print-only { display: none; }
</style>
@endpush

<script>
function setDateRange(type) {
    const today = new Date();
    let fromDate, toDate;
    
    switch(type) {
        case 'month':
            fromDate = new Date(today.getFullYear(), today.getMonth(), 1);
            toDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            break;
        case 'lastmonth':
            fromDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            toDate = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
        case 'quarter':
            const quarter = Math.floor(today.getMonth() / 3);
            fromDate = new Date(today.getFullYear(), quarter * 3, 1);
            toDate = new Date(today.getFullYear(), quarter * 3 + 3, 0);
            break;
        case 'year':
            fromDate = new Date(today.getFullYear(), 0, 1);
            toDate = new Date(today.getFullYear(), 11, 31);
            break;
    }
    
    document.querySelector('input[name="from_date"]').value = formatDate(fromDate);
    document.querySelector('input[name="to_date"]').value = formatDate(toDate);
}

function formatDate(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}
</script>
@endsection
