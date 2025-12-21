@extends('layouts.app')

@section('title', 'Bán hàng')
@section('page-title', 'Bán hàng')
@section('page-description', 'Quản lý tất cả các giao dịch bán hàng')

@section('header-actions')
@notArchive
@hasPermission('sales', 'can_create')
<a href="{{ route('sales.create') }}" class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700 transition-colors text-sm whitespace-nowrap">
    <i class="fas fa-plus mr-1"></i>Tạo hóa đơn
</a>
@endhasPermission
@endnotArchive
@endsection

@section('content')
<x-alert />

<!-- Quick Stats
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Tổng doanh thu</p>
                <p class="text-xl font-bold text-green-600">{{ number_format($sales->sum('total_vnd')) }}đ</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-chart-line text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Đã thu</p>
                <p class="text-xl font-bold text-blue-600">{{ number_format($sales->sum('paid_amount')) }}đ</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-money-bill-wave text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Công nợ</p>
                <p class="text-xl font-bold text-red-600">{{ number_format($sales->sum('debt_amount')) }}đ</p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Số đơn hàng</p>
                <p class="text-xl font-bold text-purple-600">{{ $sales->total() }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-file-invoice text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>
</div> -->

<div class="bg-white rounded-xl shadow-lg p-4 glass-effect">
    <!-- Search and Filter - Simplified for elderly users -->
    <div class="bg-gray-50 p-4 rounded-lg mb-4">
        <form method="GET" action="{{ route('sales.index') }}" id="filter-form">
            <!-- Main Row: Search + Date + Status -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
                @if($canSearch)
                <!-- Search with suggestion -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Tìm kiếm
                    </label>
                    <div class="relative">
                        <input type="text" 
                               id="search-input" 
                               name="search" 
                               value="{{ request('search') }}" 
                               class="w-full pl-8 pr-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Nhập mã HD, tên KH, SĐT..."
                               autocomplete="off">
                        <i class="fas fa-search absolute left-2.5 top-2.5 text-gray-400"></i>
                        
                        <!-- Search suggestions dropdown -->
                        <div id="search-suggestions" class="hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-96 overflow-y-auto">
                            <!-- Suggestions will be loaded here -->
                        </div>
                    </div>
                </div>
                @endif

                @if($canFilterByDate)
                <!-- Date From -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Từ ngày
                    </label>
                    <input type="date" 
                           name="from_date" 
                           value="{{ request('from_date') }}" 
                           class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Đến ngày
                    </label>
                    <input type="date" 
                           name="to_date" 
                           value="{{ request('to_date') }}" 
                           class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                @endif
            </div>       

            <!-- Second Row: Status + Dynamic Filter -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                @if($canFilterByStatus)
                <!-- Payment Status -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Trạng thái TT
                    </label>
                    <select name="payment_status" 
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Tất cả --</option>
                        <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Đã Thanh Toán</option>
                        <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Thanh Toán một phần</option>
                        <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Chưa Thanh Toán</option>
                        <option value="cancelled" {{ request('payment_status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                    </select>
                </div>
                @else
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Trạng thái TT
                    </label>
                    <div class="flex items-center gap-2 px-3 py-2 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <i class="fas fa-lock text-yellow-600"></i>
                        <span class="text-xs text-yellow-700">Không có quyền lọc</span>
                    </div>
                </div>
                @endif

                <!-- Dynamic Filter Type Selector -->
                @if($canFilterByShowroom || $canFilterByUser)
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        <i class="fas fa-filter mr-1"></i>Lọc thêm theo
                    </label>
                    <select id="filter-type" 
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            onchange="showFilterOptions(this.value)">
                        <option value="">-- Chọn loại lọc --</option>
                        <option value="amount" {{ request('min_amount') || request('max_amount') ? 'selected' : '' }}>Theo số tiền</option>
                        <option value="debt" {{ request('has_debt') !== null ? 'selected' : '' }}>Theo công nợ</option>
                        @if($canFilterByShowroom)
                        <option value="showroom" {{ request('showroom_id') ? 'selected' : '' }}>Theo showroom</option>
                        @endif
                        @if($canFilterByUser)
                        <option value="user" {{ request('user_id') ? 'selected' : '' }}>Theo nhân viên</option>
                        @endif
                    </select>
                </div>
                @else
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        <i class="fas fa-filter mr-1"></i>Lọc thêm theo
                    </label>
                    <div class="flex items-center gap-2 px-3 py-2 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <i class="fas fa-lock text-yellow-600"></i>
                        <span class="text-xs text-yellow-700">Không có quyền lọc</span>
                    </div>
                </div>
                @endif

                <!-- Dynamic Filter Value (changes based on filter type) -->
                <div id="filter-value-container">
                    <!-- Amount Filter -->
                    <div id="filter-amount" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Khoảng tiền (VNĐ)</label>
                        <div class="flex gap-2">
                            <input type="number" 
                                   name="min_amount" 
                                   value="{{ request('min_amount') }}" 
                                   class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Từ">
                            <input type="number" 
                                   name="max_amount" 
                                   value="{{ request('max_amount') }}" 
                                   class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Đến">
                        </div>
                    </div>

                    <!-- Debt Filter -->
                    <div id="filter-debt" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tình trạng công nợ</label>
                        <select name="has_debt" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">-- Tất cả --</option>
                            <option value="1" {{ request('has_debt') == '1' ? 'selected' : '' }}>⚠️ Có công nợ</option>
                            <option value="0" {{ request('has_debt') == '0' ? 'selected' : '' }}>✓ Không công nợ</option>
                        </select>
                    </div>

                    <!-- Showroom Filter -->
                    @if($canFilterByShowroom)
                    <div id="filter-showroom" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Chọn showroom</label>
                        <select name="showroom_id" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">-- Tất cả --</option>
                            @foreach($showrooms as $showroom)
                                <option value="{{ $showroom->id }}" {{ request('showroom_id') == $showroom->id ? 'selected' : '' }}>
                                    {{ $showroom->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <!-- User Filter -->
                    @if($canFilterByUser)
                    <div id="filter-user" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Chọn nhân viên</label>
                        <select name="user_id" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">-- Tất cả --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pt-2 border-t gap-2">
                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-1.5 rounded-lg transition-colors text-sm">
                        <i class="fas fa-search mr-1"></i>Tìm kiếm
                    </button>
                    <a href="{{ route('sales.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-1.5 rounded-lg transition-colors text-sm">
                        <i class="fas fa-redo mr-1"></i>Làm mới
                    </a>
                    
                    <!-- Export Buttons -->
                    <div class="relative inline-block">
                        <button type="button" onclick="toggleExportMenu()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-1.5 rounded-lg transition-colors text-sm">
                            <i class="fas fa-file-export mr-1"></i>Xuất dữ liệu
                            <i class="fas fa-chevron-down ml-1 text-xs"></i>
                        </button>
                        <div id="export-menu" class="hidden absolute left-0 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                            <div class="p-3">
                                <p class="text-xs font-semibold text-gray-700 mb-2">Chọn định dạng và showroom:</p>
                                <div class="space-y-2">
                                    <!-- Excel -->
                                    <div class="border-b pb-2">
                                        <p class="text-xs font-medium text-gray-600 mb-1">
                                            <i class="fas fa-file-excel text-green-600 mr-1"></i>Excel
                                        </p>
                                        <button type="button" onclick="exportData('excel', 'all')" class="w-full text-left px-3 py-1.5 text-xs hover:bg-gray-100 rounded">
                                            Tất cả showroom
                                        </button>
                                        <button type="button" onclick="exportData('excel', 'separate')" class="w-full text-left px-3 py-1.5 text-xs hover:bg-gray-100 rounded">
                                            Từng showroom riêng
                                        </button>
                                    </div>
                                    <!-- PDF -->
                                    <div>
                                        <p class="text-xs font-medium text-gray-600 mb-1">
                                            <i class="fas fa-file-pdf text-red-600 mr-1"></i>PDF
                                        </p>
                                        <button type="button" onclick="exportData('pdf', 'all')" class="w-full text-left px-3 py-1.5 text-xs hover:bg-gray-100 rounded">
                                            Tất cả showroom
                                        </button>
                                        <button type="button" onclick="exportData('pdf', 'separate')" class="w-full text-left px-3 py-1.5 text-xs hover:bg-gray-100 rounded">
                                            Từng showroom riêng
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-xs text-gray-700">
                    Tìm thấy: <span class="text-blue-600 font-medium">{{ $sales->total() }}</span> đơn hàng
                </div>
            </div>
        </form>
    </div>
    
    <!-- Sales Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white">
                <tr>
                    <th class="px-2 py-2 text-center text-xs">STT</th>
                    <th class="px-2 py-2 text-left text-xs">Mã HD</th>
                    <th class="px-2 py-2 text-left text-xs">Ngày bán</th>
                    <th class="px-2 py-2 text-left text-xs">Khách hàng</th>
                    <th class="px-2 py-2 text-left text-xs">Showroom</th>
                    <th class="px-2 py-2 text-left text-xs">Nhân viên</th>
                    <th class="px-2 py-2 text-right text-xs">Tổng tiền (USD/VND)</th>
                    <th class="px-2 py-2 text-right text-xs">Đã trả (USD/VND)</th>
                    <th class="px-2 py-2 text-right text-xs">Còn nợ (USD/VND)</th>
                    <th class="px-2 py-2 text-center text-xs">Tình trạng</th>
                    <th class="px-2 py-2 text-center text-xs">Thanh toán</th>
                    <th class="px-2 py-2 text-center text-xs">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($sales as $index => $sale)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-2 py-2 text-center text-gray-600 font-medium">
                        {{ ($sales->currentPage() - 1) * $sales->perPage() + $index + 1 }}
                    </td>
                    <td class="px-2 py-2">
                        <span class="font-medium text-blue-600 text-xs">{{ $sale->invoice_code }}</span>
                    </td>
                    <td class="px-2 py-2 whitespace-nowrap">
                        <span class="text-gray-900 text-xs">{{ $sale->sale_date->format('d/m/Y') }}</span>
                    </td>
                    <td class="px-2 py-2">
                        <div class="font-medium text-gray-900 text-xs truncate max-w-[120px]" title="{{ $sale->customer->name }}">{{ $sale->customer->name }}</div>
                        <div class="text-xs text-gray-600">{{ $sale->customer->phone }}</div>
                    </td>
                    <td class="px-2 py-2">
                        <span class="text-gray-900 text-xs truncate max-w-[100px] block" title="{{ $sale->showroom->name }}">{{ $sale->showroom->name }}</span>
                    </td>
                    <td class="px-2 py-2">
                        <span class="text-gray-700 text-xs truncate max-w-[100px] block" title="{{ $sale->user->name }}">
                            {{ $sale->user->name }}
                        </span>
                    </td>
                    <td class="px-2 py-2 text-right">
                        @php
                            $hasReturns = $sale->returns()->where('status', 'completed')->where('type', 'return')->exists();
                            $hasExchanges = $sale->returns()->where('status', 'completed')->where('type', 'exchange')->exists();
                            
                            // Kiểm tra xem hóa đơn GỐC có items nào dùng USD/VND (không filter is_returned)
                            $hasUsdItems = $sale->saleItems->where('currency', 'USD')->count() > 0;
                            $hasVndItems = $sale->saleItems->where('currency', 'VND')->count() > 0;
                            
                            // Fallback: nếu không có currency field, check từ price
                            if (!$hasUsdItems && !$hasVndItems) {
                                $hasUsdItems = $sale->saleItems->where('price_usd', '>', 0)->count() > 0;
                                $hasVndItems = $sale->saleItems->where('price_vnd', '>', 0)->count() > 0;
                            }
                            
                            // Lấy original_total, nếu không có thì tính từ items
                            if ($sale->original_total_vnd) {
                                $originalTotal = $sale->original_total_vnd;
                                $originalTotalUsd = $sale->original_total_usd;
                            } else {
                                // Tính từ items (cho dữ liệu cũ)
                                $originalTotal = $sale->saleItems->sum('total_vnd');
                                $exchangeRate = $sale->exchange_rate ?: 1;
                                $originalTotalUsd = $originalTotal / $exchangeRate;
                            }
                            
                            // Kiểm tra xem có thay đổi tổng tiền không (do return hoặc exchange)
                            // Check cả USD và VND
                            $totalChanged = ($hasReturns || $hasExchanges) && 
                                            ($originalTotal != $sale->total_vnd || $originalTotalUsd != $sale->total_usd);
                            
                            // Kiểm tra trả hết (tất cả items đã returned)
                            $allReturned = $sale->saleItems->where('is_returned', true)->count() == $sale->saleItems->count() && $sale->saleItems->count() > 0;
                        @endphp
                        
                        @if($allReturned || ($hasReturns && $sale->total_usd == 0 && $sale->total_vnd == 0))
                            <!-- Trả hết - hiển thị giá gốc không gạch ngang -->
                            @if($hasUsdItems)
                                <div class="font-medium text-gray-900 text-xs whitespace-nowrap">${{ number_format($originalTotalUsd, 2) }}</div>
                            @endif
                            @if($hasVndItems)
                                <div class="text-xs {{ $hasUsdItems ? 'text-gray-500' : 'font-medium text-gray-900' }}">{{ number_format($originalTotal) }}đ</div>
                            @endif
                            <div class="text-xs text-red-600">
                                <i class="fas fa-undo"></i>Trả hết
                            </div>
                        @elseif($totalChanged)
                            <!-- Có thay đổi (trả hàng hoặc đổi hàng) - hiển thị giá gốc gạch ngang và giá mới -->
                            @if($hasUsdItems)
                                <div class="text-xs text-gray-400 line-through whitespace-nowrap">${{ number_format($originalTotalUsd, 2) }}</div>
                            @endif
                            @if($hasVndItems && $hasUsdItems)
                                <div class="text-xs text-gray-400 line-through">{{ number_format($originalTotal) }}đ</div>
                            @endif
                            @if($hasUsdItems)
                                <div class="font-medium {{ $hasExchanges ? 'text-purple-600' : 'text-orange-600' }} text-xs whitespace-nowrap">
                                    ${{ number_format($sale->total_usd, 2) }}
                                </div>
                            @endif
                            @if($hasVndItems)
                                <div class="text-xs {{ $hasExchanges ? 'text-purple-600' : 'text-orange-600' }} {{ $hasUsdItems ? '' : 'font-medium' }}">
                                    {{ number_format($sale->total_vnd) }}đ
                                </div>
                            @endif
                            @if($hasExchanges)
                                <div class="text-xs text-purple-600">
                                    <i class="fas fa-exchange-alt"></i>Đổi
                                </div>
                            @endif
                        @else
                            <!-- Không có thay đổi -->
                            @if($hasUsdItems)
                                <div class="font-medium text-gray-900 text-xs whitespace-nowrap">${{ number_format($sale->total_usd, 2) }}</div>
                            @endif
                            @if($hasVndItems)
                                <div class="text-xs {{ $hasUsdItems ? 'text-gray-500' : 'font-medium text-gray-900' }}">{{ number_format($sale->total_vnd) }}đ</div>
                            @endif
                        @endif
                    </td>
                    <td class="px-2 py-2 text-right text-xs whitespace-nowrap">
                        @php
                            $paidUsdOnly = $sale->payments->sum('payment_usd');
                            $paidVndOnly = $sale->payments->sum('payment_vnd');
                        @endphp
                        @if($allReturned)
                            {{-- Trả hết - hiển thị số tiền đã trả (sẽ được hoàn lại) --}}
                            @if($paidUsdOnly > 0)
                                <div class="text-green-600 font-bold text-xs">${{ number_format($paidUsdOnly, 2) }}</div>
                            @endif
                            @if($paidVndOnly > 0)
                                <div class="text-green-600 font-bold text-xs">{{ number_format($paidVndOnly) }}đ</div>
                            @endif
                            @if($paidUsdOnly == 0 && $paidVndOnly == 0)
                                <div class="text-gray-500">0</div>
                            @endif
                        @elseif($hasUsdItems && $hasVndItems)
                            {{-- Cả USD và VND - Hiển thị riêng --}}
                            @if($paidUsdOnly > 0)
                                <div class="text-blue-600 font-bold text-xs">USD: ${{ number_format($paidUsdOnly, 2) }}</div>
                            @endif
                            @if($paidVndOnly > 0)
                                <div class="text-green-600 font-bold text-xs">VND: {{ number_format($paidVndOnly) }}đ</div>
                            @endif
                            @if($paidUsdOnly == 0 && $paidVndOnly == 0)
                                <div class="text-gray-500">0</div>
                            @endif
                        @elseif($hasUsdItems)
                            {{-- Chỉ USD --}}
                            <div class="text-green-600 font-bold">${{ number_format($sale->paid_usd, 2) }}</div>
                        @elseif($hasVndItems)
                            {{-- Chỉ VND --}}
                            <div class="text-green-600 font-bold">{{ number_format($sale->paid_vnd) }}đ</div>
                        @else
                            <div class="text-gray-500">0</div>
                        @endif
                    </td>
                    <td class="px-2 py-2 text-right text-xs whitespace-nowrap">
                        @if($sale->sale_status == 'cancelled')
                            <span class="text-gray-500">(Hủy)</span>
                        @elseif($allReturned)
                            {{-- Trả hết - còn nợ = 0 --}}
                            <div class="text-gray-500">0</div>
                        @elseif($hasUsdItems && $hasVndItems)
                            {{-- Cả USD và VND - Hiển thị riêng --}}
                            @if($sale->debt_usd > 0.01 || $sale->debt_vnd > 1)
                                @if($sale->debt_usd > 0.01)
                                    <div class="text-blue-600 font-bold text-xs">USD: ${{ number_format($sale->debt_usd, 2) }}</div>
                                @endif
                                @if($sale->debt_vnd > 1)
                                    <div class="text-red-600 font-bold text-xs">VND: {{ number_format($sale->debt_vnd) }}đ</div>
                                @endif
                            @else
                                <div class="text-gray-500">0</div>
                            @endif
                        @elseif($hasUsdItems)
                            {{-- Chỉ USD --}}
                            @if($sale->debt_usd > 0.01)
                                <div class="text-red-600 font-bold">${{ number_format($sale->debt_usd, 2) }}</div>
                            @else
                                <div class="text-gray-500">0</div>
                            @endif
                        @elseif($hasVndItems)
                            {{-- Chỉ VND --}}
                            @if($sale->debt_vnd > 1)
                                <div class="text-red-600 font-bold">{{ number_format($sale->debt_vnd) }}đ</div>
                            @else
                                <div class="text-gray-500">0</div>
                            @endif
                        @else
                            <div class="text-gray-500">0</div>
                        @endif
                    </td>
                    <td class="px-2 py-2 text-center">
                        @if($sale->sale_status == 'cancelled' || $allReturned)
                            <span class="px-2 py-1 text-xs font-bold rounded-lg bg-gray-100 text-gray-800 whitespace-nowrap">
                                <i class="fas fa-ban"></i> Hủy
                            </span>
                        @elseif($sale->sale_status == 'pending')
                            <span class="px-2 py-1 text-xs font-bold rounded-lg bg-yellow-100 text-yellow-800 whitespace-nowrap">
                                <i class="fas fa-clock"></i> Chờ
                            </span>
                        @elseif($sale->sale_status == 'completed')
                            <span class="px-2 py-1 text-xs font-bold rounded-lg bg-green-100 text-green-800 whitespace-nowrap">
                                <i class="fas fa-check-circle"></i> Duyệt
                            </span>
                        @endif
                    </td>
                    <td class="px-2 py-2 text-center">
                        @if($sale->sale_status == 'cancelled' || $allReturned)
                            <span class="px-2 py-1 text-xs font-bold rounded-lg bg-gray-100 text-gray-800 whitespace-nowrap">Hủy</span>
                        @elseif($sale->payment_status == 'paid')
                            <span class="px-2 py-1 text-xs font-bold rounded-lg bg-green-100 text-green-800 whitespace-nowrap">Đã TT</span>
                        @elseif($sale->payment_status == 'partial')
                            <span class="px-2 py-1 text-xs font-bold rounded-lg bg-yellow-100 text-yellow-800 whitespace-nowrap">TT 1 phần</span>
                        @else
                            <span class="px-2 py-1 text-xs font-bold rounded-lg bg-red-100 text-red-800 whitespace-nowrap">Chưa TT</span>
                        @endif
                    </td>
                    <td class="px-2 py-2">
                        <div class="flex items-center justify-center space-x-1">
                            @notArchive
                            <!-- Approve button - chỉ hiện khi chờ duyệt -->
                            @if($sale->canApprove())
                            <form method="POST" action="{{ route('sales.approve', $sale->id) }}" class="inline">
                                @csrf
                                <button type="submit" 
                                        onclick="return confirm('Xác nhận duyệt phiếu {{ $sale->invoice_code }}?')"
                                        class="w-7 h-7 flex items-center justify-center bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-colors text-xs" 
                                        title="Duyệt phiếu">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                            </form>
                            @endif
                            
                            <!-- Cancel button - chỉ hiện khi chờ duyệt (pending) -->
                            @if($sale->isPending())
                            <form method="POST" action="{{ route('sales.cancel', $sale->id) }}" class="inline">
                                @csrf
                                <button type="submit" 
                                        onclick="return confirm('Xác nhận hủy phiếu {{ $sale->invoice_code }}?')"
                                        class="w-7 h-7 flex items-center justify-center bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors text-xs" 
                                        title="Hủy phiếu">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </form>
                            @endif
                            @endnotArchive
                            
                            <!-- Show button - luôn hiển thị nếu có quyền xem -->
                            @hasPermission('sales', 'can_view')
                            <a href="{{ route('sales.show', $sale->id) }}" 
                               class="w-7 h-7 flex items-center justify-center bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-colors text-xs" 
                               title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            @endhasPermission
                            
                            <!-- Edit button - chỉ hiện khi có quyền và chờ duyệt, ẩn khi đã hủy/trả hết -->
                            @notArchive
                            @hasPermission('sales', 'can_edit')
                                @if($sale->canEdit() && !$allReturned && $sale->sale_status != 'cancelled')
                                <a href="{{ route('sales.edit', $sale->id) }}" 
                                   class="w-7 h-7 flex items-center justify-center bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-colors text-xs" 
                                   title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @elseif(!$allReturned && $sale->sale_status != 'cancelled')
                                <span class="w-7 h-7 flex items-center justify-center bg-gray-100 text-gray-400 rounded-lg cursor-not-allowed text-xs" 
                                      title="Không thể sửa">
                                    <i class="fas fa-lock"></i>
                                </span>
                                @endif
                            @endhasPermission
                            @endnotArchive
                            
                            <!-- Print button - ẩn khi đã hủy/trả hết hoặc không có quyền -->
                            @hasPermission('sales', 'can_print')
                                @if($sale->payment_status != 'cancelled' && !$allReturned && $sale->sale_status != 'cancelled')
                                <a href="{{ route('sales.print', $sale->id) }}" 
                                   target="_blank" 
                                   class="w-7 h-7 flex items-center justify-center bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-colors text-xs" 
                                   title="In hóa đơn">
                                    <i class="fas fa-print"></i>
                                </a>
                                @endif
                            @endhasPermission
                            
                            <!-- Delete button - hiển thị khi có quyền và phiếu chưa duyệt (pending) -->
                            @notArchive
                            @hasPermission('sales', 'can_delete')
                                @if($sale->isPending())
                                <button type="button" 
                                        class="w-7 h-7 flex items-center justify-center bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors delete-btn text-xs" 
                                        title="Xóa"
                                        data-url="{{ route('sales.destroy', $sale->id) }}"
                                        data-message="Bạn có chắc chắn muốn xóa hóa đơn {{ $sale->invoice_code }}?">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @elseif($sale->sale_status != 'cancelled')
                                <span class="w-7 h-7 flex items-center justify-center rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed text-xs" 
                                      title="Phiếu đã duyệt">
                                    <i class="fas fa-lock"></i>
                                </span>
                                @endif
                            @endhasPermission
                            @endnotArchive
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Không có dữ liệu</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="mt-6">
        {{ $sales->links() }}
    </div>
</div>

<!-- Print Invoice Modal -->
<div id="print-invoice-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-5xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4 no-print">
            <h3 class="text-lg font-medium text-gray-900">Xem trước hóa đơn</h3>
            <div class="flex space-x-2">
                <button onclick="printInvoiceContent()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-print mr-2"></i>In
                </button>
                <button onclick="closePrintModal()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                    <i class="fas fa-times mr-2"></i>Đóng
                </button>
            </div>
        </div>
        <div id="print-invoice-content" class="print-area">
            <!-- Invoice content will be loaded here -->
        </div>
    </div>
</div>

<!-- Include Delete Modal -->
<x-delete-modal />
@endsection

@push('scripts')
<script>
// Show/hide filter options based on selected type
function showFilterOptions(type) {
    // Hide all filter options (check if element exists first)
    const filterAmount = document.getElementById('filter-amount');
    const filterDebt = document.getElementById('filter-debt');
    const filterShowroom = document.getElementById('filter-showroom');
    const filterUser = document.getElementById('filter-user');
    
    if (filterAmount) filterAmount.classList.add('hidden');
    if (filterDebt) filterDebt.classList.add('hidden');
    if (filterShowroom) filterShowroom.classList.add('hidden');
    if (filterUser) filterUser.classList.add('hidden');
    
    // Show selected filter option
    if (type) {
        const selectedFilter = document.getElementById('filter-' + type);
        if (selectedFilter) {
            selectedFilter.classList.remove('hidden');
        }
    }
}

// Initialize filter on page load
document.addEventListener('DOMContentLoaded', function() {
    const filterTypeElement = document.getElementById('filter-type');
    if (filterTypeElement) {
        const filterType = filterTypeElement.value;
        if (filterType) {
            showFilterOptions(filterType);
        }
    }
});

// Search suggestions
let searchTimeout;
const searchInput = document.getElementById('search-input');
const suggestionsBox = document.getElementById('search-suggestions');

if (searchInput && suggestionsBox) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            suggestionsBox.classList.add('hidden');
            return;
        }
        
        searchTimeout = setTimeout(() => {
            fetchSuggestions(query);
        }, 300);
    });

    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) {
            fetchSuggestions(this.value.trim());
        }
    });
}


