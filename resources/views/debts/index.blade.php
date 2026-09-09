@extends('layouts.app')

@section('title', 'Lịch sử giao dịch')
@section('page-title', 'Lịch sử giao dịch')
@section('page-description', 'Quản lý tất cả các giao dịch')

@section('content')
    <x-alert />

    <!-- Statistics Cards -->
    <!-- <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Tổng đã thu</p>
                    <h3 class="text-2xl font-bold mt-1 text-green-600">{{ number_format($stats['total_payments'], 0, ',', '.') }}đ</h3>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fas fa-money-bill-wave text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Còn nợ</p>
                    <h3 class="text-2xl font-bold mt-1 text-red-600">{{ number_format($stats['total_debt'], 0, ',', '.') }}đ</h3>
                </div>
                <div class="bg-red-100 rounded-full p-3">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-orange-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">HĐ còn nợ</p>
                    <h3 class="text-2xl font-bold mt-1 text-orange-600">{{ $stats['debt_count'] }}</h3>
                </div>
                <div class="bg-orange-100 rounded-full p-3">
                    <i class="fas fa-file-invoice-dollar text-2xl text-orange-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Số giao dịch</p>
                    <h3 class="text-2xl font-bold mt-1 text-blue-600">{{ $stats['total_count'] }}</h3>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fas fa-receipt text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>
    </div> -->

    <div class="bg-white rounded-xl shadow-lg p-4 fade-in">
        <!-- Search & Filter -->
        <form method="GET" class="mb-4" id="searchForm">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                <div class="relative">
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Tìm kiếm
                    </label>
                    <input type="text" name="search" id="searchInput" value="{{ request('search') }}"
                        placeholder="Tên, SĐT, Mã HĐ..." autocomplete="off"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <div id="searchSuggestions"
                        class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Từ ngày
                    </label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Đến ngày
                    </label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                @if($canFilterByShowroom ?? true)
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Showroom
                        </label>
                        <select name="showroom_id"
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">-- Tất cả --</option>
                            @foreach($showrooms ?? [] as $showroom)
                                <option value="{{ $showroom->id }}" {{ request('showroom_id') == $showroom->id ? 'selected' : '' }}>
                                    {{ $showroom->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Trạng thái TT
                    </label>
                    <select name="payment_status"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Tất cả --</option>
                        <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Đã TT</option>
                        <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>TT 1 phần
                        </option>
                        <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Chưa TT</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Số tiền từ
                    </label>
                    <input type="number" name="amount_from" value="{{ request('amount_from') }}" placeholder="Từ..."
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Số tiền đến
                    </label>
                    <input type="number" name="amount_to" value="{{ request('amount_to') }}" placeholder="Đến..."
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div class="flex flex-wrap items-end gap-2 col-span-2 md:col-span-2">
                    <button type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-1.5 rounded-lg transition-colors text-sm whitespace-nowrap">
                        <i class="fas fa-search mr-1"></i>Tìm
                    </button>
                    <a href="{{ route('debt.index') }}"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-1.5 rounded-lg transition-colors text-sm whitespace-nowrap">
                        <i class="fas fa-redo mr-1"></i>Làm mới
                    </a>

                    <!-- Export Dropdown -->
                    <div class="relative">
                        <button onclick="toggleExportDropdown()" type="button"
                            class="bg-green-500 hover:bg-green-600 text-white px-4 py-1.5 rounded-lg transition-colors flex items-center text-sm whitespace-nowrap">
                            <i class="fas fa-download mr-1"></i>Xuất
                            <i class="fas fa-chevron-down ml-1 text-xs"></i>
                        </button>
                        <div id="exportDropdown"
                            class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl z-50 border border-gray-200">
                            <!-- Excel Export -->
                            <div class="py-1 border-b border-gray-200">
                                <div class="px-3 py-1 text-xs font-semibold text-gray-500 uppercase">Excel</div>
                                <a href="{{ route('debt.export.excel', array_merge(request()->query(), ['scope' => 'current'])) }}"
                                    class="block px-3 py-2 text-xs text-gray-700 hover:bg-blue-50 transition-colors">
                                    <i class="fas fa-file-excel text-green-600 mr-1"></i>Trang hiện tại
                                </a>
                                <a href="{{ route('debt.export.excel', array_merge(request()->query(), ['scope' => 'all'])) }}"
                                    class="block px-3 py-2 text-xs text-gray-700 hover:bg-blue-50 transition-colors">
                                    <i class="fas fa-file-excel text-green-600 mr-1"></i>Tất cả kết quả
                                </a>
                            </div>
                            <!-- PDF Export -->
                            <div class="py-1">
                                <div class="px-3 py-1 text-xs font-semibold text-gray-500 uppercase">PDF</div>
                                <a href="{{ route('debt.export.pdf', array_merge(request()->query(), ['scope' => 'current'])) }}"
                                    class="block px-3 py-2 text-xs text-gray-700 hover:bg-red-50 transition-colors">
                                    <i class="fas fa-file-pdf text-red-600 mr-1"></i>Trang hiện tại
                                </a>
                                <a href="{{ route('debt.export.pdf', array_merge(request()->query(), ['scope' => 'all'])) }}"
                                    class="block px-3 py-2 text-xs text-gray-700 hover:bg-red-50 transition-colors">
                                    <i class="fas fa-file-pdf text-red-600 mr-1"></i>Tất cả kết quả
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Debts Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white">
                    <tr>
                        <th class="px-2 py-2 text-center text-xs">STT</th>
                        <th class="px-2 py-2 text-left text-xs">Ngày trả</th>
                        <th class="px-2 py-2 text-left text-xs">Mã HĐ</th>
                        <th class="px-2 py-2 text-left text-xs">Khách hàng</th>
                        <th class="px-2 py-2 text-left text-xs">SĐT</th>
                        <th class="px-2 py-2 text-right text-xs">Tổng HĐ</th>
                        <th class="px-2 py-2 text-right text-xs">Trả lần này</th>
                        <th class="px-2 py-2 text-center text-xs">P.Thức</th>
                        <th class="px-2 py-2 text-center text-xs">Loại GD</th>
                        <th class="px-2 py-2 text-right text-xs">Còn thiếu</th>
                        <th class="px-2 py-2 text-center text-xs">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($payments as $index => $payment)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-2 py-2 text-center text-gray-600 font-medium text-xs">
                                {{ ($payments->currentPage() - 1) * $payments->perPage() + $index + 1 }}
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                @php
                                    // Debug: Log payment date info
                                    \Log::info("Payment #{$payment->id} - Raw: {$payment->payment_date} | Formatted: " . $payment->payment_date->format('Y-m-d H:i:s'));
                                @endphp
                                <div class="text-gray-900 text-xs font-medium">{{ $payment->payment_date->format('d/m/Y') }}
                                </div>
                                <div class="text-gray-500 text-xs">{{ $payment->payment_date->format('H:i') }}</div>
                            </td>
                            <td class="px-2 py-2">
                                <span class="font-medium text-blue-600 text-xs">{{ $payment->sale->invoice_code }}</span>
                            </td>
                            <td class="px-2 py-2">
                                <div class="font-medium text-gray-900 text-xs truncate max-w-[120px]"
                                    title="{{ $payment->sale->customer->name }}">{{ $payment->sale->customer->name }}</div>
                            </td>
                            <td class="px-2 py-2 text-xs">
                                {{ $payment->sale->customer->phone ?? '-' }}
                            </td>
                            <td class="px-2 py-2 text-right font-medium text-xs whitespace-nowrap">
                                @php
                                    $sale = $payment->sale;
                                    $hasReturns = $sale->returns()->where('status', 'completed')->where('type', 'return')->exists();
                                    $hasExchanges = $sale->returns()->where('status', 'completed')->where('type', 'exchange')->exists();

                                    // Lấy original_total
                                    if ($sale->original_total_vnd) {
                                        $originalTotalVnd = $sale->original_total_vnd;
                                        $originalTotalUsd = $sale->original_total_usd;
                                    } else {
                                        $originalTotalVnd = $sale->saleItems->sum('total_vnd');
                                        $originalTotalUsd = $sale->saleItems->sum('total_usd');
                                    }

                                    // Xác định loại tiền tệ của hóa đơn
                                    $isUsdInvoice = $sale->saleItems->where('currency', 'USD')->count() > 0;
                                    $isVndInvoice = $sale->saleItems->where('currency', 'VND')->count() > 0;
                                    $isMixedInvoice = $isUsdInvoice && $isVndInvoice;
                                @endphp

                                @if($hasReturns && $sale->total_usd == 0 && $sale->total_vnd == 0)
                                    <!-- Trả hết -->
                                    @if($isMixedInvoice)
                                        <div class="text-gray-900">${{ number_format($originalTotalUsd, 2) }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format($originalTotalVnd, 0, ',', '.') }}đ</div>
                                    @elseif($isUsdInvoice)
                                        <div class="text-gray-900">${{ number_format($originalTotalUsd, 2) }}</div>
                                    @else
                                        <div class="text-gray-900">{{ number_format($originalTotalVnd, 0, ',', '.') }}đ</div>
                                    @endif
                                    <div class="text-xs text-red-600"><i class="fas fa-undo"></i>Trả hết</div>
                                @elseif(($hasReturns || $hasExchanges) && ($originalTotalUsd != $sale->total_usd || $originalTotalVnd != $sale->total_vnd))
                                    <!-- Trả/Đổi một phần -->
                                    @if($isMixedInvoice)
                                        <div class="text-xs text-gray-400 line-through">${{ number_format($originalTotalUsd, 2) }} +
                                            {{ number_format($originalTotalVnd, 0, ',', '.') }}đ</div>
                                        <div class="text-orange-600">${{ number_format($sale->total_usd, 2) }}</div>
                                        <div class="text-xs text-orange-500">{{ number_format($sale->total_vnd, 0, ',', '.') }}đ</div>
                                    @elseif($isUsdInvoice)
                                        <div class="text-xs text-gray-400 line-through">${{ number_format($originalTotalUsd, 2) }}</div>
                                        <div class="text-orange-600">${{ number_format($sale->total_usd, 2) }}</div>
                                    @else
                                        <div class="text-xs text-gray-400 line-through">
                                            {{ number_format($originalTotalVnd, 0, ',', '.') }}đ</div>
                                        <div class="text-orange-600">{{ number_format($sale->total_vnd, 0, ',', '.') }}đ</div>
                                    @endif
                                @else
                                    <!-- Không có trả/đổi -->
                                    @if($isMixedInvoice)
                                        <div class="text-gray-900">${{ number_format($sale->total_usd, 2) }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format($sale->total_vnd, 0, ',', '.') }}đ</div>
                                    @elseif($isUsdInvoice)
                                        <div class="text-gray-900">${{ number_format($sale->total_usd, 2) }}</div>
                                    @else
                                        <div class="text-gray-900">{{ number_format($sale->total_vnd, 0, ',', '.') }}đ</div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-2 py-2 text-right text-xs whitespace-nowrap">
                                @php
                                    // Hiển thị số tiền trả theo loại tiền tệ của hóa đơn
                                    $paymentUsd = $payment->payment_usd ?? 0;
                                    $paymentVnd = $payment->payment_vnd ?? 0;
                                    $isRefund = $paymentUsd < 0 || $paymentVnd < 0 || ($payment->transaction_type ?? '') === 'refund';
                                    $colorText = $isRefund ? 'text-red-600' : 'text-green-600';

                                    // Fallback cho dữ liệu cũ (chỉ có amount)
                                    if ($paymentUsd == 0 && $paymentVnd == 0 && $payment->amount != 0) {
                                        $paymentVnd = $payment->amount;
                                    }
                                @endphp

                                @if($isMixedInvoice)
                                    <!-- Hóa đơn hỗn hợp: hiển thị cả USD và VND -->
                                    @if($paymentUsd != 0)
                                        <div class="font-bold {{ $colorText }}">{{ $isRefund && $paymentUsd > 0 ? '-' : '' }}${{ number_format($paymentUsd, 2) }}</div>
                                    @endif
                                    @if($paymentVnd != 0)
                                        <div class="{{ $colorText }} {{ $paymentUsd != 0 ? 'text-xs' : 'font-bold' }}">
                                            {{ $isRefund && $paymentVnd > 0 ? '-' : '' }}{{ number_format($paymentVnd, 0, ',', '.') }}đ</div>
                                    @endif
                                    @if($paymentUsd == 0 && $paymentVnd == 0)
                                        <div class="text-gray-400">-</div>
                                    @endif
                                @elseif($isUsdInvoice)
                                    <!-- Hóa đơn USD: chỉ hiển thị USD -->
                                    @if($paymentUsd != 0)
                                        <div class="font-bold {{ $colorText }}">{{ $isRefund && $paymentUsd > 0 ? '-' : '' }}${{ number_format($paymentUsd, 2) }}</div>
                                    @elseif($paymentVnd != 0)
                                        @php
                                            $rate = $payment->payment_exchange_rate ?? $sale->exchange_rate;
                                            if ($rate <= 0)
                                                $rate = 1;
                                            $convertedUsd = $paymentVnd / $rate;
                                            // Smart rounding
                                            if (abs($convertedUsd - round($convertedUsd)) < 0.05) {
                                                $convertedUsd = round($convertedUsd);
                                            }
                                        @endphp
                                        <div class="font-bold {{ $colorText }}">{{ $isRefund && $convertedUsd > 0 ? '-' : '' }}${{ number_format($convertedUsd, 2) }}</div>
                                        <div class="text-xs text-gray-500">({{ number_format($paymentVnd, 0, ',', '.') }}đ)</div>
                                    @else
                                        <div class="text-gray-400">-</div>
                                    @endif
                                @else
                                    <!-- Hóa đơn VND: chỉ hiển thị VND -->
                                    @if($paymentVnd != 0)
                                        <div class="font-bold {{ $colorText }}">{{ number_format($paymentVnd, 0, ',', '.') }}đ</div>
                                    @elseif($paymentUsd != 0)
                                        @php
                                            $rate = $payment->payment_exchange_rate ?? $sale->exchange_rate;
                                            if ($rate <= 0)
                                                $rate = 1;
                                            $convertedVnd = $paymentUsd * $rate;
                                        @endphp
                                        <div class="font-bold {{ $colorText }}">{{ number_format($convertedVnd, 0, ',', '.') }}đ</div>
                                        <div class="text-xs text-gray-500">(${{ number_format($paymentUsd, 2) }})</div>
                                    @else
                                        <div class="text-gray-400">-</div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-2 py-2 text-center">
                                @if($payment->payment_method === 'cash')
                                    <span class="px-1.5 py-0.5 bg-green-100 text-green-700 text-xs rounded-full whitespace-nowrap">
                                        Tiền mặt
                                    </span>
                                @elseif($payment->payment_method === 'bank_transfer')
                                    <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full whitespace-nowrap">
                                        CK
                                    </span>
                                @else
                                    <span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">
                                        Thẻ
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $transactionType = $payment->transaction_type ?? 'sale_payment';
                                @endphp
                                @if($transactionType === 'sale_payment')
                                    <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">
                                        Bán hàng
                                    </span>
                                @elseif($transactionType === 'refund')
                                    <span class="px-3 py-1 bg-red-100 text-red-700 text-xs font-bold rounded-full border border-red-200">
                                        Hoàn tiền
                                    </span>
                                @elseif($transactionType === 'return')
                                    <span class="px-3 py-1 bg-orange-100 text-orange-700 text-xs font-semibold rounded-full">
                                        Trả hàng
                                    </span>
                                @elseif($transactionType === 'exchange' || $transactionType === 'exchange_payment')
                                    <span class="px-3 py-1 bg-purple-100 text-purple-700 text-xs font-semibold rounded-full">
                                        Đổi hàng
                                    </span>
                                @else
                                    <span class="px-3 py-1 bg-gray-100 text-gray-700 text-xs font-semibold rounded-full">
                                        {{ $payment->getTransactionTypeLabel() }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-bold">
                                @php
                                    // Kiểm tra nếu sale đã bị hủy
                                    $isCancelled = $sale->sale_status === 'cancelled';

                                    if ($isCancelled) {
                                        $remainingDebtUsd = 0;
                                        $remainingDebtVnd = 0;
                                    } else {
                                        // Tính số nợ còn lại SAU khi thanh toán này
                                        $rate = $payment->payment_exchange_rate ?? $sale->exchange_rate;
                                        if ($rate <= 0) $rate = (float)($sale->exchange_rate ?? 0);

                                        $paidUpToNowUsd = (float)$sale->payments
                                            ->where('id', '<=', $payment->id)
                                            ->sum('payment_usd');
                                        $paidUpToNowVnd = (float)$sale->payments
                                            ->where('id', '<=', $payment->id)
                                            ->sum('payment_vnd');

                                        // Fallback cho dữ liệu cũ (chỉ có amount)
                                        if ($paidUpToNowUsd == 0 && $paidUpToNowVnd == 0) {
                                            $amountSum = (float)$sale->payments->where('id', '<=', $payment->id)->sum('amount');
                                            if ($isUsdInvoice && !$isMixedInvoice && $rate > 0) {
                                                $paidUpToNowUsd = $amountSum / $rate;
                                            } else {
                                                $paidUpToNowVnd = $amountSum;
                                            }
                                        }

                                        if ($isMixedInvoice) {
                                            if ($rate > 0) {
                                                $fullTotalVnd = (float)$sale->total_vnd + ((float)$sale->total_usd * $rate);
                                                $totalPaidInVnd = (float)$paidUpToNowVnd + ((float)$paidUpToNowUsd * $rate);
                                                $diffVnd = $fullTotalVnd - $totalPaidInVnd;

                                                if ($diffVnd <= 1000 && $diffVnd >= -1000) {
                                                    $remainingDebtUsd = 0;
                                                    $remainingDebtVnd = 0;
                                                } elseif ($diffVnd > 1000) {
                                                    // Còn thiếu
                                                    $excessUsd = max(0, $paidUpToNowUsd - (float)$sale->total_usd);
                                                    $effectivePaidVnd = $paidUpToNowVnd + ($excessUsd * $rate);
                                                    $remainingDebtVnd = max(0, (float)$sale->total_vnd - $effectivePaidVnd);
                                                    if ($remainingDebtVnd <= 1000) $remainingDebtVnd = 0;

                                                    $excessVnd = max(0, $paidUpToNowVnd - (float)$sale->total_vnd);
                                                    $effectivePaidUsd = $paidUpToNowUsd + ($excessVnd / $rate);
                                                    $remainingDebtUsd = max(0, (float)$sale->total_usd - $effectivePaidUsd);
                                                    if ($remainingDebtUsd <= 0.05) $remainingDebtUsd = 0;
                                                } else {
                                                    // Dư tiền
                                                    if ($paidUpToNowUsd > (float)$sale->total_usd) {
                                                        $remainingDebtUsd = -round(abs($diffVnd) / $rate, 2);
                                                        $remainingDebtVnd = 0;
                                                    } else {
                                                        $remainingDebtUsd = 0;
                                                        $remainingDebtVnd = -round(abs($diffVnd));
                                                    }
                                                }
                                            } else {
                                                $remainingDebtUsd = (float)$sale->total_usd - $paidUpToNowUsd;
                                                $remainingDebtVnd = (float)$sale->total_vnd - $paidUpToNowVnd;
                                                if (abs($remainingDebtUsd) <= 0.05) $remainingDebtUsd = 0;
                                                if (abs($remainingDebtVnd) <= 1000) $remainingDebtVnd = 0;
                                            }
                                        } elseif ($isUsdInvoice) {
                                            // Hóa đơn USD: Quy đổi VND → USD nếu có
                                            $convertedUsd = ($rate > 0 && $paidUpToNowVnd > 0 ? $paidUpToNowVnd / $rate : 0);
                                            $totalPaidUsd = $paidUpToNowUsd + $convertedUsd;
                                            $remainingDebtUsd = (float)$sale->total_usd - $totalPaidUsd;
                                            if (abs($remainingDebtUsd) <= 0.50) {
                                                $remainingDebtUsd = 0;
                                            }
                                            $remainingDebtVnd = 0;
                                        } else {
                                            // Hóa đơn VND: Quy đổi USD → VND nếu có
                                            $convertedVnd = ($rate > 0 && $paidUpToNowUsd > 0 ? $paidUpToNowUsd * $rate : 0);
                                            $totalPaidVnd = $paidUpToNowVnd + $convertedVnd;
                                            $remainingDebtVnd = (float)$sale->total_vnd - $totalPaidVnd;
                                            if (abs($remainingDebtVnd) <= 1000) {
                                                $remainingDebtVnd = 0;
                                            }
                                            $remainingDebtUsd = 0;
                                        }
                                    }
                                @endphp

                                @if($isCancelled)
                                    <span class="text-gray-500 text-xs">(Đã hủy)</span>
                                @else
                                    @if($isMixedInvoice)
                                        <!-- Hóa đơn hỗn hợp: hiển thị cả USD và VND -->
                                        @if($remainingDebtUsd == 0 && $remainingDebtVnd == 0)
                                            <div class="text-gray-900 font-bold">$0.00</div>
                                        @else
                                            @if($remainingDebtUsd != 0)
                                                <div
                                                    class="{{ $remainingDebtUsd > 0.05 ? 'text-red-600' : 'text-green-600' }}">
                                                    ${{ number_format($remainingDebtUsd, 2) }}
                                                </div>
                                            @endif
                                            @if($remainingDebtVnd != 0)
                                                <div
                                                    class="text-xs {{ $remainingDebtVnd > 1000 ? 'text-red-500' : 'text-green-500' }}">
                                                    {{ number_format($remainingDebtVnd, 0, ',', '.') }}đ
                                                </div>
                                            @endif
                                        @endif
                                    @elseif($isUsdInvoice)
                                        <!-- Hóa đơn USD: chỉ hiển thị USD -->
                                        <div
                                            class="{{ $remainingDebtUsd > 0.05 ? 'text-red-600' : ($remainingDebtUsd < -0.05 ? 'text-green-600' : 'text-gray-900') }}">
                                            ${{ number_format($remainingDebtUsd, 2) }}
                                        </div>
                                    @else
                                        <!-- Hóa đơn VND: chỉ hiển thị VND -->
                                        <div
                                            class="{{ $remainingDebtVnd > 1000 ? 'text-red-600' : ($remainingDebtVnd < -1000 ? 'text-green-600' : 'text-gray-900') }}">
                                            {{ number_format($remainingDebtVnd, 0, ',', '.') }}đ
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center space-x-2">
                                    @php
                                        // Kiểm tra còn nợ thực tế (USD hoặc VND)
                                        // Sử dụng $remainingDebtUsd và $remainingDebtVnd đã tính ở trên
                                        $hasDebtNow = ($remainingDebtUsd > 0.05) || ($remainingDebtVnd > 1000);
                                    @endphp

                                    {{-- Debug: Hiển thị cho mỗi dòng --}}
                                    <div style="display:none;">
                                        DEBUG {{ $payment->sale->invoice_code }}:
                                        Debt={{ $payment->sale->debt ? 'Y' : 'N' }},
                                        RemainUSD={{ number_format($remainingDebtUsd, 2) }},
                                        RemainVND={{ number_format($remainingDebtVnd, 0) }},
                                        HasDebt={{ $hasDebtNow ? 'Y' : 'N' }},
                                        Show={{ ($payment->sale->debt && $hasDebtNow) ? 'YES' : 'NO' }}
                                    </div>

                                    @if($hasDebtNow)
                                        @if($payment->sale->debt)
                                            <a href="{{ route('debt.show', $payment->sale->debt->id) }}"
                                                class="w-8 h-8 flex items-center justify-center bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-colors"
                                                title="Thanh toán nợ">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </a>
                                        @else
                                            {{-- Không có debt record, dẫn đến sales.edit để trả nợ --}}
                                            <a href="{{ route('sales.edit', $payment->sale_id) }}"
                                                class="w-8 h-8 flex items-center justify-center bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-colors"
                                                title="Thanh toán nợ (qua phiếu bán)">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </a>
                                        @endif
                                    @endif
                                    <a href="{{ route('sales.show', $payment->sale_id) }}"
                                        class="w-8 h-8 flex items-center justify-center bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-colors"
                                        title="Xem chi tiết phiếu bán hàng">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>Chưa có lịch sử thanh toán nào</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $payments->links() }}
        </div>
    </div>

    @push('scripts')
        <script>
            function toggleExportDropdown() {
                const dropdown = document.getElementById('exportDropdown');
                dropdown.classList.toggle('hidden');
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', function (event) {
                const dropdown = document.getElementById('exportDropdown');
                const button = event.target.closest('[onclick="toggleExportDropdown()"]');

                if (dropdown && !dropdown.contains(event.target) && !button) {
                    dropdown.classList.add('hidden');
                }
            });

            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.getElementById('searchInput');
                const suggestionsBox = document.getElementById('searchSuggestions');
                let debounceTimer;

                searchInput.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    const query = this.value.trim();

                    if (query.length < 2) {
                        suggestionsBox.classList.add('hidden');
                        return;
                    }

                    debounceTimer = setTimeout(() => {
                        fetch(`{{ route('debt.api.search.suggestions') }}?q=${encodeURIComponent(query)}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.length === 0) {
                                    suggestionsBox.classList.add('hidden');
                                    return;
                                }

                                suggestionsBox.innerHTML = data.map(item => `
                                <div class="suggestion-item px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0" data-value="${item.value}">
                                    <div class="flex items-center">
                                        <i class="fas ${item.type === 'customer' ? 'fa-user' : 'fa-file-invoice'} text-blue-500 mr-2"></i>
                                        <span class="text-gray-800">${item.label}</span>
                                    </div>
                                </div>
                            `).join('');

                                suggestionsBox.classList.remove('hidden');

                                // Add click handlers
                                document.querySelectorAll('.suggestion-item').forEach(item => {
                                    item.addEventListener('click', function () {
                                        searchInput.value = this.dataset.value;
                                        suggestionsBox.classList.add('hidden');
                                        document.getElementById('searchForm').submit();
                                    });
                                });
                            })
                            .catch(error => {
                                console.error('Search error:', error);
                                suggestionsBox.classList.add('hidden');
                            });
                    }, 300);
                });

                // Close suggestions when clicking outside
                document.addEventListener('click', function (e) {
                    if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                        suggestionsBox.classList.add('hidden');
                    }
                });

                // Handle keyboard navigation
                searchInput.addEventListener('keydown', function (e) {
                    const items = suggestionsBox.querySelectorAll('.suggestion-item');
                    if (items.length === 0) return;

                    const activeItem = suggestionsBox.querySelector('.bg-blue-100');
                    let currentIndex = Array.from(items).indexOf(activeItem);

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        if (activeItem) activeItem.classList.remove('bg-blue-100');
                        currentIndex = (currentIndex + 1) % items.length;
                        items[currentIndex].classList.add('bg-blue-100');
                        items[currentIndex].scrollIntoView({ block: 'nearest' });
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        if (activeItem) activeItem.classList.remove('bg-blue-100');
                        currentIndex = currentIndex <= 0 ? items.length - 1 : currentIndex - 1;
                        items[currentIndex].classList.add('bg-blue-100');
                        items[currentIndex].scrollIntoView({ block: 'nearest' });
                    } else if (e.key === 'Enter' && activeItem) {
                        e.preventDefault();
                        searchInput.value = activeItem.dataset.value;
                        suggestionsBox.classList.add('hidden');
                        document.getElementById('searchForm').submit();
                    } else if (e.key === 'Escape') {
                        suggestionsBox.classList.add('hidden');
                    }
                });
            });
        </script>
    @endpush
@endsection