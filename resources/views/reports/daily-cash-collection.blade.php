@extends('layouts.app')

@section('title', 'Báo cáo')
@section('page-title', 'Báo cáo')
@section('page-description', 'Daily Cash Collection Report')

@section('content')
    <x-alert />

    <!-- Filter Form -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6 no-print">
        <form method="GET" action="{{ route('reports.daily-cash-collection') }}" class="space-y-4">
            <!-- Row 1: Date range, Showroom, Exchange Rate -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @if($canFilterByDate)
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-calendar-day mr-2 text-blue-500"></i>Từ ngày
                        </label>
                        <input type="date" name="from_date" value="{{ request('from_date', $fromDate->format('Y-m-d')) }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-calendar-day mr-2 text-red-500"></i>Đến ngày
                        </label>
                        <input type="date" name="to_date" value="{{ request('to_date', $toDate->format('Y-m-d')) }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                @else
                    <!-- Hidden inputs để giữ giá trị mặc định khi không có quyền lọc -->
                    <input type="hidden" name="from_date" value="{{ $fromDate->format('Y-m-d') }}">
                    <input type="hidden" name="to_date" value="{{ $toDate->format('Y-m-d') }}">
                @endif

                @if($canFilterByShowroom)
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-store mr-2 text-green-500"></i>Showroom
                        </label>
                        <select name="showroom_id"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">-- Tất cả Showroom --</option>
                            @foreach($showrooms as $showroom)
                                <option value="{{ $showroom->id }}" {{ $showroomId == $showroom->id ? 'selected' : '' }}>
                                    {{ $showroom->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-exchange-alt mr-2 text-purple-500"></i>Tỷ giá (VND/USD)
                    </label>
                    <input type="text" name="exchange_rate" value="{{ request('exchange_rate', '') }}"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Nhập tỷ giá (VD: 25000)">
                </div>
            </div>

            <!-- Row 2: Employee, Customer, Payment Type, Actions -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                @if($canFilterByUser)
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-user-tie mr-2 text-indigo-500"></i>Nhân viên
                        </label>
                        <select name="employee_id"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">-- Tất cả nhân viên --</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-user mr-2 text-pink-500"></i>Khách hàng
                    </label>
                    <select name="customer_id"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Tất cả khách hàng --</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-money-bill-wave mr-2 text-green-500"></i>Loại thanh toán
                    </label>
                    <select name="payment_type"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Tất cả --</option>
                        <option value="cash" {{ request('payment_type') == 'cash' ? 'selected' : '' }}>Tiền mặt (Cash)
                        </option>
                        <option value="card_transfer" {{ request('payment_type') == 'card_transfer' ? 'selected' : '' }}>Thẻ +
                            Chuyển khoản</option>
                    </select>
                </div>

                <!-- Action Buttons - Row 1: Primary Actions -->
                <div class="md:col-span-2 flex flex-col gap-3">
                    <div class="flex gap-2">
                        <button type="submit"
                            class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-2.5 rounded-lg font-semibold shadow-lg transition-all duration-200">
                            <i class="fas fa-search mr-2"></i>Xem báo cáo
                        </button>
                        <a href="{{ route('reports.daily-cash-collection') }}"
                            class="flex-1 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white px-6 py-2.5 rounded-lg font-semibold shadow-lg transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-times-circle mr-2"></i>Xóa bộ lọc
                        </a>
                    </div>

                    <!-- Row 2: Export Actions -->
                    <div class="flex gap-2">
                        <!-- Cho phép xuất Excel/PDF bất kể có tỷ giá hay không -->
                        <a href="{{ route('reports.daily-cash-collection.export.excel', request()->all()) }}"
                            class="flex-1 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-4 py-2.5 rounded-lg font-semibold shadow-lg transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-file-excel mr-2"></i>Excel
                        </a>
                        <a href="{{ route('reports.daily-cash-collection.export.pdf', request()->all()) }}"
                            class="flex-1 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white px-4 py-2.5 rounded-lg font-semibold shadow-lg transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-file-pdf mr-2"></i>PDF
                        </a>

                        @if($canPrint)
                            <button type="button" onclick="window.print()"
                                class="flex-1 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white px-4 py-2.5 rounded-lg font-semibold shadow-lg transition-all duration-200">
                                <i class="fas fa-print mr-2"></i>In báo cáo
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </form>

        <!-- Quick Filters -->
        <div class="mt-4 flex gap-2 flex-wrap">
            <span class="text-sm text-gray-600 font-semibold">Lọc nhanh:</span>
            <button onclick="setDateRange('today')"
                class="px-3 py-1 text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition">
                <i class="fas fa-calendar-day mr-1"></i>Hôm nay
            </button>
            <button onclick="setDateRange('week')"
                class="px-3 py-1 text-xs bg-green-100 hover:bg-green-200 text-green-700 rounded-lg transition">
                <i class="fas fa-calendar-week mr-1"></i>Tuần này
            </button>
            <button onclick="setDateRange('month')"
                class="px-3 py-1 text-xs bg-purple-100 hover:bg-purple-200 text-purple-700 rounded-lg transition">
                <i class="fas fa-calendar-alt mr-1"></i>Tháng này
            </button>
            <button onclick="setDateRange('year')"
                class="px-3 py-1 text-xs bg-orange-100 hover:bg-orange-200 text-orange-700 rounded-lg transition">
                <i class="fas fa-calendar mr-1"></i>Năm nay
            </button>
        </div>

        <script>
            function setDateRange(type) {
                const today = new Date();
                let fromDate, toDate;

                switch (type) {
                    case 'today':
                        fromDate = toDate = today;
                        break;
                    case 'week':
                        const dayOfWeek = today.getDay();
                        const diff = today.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1);
                        fromDate = new Date(today.setDate(diff));
                        toDate = new Date();
                        toDate.setDate(fromDate.getDate() + 6);
                        break;
                    case 'month':
                        fromDate = new Date(today.getFullYear(), today.getMonth(), 1);
                        toDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
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
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }
        </script>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 gap-4 mb-6 no-print">
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    @php
                        $paymentTypeLabel = 'Cash + Card';
                        $paymentTypeIcon = 'fa-cash-register';
                        if (request('payment_type') == 'cash') {
                            $paymentTypeLabel = '💵 Tiền mặt (Cash)';
                            $paymentTypeIcon = 'fa-money-bill-wave';
                        } elseif (request('payment_type') == 'card_transfer') {
                            $paymentTypeLabel = '💳 Thẻ + Chuyển khoản';
                            $paymentTypeIcon = 'fa-credit-card';
                        }
                    @endphp
                    <p class="text-sm opacity-90 mb-1">Collection: {{ $paymentTypeLabel }}</p>
                    @if($totalCollectionUsd > 0 && $exchangeRate <= 1)
                        <!-- Có USD nhưng chưa nhập tỷ giá - Hiển thị cả 2 đơn vị tiền tệ -->
                        <p class="text-lg font-bold">
                            VND: {{ number_format($totalCollectionVnd, 0) }}đ
                        </p>
                        <p class="text-base font-medium opacity-90 mt-1">
                            + USD: ${{ number_format($totalCollectionUsd, 2) }}
                        </p>
                    @else
                        <!-- Không có USD hoặc đã nhập tỷ giá -->
                        <p class="text-2xl font-bold">{{ number_format($cashCollectionVnd + $cardCollectionVnd, 0) }} đ</p>
                        @if(!request('payment_type'))
                            <p class="text-xs opacity-75 mt-1">Cash: {{ number_format($cashCollectionVnd, 0) }}đ | Card:
                                {{ number_format($cardCollectionVnd, 0) }}đ</p>
                        @endif
                    @endif
                    <p class="text-xs opacity-75 mt-1 italic">(Không yêu cầu nhập tỷ giá)</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-3">
                    <i class="fas {{ $paymentTypeIcon }} text-3xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Table (for screen) -->
    <div id="screen-view" class="bg-white rounded-xl shadow-lg overflow-hidden no-print">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-table mr-2 text-blue-500"></i>Chi tiết giao dịch
                    @if(request('payment_type') == 'cash')
                        <span class="text-sm bg-green-100 text-green-700 px-2 py-1 rounded-full ml-2">💵 Tiền mặt</span>
                    @elseif(request('payment_type') == 'card_transfer')
                        <span class="text-sm bg-blue-100 text-blue-700 px-2 py-1 rounded-full ml-2">💳 Thẻ + CK</span>
                    @endif
                    <span class="text-sm font-normal text-gray-600 ml-2">
                        ({{ $fromDate->format('d/m/Y') }} - {{ $toDate->format('d/m/Y') }})
                    </span>
                </h3>
                <span class="text-sm text-gray-600">{{ count($reportData) }} items</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border-r border-gray-300">No.
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border-r border-gray-300">Invoice
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border-r border-gray-300">ID Code
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 border-r border-gray-300">
                                Customer name</th>
                            <th class="px-3 py-2 text-center text-xs font-bold text-gray-700 border-l-2 border-gray-400"
                                colspan="2">Adjustment</th>
                            <th class="px-3 py-2 text-center text-xs font-bold text-gray-700 border-l-2 border-gray-400"
                                colspan="2">Collection</th>
                        </tr>
                        <tr class="bg-gray-50 border-b text-xs">
                            <th class="border-r border-gray-300"></th>
                            <th class="border-r border-gray-300"></th>
                            <th class="border-r border-gray-300"></th>
                            <th class="border-r border-gray-300"></th>
                            <th class="px-2 py-1 text-right text-gray-600 border-l-2 border-gray-400">USD</th>
                            <th class="px-2 py-1 text-right text-gray-600">VND</th>
                            <th class="px-2 py-1 text-right text-gray-600 border-l-2 border-gray-400">USD</th>
                            <th class="px-2 py-1 text-right text-gray-600">VND</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData as $index => $item)
                            <tr class="border-b border-gray-100 hover:bg-blue-50 transition-colors">
                                <td class="px-3 py-2 border-r border-gray-300">{{ $index + 1 }}</td>
                                <td class="px-3 py-2 font-medium text-blue-600 border-r border-gray-300">
                                    {{ $item['invoice_code'] }}</td>
                                <td class="px-3 py-2 font-medium border-r border-gray-300">{{ $item['id_code'] }}</td>
                                <td class="px-3 py-2 border-r border-gray-300">{{ $item['customer_name'] }}</td>

                                <td class="px-2 py-2 text-right text-red-600 border-l-2 border-gray-400">
                                    @if($item['adjustment_usd'] != 0)
                                        {{ $item['adjustment_usd'] == floor($item['adjustment_usd']) ? number_format($item['adjustment_usd'], 0) : number_format($item['adjustment_usd'], 2) }}
                                    @endif
                                </td>
                                <td class="px-2 py-2 text-right text-red-600">
                                    {{ $item['adjustment_vnd'] != 0 ? number_format($item['adjustment_vnd'], 0) : '' }}</td>

                                <td class="px-2 py-2 text-right font-semibold text-green-600 border-l-2 border-gray-400">
                                    @if($item['collection_usd'] > 0)
                                        {{ $item['collection_usd'] == floor($item['collection_usd']) ? number_format($item['collection_usd'], 0) : number_format($item['collection_usd'], 2) }}
                                    @endif
                                </td>
                                <td class="px-2 py-2 text-right font-semibold text-green-600">
                                    {{ $item['collection_vnd'] > 0 ? number_format($item['collection_vnd'], 0) : '' }}</td>
                            </tr>
                        @endforeach


                        <tr class="bg-gradient-to-r from-gray-100 to-gray-200 font-bold border-t-2">
                            <td colspan="4" class="px-3 py-3 border-r border-gray-300">GRAND TOTAL</td>

                            <td class="px-2 py-3 text-right border-l-2 border-gray-400">
                                @if($totalAdjustmentUsd != 0)
                                    ${{ $totalAdjustmentUsd == floor($totalAdjustmentUsd) ? number_format($totalAdjustmentUsd, 0) : number_format($totalAdjustmentUsd, 2) }}
                                    @if($exchangeRate > 1)
                                        <div class="text-xs text-gray-600 font-normal">(= {{ number_format($totalAdjustmentUsd * $exchangeRate, 0) }}đ)</div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-2 py-3 text-right">
                                {{ $totalAdjustmentVnd != 0 ? number_format($totalAdjustmentVnd, 0) : '' }}</td>

                            <td class="px-2 py-3 text-right text-green-600 border-l-2 border-gray-400">
                                ${{ $totalCollectionUsd == floor($totalCollectionUsd) ? number_format($totalCollectionUsd, 0) : number_format($totalCollectionUsd, 2) }}
                                @if($totalCollectionUsd > 0 && $exchangeRate > 1)
                                    <div class="text-xs text-gray-600 font-normal">(= {{ number_format($totalCollectionUsd * $exchangeRate, 0) }}đ)</div>
                                @endif
                            </td>
                            <td class="px-2 py-3 text-right text-green-600">{{ number_format($totalCollectionVnd, 0) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Summary Section -->
            <div class="mt-6 bg-gradient-to-r from-gray-50 to-blue-50 rounded-lg p-6 border-l-4 border-blue-500">
                <h4 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-calculator mr-2 text-blue-600"></i>Tổng kết thu tiền
                </h4>

                @if($totalCollectionUsd > 0 && $exchangeRate <= 1)
                    <!-- Có USD nhưng chưa nhập tỷ giá - Hiển thị tách biệt USD và VND -->
                    <div class="space-y-3">
                        @if(!request('payment_type') || request('payment_type') == 'cash')
                            <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                <span class="text-gray-700 font-medium">Collection in CASH:</span>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-green-700">VND {{ number_format($cashCollectionVnd, 0) }}</div>
                                    @if($cashCollectionUsd > 0)
                                        <div class="text-sm text-green-600">+ USD ${{ $cashCollectionUsd == floor($cashCollectionUsd) ? number_format($cashCollectionUsd, 0) : number_format($cashCollectionUsd, 2) }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if(!request('payment_type') || request('payment_type') == 'card_transfer')
                            <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                <span class="text-gray-700 font-medium">In Credit Card + Transfer:</span>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-blue-700">VND {{ number_format($cardCollectionVnd, 0) }}</div>
                                    @if($cardCollectionUsd > 0)
                                        <div class="text-sm text-blue-600">+ USD ${{ $cardCollectionUsd == floor($cardCollectionUsd) ? number_format($cardCollectionUsd, 0) : number_format($cardCollectionUsd, 2) }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="bg-gradient-to-r from-blue-100 to-purple-100 rounded-lg px-4 py-3 mt-2">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-gray-800 font-bold text-lg">Total VND:</span>
                                <span class="text-xl font-bold text-purple-700">VND {{ number_format($totalCollectionVnd, 0) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-800 font-bold text-lg">Total USD:</span>
                                <span class="text-xl font-bold text-blue-700">${{ $totalCollectionUsd == floor($totalCollectionUsd) ? number_format($totalCollectionUsd, 0) : number_format($totalCollectionUsd, 2) }}</span>
                            </div>
                            <p class="text-xs text-gray-500 italic mt-2"><i class="fas fa-info-circle mr-1"></i>(Chưa quy đổi do không nhập tỷ giá)</p>
                        </div>
                    </div>
                @else
                    <!-- Không có USD hoặc đã nhập tỷ giá -->
                    <div class="space-y-3">
                        @if(!request('payment_type') || request('payment_type') == 'cash')
                            <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                <span class="text-gray-700 font-medium">Collection in CASH:</span>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-green-700">VND {{ number_format($cashCollectionVnd, 0) }}</div>
                                    @if($cashCollectionUsd > 0)
                                        <div class="text-sm text-green-600">(incl. ${{ $cashCollectionUsd == floor($cashCollectionUsd) ? number_format($cashCollectionUsd, 0) : number_format($cashCollectionUsd, 2) }})</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if(!request('payment_type') || request('payment_type') == 'card_transfer')
                            <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                <span class="text-gray-700 font-medium">In Credit Card + Transfer:</span>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-blue-700">VND {{ number_format($cardCollectionVnd, 0) }}</div>
                                    @if($cardCollectionUsd > 0)
                                        <div class="text-sm text-blue-600">(incl. ${{ $cardCollectionUsd == floor($cardCollectionUsd) ? number_format($cardCollectionUsd, 0) : number_format($cardCollectionUsd, 2) }})</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <div class="flex justify-between items-center py-3 bg-gradient-to-r from-blue-100 to-purple-100 rounded-lg px-4 mt-2">
                            <span class="text-gray-800 font-bold text-lg">Grand Total:</span>
                            <span class="text-xl font-bold text-purple-700">VND {{ number_format($cashCollectionVnd + $cardCollectionVnd, 0) }}</span>
                        </div>
                        @if($totalCollectionUsd > 0 && $exchangeRate > 1)
                            <p class="text-xs text-gray-500 italic mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                ({{ $totalCollectionUsd == floor($totalCollectionUsd) ? number_format($totalCollectionUsd, 0) : number_format($totalCollectionUsd, 2) }} USD × {{ number_format($exchangeRate, 0) }} + {{ number_format($totalCollectionVnd, 0) }} VND)
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Print View -->
    <div id="print-view" class="print-only" style="display: none;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 10px;">
            <div style="text-align: left;">
                <strong
                    style="font-size: 12px;">{{ $selectedShowroom ? $selectedShowroom->name : 'Ben Thanh Art Gallery' }}</strong><br>
                @if($selectedShowroom)
                    {{ $selectedShowroom->address }}<br>
                    Tel: {{ $selectedShowroom->phone }}
                @else
                    07 Nguyen Thiep - Dist.1, HCMC<br>
                    Tel: (84-8) 3823 3001 - 3823 8101
                @endif
            </div>
            <div style="text-align: right;">
                <strong>Page 1</strong><br>
                Date: {{ now()->format('d/m/Y') }}
            </div>
        </div>

        <div class="text-center mb-4">
            <h2 class="text-base font-bold mt-2">
                @if(request('payment_type') == 'cash')
                    Cash Collection Report
                @elseif(request('payment_type') == 'card_transfer')
                    Card & Transfer Collection Report
                @else
                    Daily Cash Collection Report
                @endif
                @if($fromDate->format('Y-m-d') == $toDate->format('Y-m-d'))
                    of {{ $fromDate->format('d/m/Y') }}
                @else
                    from {{ $fromDate->format('d/m/Y') }} to {{ $toDate->format('d/m/Y') }}
                @endif
            </h2>
        </div>

        <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; font-size: 9px;">
            <thead>
                <tr style="background-color: #f0f0f0;">
                    <th style="border: 1px solid #000; padding: 4px;">No.</th>
                    <th style="border: 1px solid #000; padding: 4px;">Invoice</th>
                    <th style="border: 1px solid #000; padding: 4px;">ID Code</th>
                    <th style="border: 1px solid #000; padding: 4px;">Customer name</th>
                    <th style="border: 1px solid #000; padding: 4px;" colspan="2">Deposit</th>
                    <th style="border: 1px solid #000; padding: 4px;" colspan="2">Adjustment</th>
                    <th style="border: 1px solid #000; padding: 4px;" colspan="2">Collection</th>
                </tr>
                <tr style="background-color: #f9f9f9; font-size: 8px;">
                    <th style="border: 1px solid #000; padding: 2px;"></th>
                    <th style="border: 1px solid #000; padding: 2px;"></th>
                    <th style="border: 1px solid #000; padding: 2px;"></th>
                    <th style="border: 1px solid #000; padding: 2px;"></th>
                    <th style="border: 1px solid #000; padding: 2px;">USD</th>
                    <th style="border: 1px solid #000; padding: 2px;">VND</th>
                    <th style="border: 1px solid #000; padding: 2px;">USD</th>
                    <th style="border: 1px solid #000; padding: 2px;">VND</th>
                    <th style="border: 1px solid #000; padding: 2px;">USD</th>
                    <th style="border: 1px solid #000; padding: 2px;">VND</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $index => $item)
                    <tr>
                        <td style="border: 1px solid #000; padding: 3px;">{{ $index + 1 }}</td>
                        <td style="border: 1px solid #000; padding: 3px;">{{ $item['invoice_code'] }}</td>
                        <td style="border: 1px solid #000; padding: 3px;">{{ $item['id_code'] }}</td>
                        <td style="border: 1px solid #000; padding: 3px;">{{ $item['customer_name'] }}</td>

                        <td style="border: 1px solid #000; padding: 3px; text-align: right;"></td>
                        <td style="border: 1px solid #000; padding: 3px; text-align: right;"></td>

                        <td style="border: 1px solid #000; padding: 3px; text-align: right;">
                             @if($item['adjustment_usd'] != 0)
                                @php
                                    $val = $item['adjustment_usd'];
                                    $formatted = $val == floor($val) ? number_format($val, 0) : number_format($val, 2);
                                @endphp
                                {{ $formatted }}
                             @endif
                        </td>
                        <td style="border: 1px solid #000; padding: 3px; text-align: right;">
                            {{ $item['adjustment_vnd'] != 0 ? number_format($item['adjustment_vnd'], 0) : '' }}</td>

                        <td style="border: 1px solid #000; padding: 3px; text-align: right;">
                             @if($item['collection_usd'] > 0)
                                @php
                                    $val = $item['collection_usd'];
                                    $formatted = $val == floor($val) ? number_format($val, 0) : number_format($val, 2);
                                @endphp
                                {{ $formatted }}
                             @endif
                        </td>
                        <td style="border: 1px solid #000; padding: 3px; text-align: right;">
                            {{ $item['collection_vnd'] > 0 ? number_format($item['collection_vnd'], 0) : '' }}</td>
                    </tr>
                @endforeach


                <tr style="background-color: #e0e0e0; font-weight: bold;">
                    <td colspan="4" style="border: 1px solid #000; padding: 5px;">Grand total</td>
                    <td style="border: 1px solid #000; padding: 5px; text-align: right;"></td>
                    <td style="border: 1px solid #000; padding: 5px; text-align: right;"></td>

                    <td style="border: 1px solid #000; padding: 5px; text-align: right;">
                        @if($totalAdjustmentUsd != 0)
                            @php
                                $val = $totalAdjustmentUsd;
                                $formatted = $val == floor($val) ? number_format($val, 0) : number_format($val, 2);
                            @endphp
                            {{ $formatted }}
                        @endif
                    </td>
                    <td style="border: 1px solid #000; padding: 5px; text-align: right;">
                        {{ $totalAdjustmentVnd != 0 ? number_format($totalAdjustmentVnd, 0) : '' }}</td>

                    <td style="border: 1px solid #000; padding: 5px; text-align: right;">
                        @if($totalCollectionUsd > 0)
                            @php
                                $val = $totalCollectionUsd;
                                $formatted = $val == floor($val) ? number_format($val, 0) : number_format($val, 2);
                            @endphp
                            {{ $formatted }}
                        @endif
                    </td>
                    <td style="border: 1px solid #000; padding: 5px; text-align: right;">
                        {{ number_format($totalCollectionVnd, 0) }}</td>
                </tr>
            </tbody>
        </table>


        <div style="margin-top: 15px; font-size: 10px;">
            @if(isset($exchangeRate) && $exchangeRate > 1)
                {{-- Combined Display (Rate > 1) --}}
                <p style="margin: 3px 0;">
                    <strong>Collection in CASH:</strong> VND {{ number_format($cashCollectionVnd, 0) }}
                    @if(isset($cashCollectionUsd) && $cashCollectionUsd > 0)
                         @php
                            $val = $cashCollectionUsd;
                            $formatted = $val == floor($val) ? number_format($val, 0) : number_format($val, 2);
                        @endphp
                        (incl. USD ${{ $formatted }})
                    @endif
                </p>
                <p style="margin: 3px 0;">
                    <strong>in Credit Card + Transfer:</strong> VND {{ number_format($cardCollectionVnd, 0) }}
                    @if(isset($cardCollectionUsd) && $cardCollectionUsd > 0)
                         @php
                            $val = $cardCollectionUsd;
                            $formatted = $val == floor($val) ? number_format($val, 0) : number_format($val, 2);
                        @endphp
                        (incl. USD ${{ $formatted }})
                    @endif
                </p>
                 <p style="border-top: 2px solid #000; border-bottom: 2px double #000; padding: 6px 0; margin-top: 6px; font-weight: bold; font-size: 11px;">
                    <strong>GRAND TOTAL:</strong> VND {{ number_format($cashCollectionVnd + $cardCollectionVnd, 0) }}
                </p>
            @else
                {{-- Separated Display (Rate = 1 or not provided) --}}
                 @if(!request('payment_type') || request('payment_type') == 'cash')
                    <p style="margin: 3px 0;"><strong>Collection in CASH:</strong> VND {{ number_format($cashCollectionVnd, 0) }}
                        @if(isset($cashCollectionUsd) && $cashCollectionUsd > 0)
                            @php
                                $val = $cashCollectionUsd;
                                $formatted = $val == floor($val) ? number_format($val, 0) : number_format($val, 2);
                            @endphp
                            + USD ${{ $formatted }}
                        @endif
                    </p>
                @endif
                @if(!request('payment_type') || request('payment_type') == 'card_transfer')
                    <p style="margin: 3px 0;"><strong>in Credit Card + Transfer:</strong> VND {{ number_format($cardCollectionVnd, 0) }}
                        @if(isset($cardCollectionUsd) && $cardCollectionUsd > 0)
                            @php
                                $val = $cardCollectionUsd;
                                $formatted = $val == floor($val) ? number_format($val, 0) : number_format($val, 2);
                            @endphp
                            + USD ${{ $formatted }}
                        @endif
                    </p>
                @endif
                
                <p
                    style="border-top: 2px solid #000; border-bottom: 2px double #000; padding: 6px 0; margin-top: 6px; font-weight: bold; font-size: 11px;">
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
                    <strong>GRAND TOTAL:</strong> VND {{ number_format($totalVnd, 0) }}{{ $usdPart }}
                </p>
            @endif
        </div>
    </div>

    @push('styles')
        <style>
            @media print {
                .no-print {
                    display: none !important;
                }

                #screen-view {
                    display: none !important;
                }

                #print-view {
                    display: block !important;
                }

                @page {
                    margin: 0.8cm;
                }

                body {
                    print-color-adjust: exact;
                    -webkit-print-color-adjust: exact;
                }
            }

            .print-only {
                display: none;
            }
        </style>
    @endpush

@endsection