// Close suggestions when clicking outside
if (searchInput && suggestionsBox) {
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.classList.add('hidden');
        }
    });
}

function fetchSuggestions(query) {
    fetch(`{{ route('sales.api.search.suggestions') }}?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            displaySuggestions(data);
        })
        .catch(error => {
            console.error('Error fetching suggestions:', error);
        });
}

function displaySuggestions(suggestions) {
    if (suggestions.length === 0) {
        suggestionsBox.classList.add('hidden');
        return;
    }
    
    let html = '<div class="py-2">';
    
    // Group by type
    const invoices = suggestions.filter(s => s.type === 'invoice');
    const customers = suggestions.filter(s => s.type === 'customer');
    
    if (invoices.length > 0) {
        html += '<div class="px-4 py-2 text-sm font-bold text-gray-600 uppercase bg-gray-100">📄 Hóa đơn</div>';
        invoices.forEach(item => {
            html += `
                <a href="${item.url}" class="flex items-center px-4 py-3 hover:bg-blue-50 cursor-pointer transition-colors border-b">
                    <i class="fas ${item.icon} text-blue-600 mr-3 text-lg"></i>
                    <div class="flex-1">
                        <div class="text-base font-semibold text-gray-900">${item.label}</div>
                        <div class="text-sm text-gray-600">${item.sublabel}</div>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400"></i>
                </a>
            `;
        });
    }
    
    if (customers.length > 0) {
        html += '<div class="px-4 py-2 text-sm font-bold text-gray-600 uppercase bg-gray-100 border-t-2">👤 Khách hàng</div>';
        customers.forEach(item => {
            html += `
                <div onclick="selectSuggestion('${item.search}')" class="flex items-center px-4 py-3 hover:bg-green-50 cursor-pointer transition-colors border-b">
                    <i class="fas ${item.icon} text-green-600 mr-3 text-lg"></i>
                    <div class="flex-1">
                        <div class="text-base font-semibold text-gray-900">${item.label}</div>
                        <div class="text-sm text-gray-600">${item.sublabel}</div>
                    </div>
                    <i class="fas fa-search text-gray-400"></i>
                </div>
            `;
        });
    }
    
    html += '</div>';
    
    suggestionsBox.innerHTML = html;
    suggestionsBox.classList.remove('hidden');
}

function selectSuggestion(value) {
    searchInput.value = value;
    suggestionsBox.classList.add('hidden');
    document.getElementById('filter-form').submit();
}

// Handle delete button clicks
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            const message = this.getAttribute('data-message');
            showDeleteModal(url, message);
        });
    });
});

function showPrintModal(invoiceId) {
    // In real app, fetch invoice data via AJAX
    const modal = document.getElementById('print-invoice-modal');
    const content = document.getElementById('print-invoice-content');
    
    // Mock invoice data
    const invoice = {
        id: invoiceId,
        date: '07/10/2025',
        customer_name: 'Khách hàng demo',
        customer_phone: '0123 456 789',
        customer_address: '123 Đường ABC, Quận 1, TP.HCM',
        items: [
            { 
                name: 'Tranh sơn dầu', 
                quantity: 1, 
                price_usd: 100, 
                price_vnd: 2500000,
                total_usd: 100,
                total_vnd: 2500000,
                image: 'https://bizweb.dktcdn.net/100/372/422/products/tranh-son-dau-dep-da-nang-4-3.jpg?v=1679906135817'
            },
            { 
                name: 'Khung 30x40', 
                quantity: 1, 
                price_usd: 20, 
                price_vnd: 500000,
                total_usd: 20,
                total_vnd: 500000,
                image: 'https://bizweb.dktcdn.net/100/372/422/products/tranh-son-dau-dep-da-nang-4-3.jpg?v=1679906135817'
            }
        ],
        subtotal_usd: 120,
        subtotal_vnd: 3000000,
        discount_percent: 10,
        discount_usd: 12,
        discount_vnd: 300000,
        total_usd: 108,
        total_vnd: 2700000,
        exchange_rate: 25000
    };
    
    content.innerHTML = `
        <div class="bg-white p-6">
            <!-- Header -->
            <div class="flex justify-between items-start mb-6">
                <div class="flex items-center space-x-3">
                    <img src="https://via.placeholder.com/60x60/4F46E5/FFFFFF?text=Logo" alt="logo" class="w-16 h-16 rounded-lg" />
                    <div>
                        <h2 class="text-2xl font-bold">HÓA ĐƠN BÁN HÀNG</h2>
                        <p class="text-sm text-gray-600">Mã HD: <span class="font-semibold text-blue-600">${invoice.id}</span></p>
                        <p class="text-sm text-gray-600">Ngày: ${invoice.date}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-bold">Bến Thành Art Gallery</p>
                    <p class="text-sm text-gray-600">123 Lê Lợi, Q.1, TP.HCM</p>
                    <p class="text-sm text-gray-600">Hotline: 0987 654 321</p>
                </div>
            </div>

            <!-- Customer Info -->
            <div class="mb-4 p-3 bg-gray-50 rounded">
                <h3 class="font-semibold mb-2">Thông tin khách hàng</h3>
                <p class="text-sm"><strong>Tên:</strong> ${invoice.customer_name}</p>
                <p class="text-sm"><strong>SĐT:</strong> ${invoice.customer_phone}</p>
                <p class="text-sm"><strong>Địa chỉ:</strong> ${invoice.customer_address}</p>
            </div>

            <!-- Items Table -->
            <table class="w-full mb-4 border-collapse">
                <thead>
                    <tr class="bg-gray-100 border-b">
                        <th class="px-3 py-2 text-left text-sm">#</th>
                        <th class="px-3 py-2 text-left text-sm">HÌNH</th>
                        <th class="px-3 py-2 text-left text-sm">SẢN PHẨM</th>
                        <th class="px-3 py-2 text-center text-sm">SL</th>
                        <th class="px-3 py-2 text-right text-sm">ĐƠN GIÁ</th>
                        <th class="px-3 py-2 text-right text-sm">THÀNH TIỀN</th>
                    </tr>
                </thead>
                <tbody>
                    ${invoice.items.map((item, index) => `
                        <tr class="border-b">
                            <td class="px-3 py-2 text-sm">${index + 1}</td>
                            <td class="px-3 py-2">
                                <img src="${item.image}" alt="img" class="w-20 h-16 object-cover rounded border" />
                            </td>
                            <td class="px-3 py-2 text-sm">${item.name}</td>
                            <td class="px-3 py-2 text-sm text-center">${item.quantity}</td>
                            <td class="px-3 py-2 text-sm text-right">
                                <div>$${item.price_usd.toLocaleString('en-US')}</div>
                                <div class="text-xs text-gray-500">${item.price_vnd.toLocaleString('vi-VN')}đ</div>
                            </td>
                            <td class="px-3 py-2 text-sm text-right font-semibold">
                                <div>$${item.total_usd.toLocaleString('en-US')}</div>
                                <div class="text-xs text-gray-500">${item.total_vnd.toLocaleString('vi-VN')}đ</div>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>

            <!-- Totals -->
            <div class="flex justify-end">
                <div class="w-1/2">
                    <div class="flex justify-between py-1 text-sm">
                        <span>Tạm tính:</span>
                        <span>
                            <div>$${invoice.subtotal_usd.toLocaleString('en-US')}</div>
                            <div class="text-xs text-gray-500">${invoice.subtotal_vnd.toLocaleString('vi-VN')}đ</div>
                        </span>
                    </div>
                    ${invoice.discount_percent > 0 ? `
                        <div class="flex justify-between py-1 text-sm">
                            <span>Giảm giá (${invoice.discount_percent}%):</span>
                            <span class="text-red-600">
                                <div>-$${invoice.discount_usd.toLocaleString('en-US')}</div>
                                <div class="text-xs text-gray-500">-${invoice.discount_vnd.toLocaleString('vi-VN')}đ</div>
                            </span>
                        </div>
                    ` : ''}
                    <div class="flex justify-between py-2 font-bold text-lg border-t">
                        <span>Tổng cộng:</span>
                        <span class="text-green-600">
                            <div>$${invoice.total_usd.toLocaleString('en-US')}</div>
                            <div class="text-xs text-gray-500">${invoice.total_vnd.toLocaleString('vi-VN')}đ</div>
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 text-right mt-2">
                        Tỷ giá: 1 USD = ${invoice.exchange_rate.toLocaleString('vi-VN')} VND
                    </div>
                </div>
            </div>

            <!-- Signatures -->
            <div class="grid grid-cols-2 gap-8 mt-8">
                <div class="text-center">
                    <p class="font-semibold mb-12">Người bán hàng</p>
                    <p class="text-xs text-gray-500">(Ký và ghi rõ họ tên)</p>
                </div>
                <div class="text-center">
                    <p class="font-semibold mb-12">Khách hàng</p>
                    <p class="text-xs text-gray-500">(Ký và ghi rõ họ tên)</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="border-t pt-3 mt-6 text-xs text-gray-600">
                <div class="flex justify-between">
                    <span>Hotline: 0987 654 321</span>
                    <span>Ngân hàng: Vietcombank 0123456789 - CN Sài Gòn</span>
                </div>
                <p class="text-center mt-2">Cảm ơn quý khách đã mua hàng!</p>
            </div>
        </div>
    `;
    
    modal.classList.remove('hidden');
}

function closePrintModal() {
    document.getElementById('print-invoice-modal').classList.add('hidden');
}

function printInvoiceContent() {
    const content = document.getElementById('print-invoice-content').innerHTML;
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    
    printWindow.document.write('<!DOCTYPE html>');
    printWindow.document.write('<html><head>');
    printWindow.document.write('<title>In hóa đơn</title>');
    printWindow.document.write('<script src="https://cdn.tailwindcss.com"><\/script>');
    printWindow.document.write('<style>');
    printWindow.document.write('@media print { .no-print { display: none !important; } body { margin: 0; padding: 20px; } }');
    printWindow.document.write('@page { size: A4; margin: 1cm; }');
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(content);
    printWindow.document.write('<script>window.onload = function() { window.print(); }<\/script>');
    printWindow.document.write('</body></html>');
    printWindow.document.close();
}

// Close modal when clicking outside
document.getElementById('print-invoice-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closePrintModal();
    }
});

// Toggle export menu
function toggleExportMenu() {
    const menu = document.getElementById('export-menu');
    menu.classList.toggle('hidden');
}

// Close export menu when clicking outside
document.addEventListener('click', function(e) {
    const menu = document.getElementById('export-menu');
    const button = e.target.closest('button');
    
    if (menu && !menu.contains(e.target) && (!button || button.onclick?.toString().indexOf('toggleExportMenu') === -1)) {
        menu.classList.add('hidden');
    }
});

// Export data function
function exportData(format, type) {
    // Get current filter parameters
    const form = document.getElementById('filter-form');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    
    // Add export parameters
    params.append('export', format);
    params.append('export_type', type);
    
    // Build URL
    const url = '{{ route("sales.export") }}?' + params.toString();
    
    // Show loading
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Đang xuất...';
    button.disabled = true;
    
    // Download file
    window.location.href = url;
    
    // Reset button after delay
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
        document.getElementById('export-menu').classList.add('hidden');
    }, 2000);
}
</script>
@endpush
