@extends('layouts.app')

@section('title', 'Báo cáo Nhập Stock')
@section('page-title', 'Báo cáo')
@section('page-description', 'Stock Import Report')

@section('content')
<x-alert />

<!-- Filter Form -->
<div class="bg-white rounded-xl shadow-md p-6 mb-6 no-print">
    <form method="GET" action="{{ route('reports.stock-import') }}" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-exchange-alt mr-2 text-purple-500"></i>Tỷ giá (VND/USD)
                </label>
                <input type="text" name="exchange_rate" value="{{ request('exchange_rate', '') }}" 
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                       placeholder="VD: 25000">
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex flex-col gap-3">
            <!-- Row 1: Primary Actions -->
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-2.5 rounded-lg font-semibold shadow-lg transition-all duration-200">
                    <i class="fas fa-search mr-2"></i>Xem báo cáo
                </button>
                <a href="{{ route('reports.stock-import') }}" class="flex-1 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white px-6 py-2.5 rounded-lg font-semibold shadow-lg transition-all duration-200 flex items-center justify-center">
                    <i class="fas fa-times-circle mr-2"></i>Xóa bộ lọc
                </a>
            </div>
            
            <!-- Row 2: Export Actions -->
            <div class="flex gap-2">
                @if($exchangeRate <= 1)
                    <!-- Chưa nhập tỷ giá → Vô hiệu hóa Excel/PDF/Print -->
                    <button type="button" disabled class="flex-1 bg-gray-400 cursor-not-allowed text-white px-4 py-2.5 rounded-lg font-semibold shadow-lg opacity-60 relative group">
                        <i class="fas fa-file-excel mr-2"></i>Excel
                        <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                            ⚠️ Cần nhập tỷ giá trước khi xuất
                        </span>
                    </button>
                    <button type="button" disabled class="flex-1 bg-gray-400 cursor-not-allowed text-white px-4 py-2.5 rounded-lg font-semibold shadow-lg opacity-60 relative group">
                        <i class="fas fa-file-pdf mr-2"></i>PDF
                        <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                            ⚠️ Cần nhập tỷ giá trước khi xuất
                        </span>
                    </button>
                    @if($canPrint)
                    <button type="button" disabled class="flex-1 bg-gray-400 cursor-not-allowed text-white px-4 py-2.5 rounded-lg font-semibold shadow-lg opacity-60 relative group">
                        <i class="fas fa-print mr-2"></i>In báo cáo
                        <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                            ⚠️ Cần nhập tỷ giá trước khi in
                        </span>
                    </button>
                    @endif
                @else
                    <!-- Đã nhập tỷ giá → Cho phép xuất -->
                    <a href="{{ route('reports.stock-import.export.excel', request()->all()) }}" class="flex-1 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-4 py-2.5 rounded-lg font-semibold shadow-lg transition-all duration-200 flex items-center justify-center">
                        <i class="fas fa-file-excel mr-2"></i>Excel
                    </a>
                    <a href="{{ route('reports.stock-import.export.pdf', request()->all()) }}" class="flex-1 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white px-4 py-2.5 rounded-lg font-semibold shadow-lg transition-all duration-200 flex items-center justify-center">
                        <i class="fas fa-file-pdf mr-2"></i>PDF
                    </a>
                    @if($canPrint)
                    <button type="button" onclick="window.print()" class="flex-1 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white px-4 py-2.5 rounded-lg font-semibold shadow-lg transition-all duration-200">
                        <i class="fas fa-print mr-2"></i>In báo cáo
                    </button>
                    @endif
                @endif
            </div>
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
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 no-print">
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-5 text-white">
        <p class="text-sm opacity-90">Số lượng tranh nhập</p>
        <p class="text-2xl font-bold">{{ $totalQuantity }}</p>
        <p class="text-xs opacity-75">{{ count($reportData) }} mã tranh</p>
    </div>
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-5 text-white">
        <p class="text-sm opacity-90">Tổng giá trị (USD)</p>
        <p class="text-2xl font-bold">${{ number_format($totalPriceUsd, 2) }}</p>
    </div>
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-5 text-white">
        <p class="text-sm opacity-90">Tổng giá trị (VND)</p>
        @if($totalPriceUsd > 0 && $exchangeRate <= 1)
            <p class="text-lg font-bold text-yellow-300">Cần nhập tỷ giá</p>
        @else
            <p class="text-2xl font-bold">{{ number_format($grandTotalVnd, 0) }} đ</p>
        @endif
    </div>
