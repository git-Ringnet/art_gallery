@extends('layouts.app')

@section('title', 'Báo cáo Công nợ')
@section('page-title', 'Báo cáo')
@section('page-description', 'Debt Report')

@section('content')
<x-alert />

<!-- Filter Form -->
<div class="bg-white rounded-xl shadow-md p-6 mb-6 no-print">
    <form method="GET" action="{{ route('reports.debt-report') }}" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-list mr-2 text-purple-500"></i>Loại báo cáo
                </label>
                <select name="report_type" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="cumulative" {{ $reportType == 'cumulative' ? 'selected' : '' }}>Lũy kế (tất cả công nợ)</option>
                    <option value="month" {{ $reportType == 'month' ? 'selected' : '' }}>Theo khoảng thời gian</option>
                </select>
            </div>
            
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
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-user mr-2 text-pink-500"></i>Khách hàng
                </label>
                <select name="customer_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Tất cả --</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ $customerId == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-exchange-alt mr-2 text-purple-500"></i>Tỷ giá
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
                <a href="{{ route('reports.debt-report') }}" class="flex-1 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white px-6 py-2.5 rounded-lg font-semibold shadow- transition-all duration-200 flex items-center justify-center">
                    <i class="fas fa-times-circle mr-2"></i>Xóa bộ lọc
                </a>
            </div>
            
            <!-- Warning message when exchange rate not set -->
            @if($exchangeRate <= 1)
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-2">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                        <p class="text-sm text-yellow-800 font-medium">Vui lòng nhập tỷ giá để xuất báo cáo (Excel, PDF, In)</p>
                    </div>
                </div>
            @endif
            
            <!-- Row 2: Export Actions -->
            <div class="flex gap-2">
                @if($exchangeRate <= 1)
                    <button type="button" disabled class="flex-1 bg-gray-400 cursor-not-allowed text-white px-4 py-2.5 rounded-lg font-semibold shadow-lg opacity-60">
                        <i class="fas fa-file-excel mr-2"></i>Excel
                    </button>
                    <button type="button" disabled class="flex-1 bg-gray-400 cursor-not-allowed text-white px-4 py-2.5 rounded-lg font-semibold shadow-lg opacity-60">
                        <i class="fas fa-file-pdf mr-2"></i>PDF
                    </button>
                @else
                    <!-- Đã nhập tỷ giá → Cho phép xuất -->
                    <a href="{{ route('reports.debt-report.export.excel', request()->all()) }}" class="flex-1 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-4 py-2.5 rounded-lg font-semibold shadow-lg transition-all duration-200 flex items-center justify-center">
                        <i class="fas fa-file-excel mr-2"></i>Excel
                    </a>
                    <a href="{{ route('reports.debt-report.export.pdf', request()->all()) }}" class="flex-1 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white px-4 py-2.5 rounded-lg font-semibold shadow-lg transition-all duration-200 flex items-center justify-center">
                        <i class="fas fa-file-pdf mr-2"></i>PDF
                    </a>
                @endif
                
                @if($canPrint)
                    @if($exchangeRate <= 1)
                        <button type="button" disabled class="flex-1 bg-gray-400 cursor-not-allowed text-white px-4 py-2.5 rounded-lg font-semibold shadow-lg opacity-60">
                            <i class="fas fa-print mr-2"></i>In báo cáo
                        </button>
                    @else
                        <!-- Đã nhập tỷ giá → Cho phép in -->
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
        <button onclick="setDateRange('all')" class="px-3 py-1 text-xs bg-red-100 hover:bg-red-200 text-red-700 rounded-lg">
            <i class="fas fa-list mr-1"></i>Tất cả công nợ
        </button>
        <button onclick="setDateRange('month')" class="px-3 py-1 text-xs bg-purple-100 hover:bg-purple-200 text-purple-700 rounded-lg">
            <i class="fas fa-calendar-alt mr-1"></i>Tháng này
        </button>
        <button onclick="setDateRange('lastmonth')" class="px-3 py-1 text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg">
            <i class="fas fa-calendar mr-1"></i>Tháng trước
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 no-print">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-5 text-white">
        <p class="text-sm opacity-90">Tổng giá trị đơn hàng</p>
        @if($exchangeRate > 1)
            {{-- Có tỷ giá: quy đổi tất cả ra VND --}}
            <p class="text-2xl font-bold">{{ number_format($grandTotalVnd, 0) }}đ</p>
            @if($totalSaleUsd > 0 || $totalSaleVnd > 0)
            <p class="text-xs opacity-75">
                @if($totalSaleUsd > 0) ${{ number_format($totalSaleUsd, 2) }} @endif
                @if($totalSaleUsd > 0 && $totalSaleVnd > 0) + @endif
                @if($totalSaleVnd > 0) {{ number_format($totalSaleVnd, 0) }}đ @endif
            </p>
            @endif
        @else
            {{-- Không có tỷ giá: hiển thị riêng --}}
            @if($totalSaleUsd > 0 && $totalSaleVnd > 0)
                <p class="text-xl font-bold">${{ number_format($totalSaleUsd, 2) }}</p>
                <p class="text-lg">{{ number_format($totalSaleVnd, 0) }}đ</p>
            @elseif($totalSaleUsd > 0)
                <p class="text-2xl font-bold">${{ number_format($totalSaleUsd, 2) }}</p>
            @else
                <p class="text-2xl font-bold">{{ number_format($totalSaleVnd, 0) }}đ</p>
            @endif
        @endif
    </div>
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-5 text-white">
        <p class="text-sm opacity-90">Đã thanh toán</p>
        @if($exchangeRate > 1)
            <p class="text-2xl font-bold">{{ number_format($grandPaidVnd, 0) }}đ</p>
            @if($totalPaidUsd > 0 || $totalPaidVnd > 0)
            <p class="text-xs opacity-75">
                @if($totalPaidUsd > 0) ${{ number_format($totalPaidUsd, 2) }} @endif
                @if($totalPaidUsd > 0 && $totalPaidVnd > 0) + @endif
                @if($totalPaidVnd > 0) {{ number_format($totalPaidVnd, 0) }}đ @endif
            </p>
            @endif
        @else
            @if($totalPaidUsd > 0 && $totalPaidVnd > 0)
                <p class="text-xl font-bold">${{ number_format($totalPaidUsd, 2) }}</p>
                <p class="text-lg">{{ number_format($totalPaidVnd, 0) }}đ</p>
            @elseif($totalPaidUsd > 0)
                <p class="text-2xl font-bold">${{ number_format($totalPaidUsd, 2) }}</p>
            @else
                <p class="text-2xl font-bold">{{ number_format($totalPaidVnd, 0) }}đ</p>
            @endif
        @endif
    </div>
    <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-5 text-white">
        <p class="text-sm opacity-90">Tổng công nợ</p>
        @if($exchangeRate > 1)
            <p class="text-2xl font-bold">{{ number_format($grandDebtVnd, 0) }}đ</p>
            @if($totalDebtUsd > 0 || $totalDebtVnd > 0)
            <p class="text-xs opacity-75">
                @if($totalDebtUsd > 0) ${{ number_format($totalDebtUsd, 2) }} @endif
                @if($totalDebtUsd > 0 && $totalDebtVnd > 0) + @endif
                @if($totalDebtVnd > 0) {{ number_format($totalDebtVnd, 0) }}đ @endif
            </p>
            @endif
        @else
            @if($totalDebtUsd > 0 && $totalDebtVnd > 0)
                <p class="text-xl font-bold">${{ number_format($totalDebtUsd, 2) }}</p>
                <p class="text-lg">{{ number_format($totalDebtVnd, 0) }}đ</p>
            @elseif($totalDebtUsd > 0)
                <p class="text-2xl font-bold">${{ number_format($totalDebtUsd, 2) }}</p>
            @else
                <p class="text-2xl font-bold">{{ number_format($totalDebtVnd, 0) }}đ</p>
            @endif
        @endif
        <p class="text-xs opacity-75 mt-1">{{ count($reportData) }} khách hàng còn nợ</p>
    </div>