</div>

<!-- Report Table (Screen) -->
<div id="screen-view" class="bg-white rounded-xl shadow-lg overflow-hidden no-print">
    <div class="p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-table mr-2 text-green-500"></i>Chi tiết tranh nhập kho
                <span class="text-sm font-normal text-gray-600 ml-2">({{ $fromDate->format('d/m/Y') }} - {{ $toDate->format('d/m/Y') }})</span>
            </h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">No.</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">Ngày nhập</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">Mã tranh</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">Tên tranh</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">Họa sĩ</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">Chất liệu</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">Kích thước</th>
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-700">SL</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-700">Giá (USD)</th>
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-700">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData as $index => $item)
                    <tr class="border-b border-gray-100 hover:bg-green-50">
                        <td class="px-3 py-2">{{ $index + 1 }}</td>
                        <td class="px-3 py-2">{{ $item['import_date'] }}</td>
                        <td class="px-3 py-2 font-medium text-blue-600">{{ $item['code'] }}</td>
                        <td class="px-3 py-2">{{ $item['name'] }}</td>
                        <td class="px-3 py-2">{{ $item['artist'] }}</td>
                        <td class="px-3 py-2">{{ $item['material'] }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ $item['dimensions'] }}</td>
                        <td class="px-3 py-2 text-center">{{ $item['quantity'] }}</td>
                        <td class="px-3 py-2 text-right font-medium">${{ number_format($item['price_usd'], 2) }}</td>
                        <td class="px-3 py-2 text-center">
                            @if($item['status'] == 'Còn hàng')
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded-full">{{ $item['status'] }}</span>
                            @elseif($item['status'] == 'Đã bán')
                                <span class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded-full">{{ $item['status'] }}</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 rounded-full">{{ $item['status'] }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="px-3 py-8 text-center text-gray-500">Không có tranh nhập trong khoảng thời gian này</td></tr>
                    @endforelse
                    
                    @if(count($reportData) > 0)
                    <tr class="bg-gradient-to-r from-gray-100 to-gray-200 font-bold border-t-2">
                        <td colspan="7" class="px-3 py-3">TỔNG CỘNG</td>
                        <td class="px-3 py-3 text-center">{{ $totalQuantity }}</td>
                        <td class="px-3 py-3 text-right">${{ number_format($totalPriceUsd, 2) }}</td>
                        <td class="px-3 py-3"></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        
        <!-- Summary -->
        @if(count($reportData) > 0)
        <div class="mt-6 bg-gradient-to-r from-gray-50 to-green-50 rounded-lg p-6 border-l-4 border-green-500">
            <h4 class="text-lg font-bold text-gray-800 mb-4"><i class="fas fa-calculator mr-2 text-green-600"></i>Tổng kết nhập kho</h4>
            <div class="space-y-3">
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                    <span class="text-gray-700 font-medium">Số lượng tranh:</span>
                    <span class="text-lg font-bold text-green-700">{{ $totalQuantity }} tranh</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                    <span class="text-gray-700 font-medium">Tổng giá trị (USD):</span>
                    <span class="text-lg font-bold text-blue-700">${{ number_format($totalPriceUsd, 2) }}</span>
                </div>
                <div class="flex justify-between items-center py-3 bg-gradient-to-r from-green-100 to-blue-100 rounded-lg px-4">
                    <span class="text-gray-800 font-bold text-lg">Tổng giá trị (VND):</span>
                    <span class="text-xl font-bold text-purple-700">
                        @if($totalPriceUsd > 0 && $exchangeRate <= 1)
                            Cần nhập tỷ giá
                        @else
                            VND {{ number_format($grandTotalVnd, 0) }}
                        @endif
                    </span>
                </div>
                @if($totalPriceUsd > 0 && $exchangeRate > 1)
                <p class="text-xs text-gray-500 italic">
                    <i class="fas fa-info-circle mr-1"></i>
                    (${{ number_format($totalPriceUsd, 2) }} × {{ number_format($exchangeRate, 0) }} + {{ number_format($totalPriceVnd, 0) }}đ)
                </p>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Print View -->
<div id="print-view" class="print-only" style="display: none;">
    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 10px;">
        <div>
            <strong style="font-size: 12px;">Ben Thanh Art Gallery</strong><br>
            07 Nguyen Thiep - Dist.1, HCMC<br>
            Tel: (84-8) 3823 3001 - 3823 8101
        </div>
        <div style="text-align: right;">
            <strong>Page 1</strong><br>Date: {{ now()->format('d/m/Y') }}
        </div>
    </div>
    
    <div class="text-center mb-4">
        <h2 class="text-base font-bold mt-2">Stock Import Report<br>{{ $fromDate->format('d/m/Y') }} - {{ $toDate->format('d/m/Y') }}</h2>
    </div>

    <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; font-size: 8px;">
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th style="border: 1px solid #000; padding: 3px;">No.</th>
                <th style="border: 1px solid #000; padding: 3px;">Import Date</th>
                <th style="border: 1px solid #000; padding: 3px;">Code</th>
                <th style="border: 1px solid #000; padding: 3px;">Name</th>
                <th style="border: 1px solid #000; padding: 3px;">Artist</th>
                <th style="border: 1px solid #000; padding: 3px;">Material</th>
                <th style="border: 1px solid #000; padding: 3px;">Dimensions</th>
                <th style="border: 1px solid #000; padding: 3px;">Qty</th>
                <th style="border: 1px solid #000; padding: 3px;">Price (USD)</th>
                <th style="border: 1px solid #000; padding: 3px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $index => $item)
            <tr>
                <td style="border: 1px solid #000; padding: 2px;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000; padding: 2px;">{{ $item['import_date'] }}</td>
                <td style="border: 1px solid #000; padding: 2px;">{{ $item['code'] }}</td>
                <td style="border: 1px solid #000; padding: 2px;">{{ $item['name'] }}</td>
                <td style="border: 1px solid #000; padding: 2px;">{{ $item['artist'] }}</td>
                <td style="border: 1px solid #000; padding: 2px;">{{ $item['material'] }}</td>
                <td style="border: 1px solid #000; padding: 2px;">{{ $item['dimensions'] }}</td>
                <td style="border: 1px solid #000; padding: 2px; text-align: center;">{{ $item['quantity'] }}</td>
                <td style="border: 1px solid #000; padding: 2px; text-align: right;">{{ number_format($item['price_usd'], 2) }}</td>
                <td style="border: 1px solid #000; padding: 2px;">{{ $item['status'] }}</td>
            </tr>
            @endforeach
            <tr style="background-color: #e0e0e0; font-weight: bold;">
                <td colspan="7" style="border: 1px solid #000; padding: 4px;">GRAND TOTAL</td>
                <td style="border: 1px solid #000; padding: 4px; text-align: center;">{{ $totalQuantity }}</td>
                <td style="border: 1px solid #000; padding: 4px; text-align: right;">{{ number_format($totalPriceUsd, 2) }}</td>
                <td style="border: 1px solid #000; padding: 4px;"></td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 15px; font-size: 10px;">
        <p style="margin: 3px 0;"><strong>Total Paintings:</strong> {{ $totalQuantity }}</p>
        <p style="margin: 3px 0;"><strong>Total Value (USD):</strong> ${{ number_format($totalPriceUsd, 2) }}</p>
        <p style="border-top: 2px solid #000; border-bottom: 2px double #000; padding: 6px 0; margin-top: 6px; font-weight: bold;">
            <strong>TOTAL VALUE (VND):</strong> {{ number_format($grandTotalVnd, 0) }}
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