</div>

<!-- Report Table (Screen) -->
<div id="screen-view" class="bg-white rounded-xl shadow-lg overflow-hidden no-print">
    <div class="p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-table mr-2 text-red-500"></i>Chi tiết công nợ
                <span class="text-sm font-normal text-gray-600 ml-2">
                    @if($reportType == 'cumulative')
                        (Lũy kế đến {{ $toDate->format('d/m/Y') }})
                    @else
                        ({{ $fromDate->format('d/m/Y') }} - {{ $toDate->format('d/m/Y') }})
                    @endif
                </span>
            </h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">No.</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">Ngày bán</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">Mã HĐ</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">ID Code</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">Khách hàng</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">SĐT</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-700">Tổng tiền</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-700">Đã trả</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-700">Còn nợ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData as $index => $item)
                    <tr class="border-b border-gray-100 hover:bg-red-50">
                        <td class="px-3 py-2">{{ $index + 1 }}</td>
                        <td class="px-3 py-2">{{ $item['sale_date'] }}</td>
                        <td class="px-3 py-2 font-medium text-blue-600">{{ $item['invoice_code'] }}</td>
                        <td class="px-3 py-2">{{ $item['id_code'] }}</td>
                        <td class="px-3 py-2 font-medium">{{ $item['customer_name'] }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ $item['customer_phone'] }}</td>
                        <td class="px-3 py-2 text-right">
                            @if($item['total_usd'] > 0) <div>${{ number_format($item['total_usd'], 2) }}</div> @endif
                            @if($item['total_vnd'] > 0) <div>{{ number_format($item['total_vnd'], 0) }}đ</div> @endif
                        </td>
                        <td class="px-3 py-2 text-right text-green-600">
                            {{-- Hiển thị theo loại hóa đơn --}}
                            @if($item['is_usd_only'] ?? false)
                                {{-- Hóa đơn CHỈ có USD: chỉ hiện USD (đã bao gồm VND quy đổi) --}}
                                <div>${{ number_format($item['paid_usd'], 2) }}</div>
                            @elseif($item['is_vnd_only'] ?? false)
                                {{-- Hóa đơn CHỈ có VND: chỉ hiện VND (đã bao gồm USD quy đổi) --}}
                                <div>{{ number_format($item['paid_vnd'], 0) }}đ</div>
                            @else
                                {{-- Hóa đơn hỗn hợp: hiện riêng USD và VND --}}
                                @if($item['paid_usd'] > 0) <div>${{ number_format($item['paid_usd'], 2) }}</div> @endif
                                @if($item['paid_vnd'] > 0) <div>{{ number_format($item['paid_vnd'], 0) }}đ</div> @endif
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right text-red-600 font-semibold">
                            {{-- Hiển thị theo loại hóa đơn --}}
                            @if($item['is_usd_only'] ?? false)
                                <div>${{ number_format($item['debt_usd'], 2) }}</div>
                            @elseif($item['is_vnd_only'] ?? false)
                                <div>{{ number_format($item['debt_vnd'], 0) }}đ</div>
                            @else
                                @if($item['debt_usd'] > 0) <div>${{ number_format($item['debt_usd'], 2) }}</div> @endif
                                @if($item['debt_vnd'] > 0) <div>{{ number_format($item['debt_vnd'], 0) }}đ</div> @endif
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-3 py-8 text-center text-gray-500">Không có công nợ trong khoảng thời gian này</td></tr>
                    @endforelse
                    
                    @if(count($reportData) > 0)
                    <tr class="bg-gradient-to-r from-gray-100 to-gray-200 font-bold border-t-2">
                        <td colspan="6" class="px-3 py-3">TỔNG CỘNG</td>
                        <td class="px-3 py-3 text-right">
                            @if($exchangeRate > 1)
                                <div>{{ number_format($grandTotalVnd, 0) }}đ</div>
                                @if($totalSaleUsd > 0)
                                <div class="text-xs text-gray-500 font-normal">${{ number_format($totalSaleUsd, 2) }} @if($totalSaleVnd > 0) + {{ number_format($totalSaleVnd, 0) }}đ @endif</div>
                                @endif
                            @else
                                @if($totalSaleUsd > 0) <div>${{ number_format($totalSaleUsd, 2) }}</div> @endif
                                @if($totalSaleVnd > 0) <div>{{ number_format($totalSaleVnd, 0) }}đ</div> @endif
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right text-green-600">
                            @if($exchangeRate > 1)
                                <div>{{ number_format($grandPaidVnd, 0) }}đ</div>
                                @if($totalPaidUsd > 0)
                                <div class="text-xs text-gray-500 font-normal">${{ number_format($totalPaidUsd, 2) }} @if($totalPaidVnd > 0) + {{ number_format($totalPaidVnd, 0) }}đ @endif</div>
                                @endif
                            @else
                                @if($totalPaidUsd > 0) <div>${{ number_format($totalPaidUsd, 2) }}</div> @endif
                                @if($totalPaidVnd > 0) <div>{{ number_format($totalPaidVnd, 0) }}đ</div> @endif
                                @if($totalPaidUsd == 0 && $totalPaidVnd == 0) <div>0đ</div> @endif
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right text-red-600">
                            @if($exchangeRate > 1)
                                <div>{{ number_format($grandDebtVnd, 0) }}đ</div>
                                @if($totalDebtUsd > 0)
                                <div class="text-xs text-gray-500 font-normal">${{ number_format($totalDebtUsd, 2) }} @if($totalDebtVnd > 0) + {{ number_format($totalDebtVnd, 0) }}đ @endif</div>
                                @endif
                            @else
                                @if($totalDebtUsd > 0) <div>${{ number_format($totalDebtUsd, 2) }}</div> @endif
                                @if($totalDebtVnd > 0) <div>{{ number_format($totalDebtVnd, 0) }}đ</div> @endif
                            @endif
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        
        <!-- Summary -->
        @if(count($reportData) > 0)
        <div class="mt-6 bg-gradient-to-r from-gray-50 to-red-50 rounded-lg p-6 border-l-4 border-red-500">
            <h4 class="text-lg font-bold text-gray-800 mb-4"><i class="fas fa-calculator mr-2 text-red-600"></i>Tổng kết công nợ</h4>
            <div class="space-y-3">
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                    <span class="text-gray-700 font-medium">Tổng giá trị đơn hàng:</span>
                    <div class="text-right">
                        @if($exchangeRate > 1)
                            <span class="text-lg font-bold text-blue-700">{{ number_format($grandTotalVnd, 0) }}đ</span>
                            @if($totalSaleUsd > 0)
                            <div class="text-sm text-gray-500">${{ number_format($totalSaleUsd, 2) }} @if($totalSaleVnd > 0) + {{ number_format($totalSaleVnd, 0) }}đ @endif</div>
                            @endif
                        @else
                            <span class="text-lg font-bold text-blue-700">
                                @if($totalSaleUsd > 0) ${{ number_format($totalSaleUsd, 2) }} @endif
                                @if($totalSaleUsd > 0 && $totalSaleVnd > 0) + @endif
                                @if($totalSaleVnd > 0) {{ number_format($totalSaleVnd, 0) }}đ @endif
                            </span>
                        @endif
                    </div>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                    <span class="text-gray-700 font-medium">Đã thanh toán:</span>
                    <div class="text-right">
                        @if($exchangeRate > 1)
                            <span class="text-lg font-bold text-green-700">{{ number_format($grandPaidVnd, 0) }}đ</span>
                            @if($totalPaidUsd > 0)
                            <div class="text-sm text-gray-500">${{ number_format($totalPaidUsd, 2) }} @if($totalPaidVnd > 0) + {{ number_format($totalPaidVnd, 0) }}đ @endif</div>
                            @endif
                        @else
                            <span class="text-lg font-bold text-green-700">
                                @if($totalPaidUsd > 0) ${{ number_format($totalPaidUsd, 2) }} @endif
                                @if($totalPaidUsd > 0 && $totalPaidVnd > 0) + @endif
                                @if($totalPaidVnd > 0) {{ number_format($totalPaidVnd, 0) }}đ @endif
                                @if($totalPaidUsd == 0 && $totalPaidVnd == 0) 0đ @endif
                            </span>
                        @endif
                    </div>
                </div>
                <div class="flex justify-between items-center py-3 bg-gradient-to-r from-red-100 to-orange-100 rounded-lg px-4">
                    <span class="text-gray-800 font-bold text-lg">TỔNG CÔNG NỢ:</span>
                    <div class="text-right">
                        @if($exchangeRate > 1)
                            <span class="text-xl font-bold text-red-700">{{ number_format($grandDebtVnd, 0) }}đ</span>
                            @if($totalDebtUsd > 0)
                            <div class="text-sm text-red-600">${{ number_format($totalDebtUsd, 2) }} @if($totalDebtVnd > 0) + {{ number_format($totalDebtVnd, 0) }}đ @endif</div>
                            @endif
                        @else
                            <span class="text-xl font-bold text-red-700">
                                @if($totalDebtUsd > 0) ${{ number_format($totalDebtUsd, 2) }} @endif
                                @if($totalDebtUsd > 0 && $totalDebtVnd > 0) + @endif
                                @if($totalDebtVnd > 0) {{ number_format($totalDebtVnd, 0) }}đ @endif
                                @if($totalDebtUsd == 0 && $totalDebtVnd == 0) 0đ @endif
                            </span>
                        @endif
                    </div>
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
        <h2 class="text-base font-bold mt-2">
            Debt Report
            @if($reportType == 'cumulative')
                <br>Cumulative to {{ $toDate->format('d/m/Y') }}
            @else
                <br>{{ $fromDate->format('d/m/Y') }} - {{ $toDate->format('d/m/Y') }}
            @endif
        </h2>
    </div>

    <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; font-size: 9px;">
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th style="border: 1px solid #000; padding: 4px;">No.</th>
                <th style="border: 1px solid #000; padding: 4px;">Date</th>
                <th style="border: 1px solid #000; padding: 4px;">Invoice</th>
                <th style="border: 1px solid #000; padding: 4px;">ID Code</th>
                <th style="border: 1px solid #000; padding: 4px;">Customer</th>
                <th style="border: 1px solid #000; padding: 4px;">Phone</th>
                <th style="border: 1px solid #000; padding: 4px;">Total</th>
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
                <td style="border: 1px solid #000; padding: 3px;">{{ $item['customer_phone'] }}</td>
                <td style="border: 1px solid #000; padding: 3px; text-align: right;">
                    @if($item['total_usd'] > 0) ${{ number_format($item['total_usd'], 2) }} @endif
                    @if($item['total_vnd'] > 0) {{ number_format($item['total_vnd'], 0) }}đ @endif
                </td>
                <td style="border: 1px solid #000; padding: 3px; text-align: right;">
                    @if($item['is_usd_only'] ?? false)
                        ${{ number_format($item['paid_usd'], 2) }}
                    @elseif($item['is_vnd_only'] ?? false)
                        {{ number_format($item['paid_vnd'], 0) }}đ
                    @else
                        @if($item['paid_usd'] > 0) ${{ number_format($item['paid_usd'], 2) }} @endif
                        @if($item['paid_vnd'] > 0) {{ number_format($item['paid_vnd'], 0) }}đ @endif
                    @endif
                </td>
                <td style="border: 1px solid #000; padding: 3px; text-align: right;">
                    @if($item['is_usd_only'] ?? false)
                        ${{ number_format($item['debt_usd'], 2) }}
                    @elseif($item['is_vnd_only'] ?? false)
                        {{ number_format($item['debt_vnd'], 0) }}đ
                    @else
                        @if($item['debt_usd'] > 0) ${{ number_format($item['debt_usd'], 2) }} @endif
                        @if($item['debt_vnd'] > 0) {{ number_format($item['debt_vnd'], 0) }}đ @endif
                    @endif
                </td>
            </tr>
            @endforeach
            <tr style="background-color: #e0e0e0; font-weight: bold;">
                <td colspan="6" style="border: 1px solid #000; padding: 5px;">GRAND TOTAL</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: right;">
                    @if($totalSaleUsd > 0) ${{ number_format($totalSaleUsd, 2) }} @endif
                    @if($totalSaleVnd > 0) {{ number_format($totalSaleVnd, 0) }}đ @endif
                </td>
                <td style="border: 1px solid #000; padding: 5px; text-align: right;">
                    @if($totalPaidUsd > 0) ${{ number_format($totalPaidUsd, 2) }} @endif
                    @if($totalPaidVnd > 0) {{ number_format($totalPaidVnd, 0) }}đ @endif
                    @if($totalPaidUsd == 0 && $totalPaidVnd == 0) $0.00 @endif
                </td>
                <td style="border: 1px solid #000; padding: 5px; text-align: right;">
                    @if($totalDebtUsd > 0) ${{ number_format($totalDebtUsd, 2) }} @endif
                    @if($totalDebtVnd > 0) {{ number_format($totalDebtVnd, 0) }}đ @endif
                </td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 15px; font-size: 10px;">
        <p style="margin: 3px 0;"><strong>Total Sales:</strong> 
            @if($totalSaleUsd > 0) ${{ number_format($totalSaleUsd, 2) }} @endif
            @if($totalSaleUsd > 0 && $totalSaleVnd > 0) + @endif
            @if($totalSaleVnd > 0) {{ number_format($totalSaleVnd, 0) }}đ @endif
        </p>
        <p style="margin: 3px 0;"><strong>Total Paid:</strong> 
            @if($totalPaidUsd > 0) ${{ number_format($totalPaidUsd, 2) }} @endif
            @if($totalPaidUsd > 0 && $totalPaidVnd > 0) + @endif
            @if($totalPaidVnd > 0) {{ number_format($totalPaidVnd, 0) }}đ @endif
            @if($totalPaidUsd == 0 && $totalPaidVnd == 0) $0.00 @endif
        </p>
        <p style="border-top: 2px solid #000; border-bottom: 2px double #000; padding: 6px 0; margin-top: 6px; font-weight: bold;">
            <strong>TOTAL DEBT:</strong> 
            @if($totalDebtUsd > 0) ${{ number_format($totalDebtUsd, 2) }} @endif
            @if($totalDebtUsd > 0 && $totalDebtVnd > 0) + @endif
            @if($totalDebtVnd > 0) {{ number_format($totalDebtVnd, 0) }}đ @endif
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
        case 'all':
            // Tất cả công nợ - lũy kế từ đầu năm đến nay
            fromDate = new Date(today.getFullYear(), 0, 1);
            toDate = today;
            document.querySelector('select[name="report_type"]').value = 'cumulative';
            break;
        case 'month':
            fromDate = new Date(today.getFullYear(), today.getMonth(), 1);
            toDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            document.querySelector('select[name="report_type"]').value = 'month';
            break;
        case 'lastmonth':
            fromDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            toDate = new Date(today.getFullYear(), today.getMonth(), 0);
            document.querySelector('select[name="report_type"]').value = 'month';
            break;
        case 'cumulative':
            fromDate = new Date(today.getFullYear(), 0, 1);
            toDate = today;
            document.querySelector('select[name="report_type"]').value = 'cumulative';
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
