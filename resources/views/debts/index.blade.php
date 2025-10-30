@extends('layouts.app')

@section('title', 'Lịch sử Công nợ')
@section('page-title', 'Lịch sử Công nợ')
@section('page-description', 'Quản lý và theo dõi công nợ khách hàng')

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

<div class="bg-white rounded-xl shadow-lg p-6 fade-in">
    <!-- Search & Filter -->
    <form method="GET" class="mb-6" id="searchForm">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Tìm kiếm
                </label>
                <input type="text" name="search" id="searchInput" value="{{ request('search') }}" 
                    placeholder="Tên, SĐT, Mã HĐ..." 
                    autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <div id="searchSuggestions" class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Từ ngày
                </label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Đến ngày
                </label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Trạng thái TT
                </label>
                <select name="payment_status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">-- Tất cả --</option>
                    <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Đã Thanh Toán</option>
                    <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Thanh Toán một phần</option>
                    <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Chưa Thanh Toán</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Số tiền từ
                </label>
                <input type="number" name="amount_from" value="{{ request('amount_from') }}" 
                    placeholder="Từ..." 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Số tiền đến
                </label>
                <input type="number" name="amount_to" value="{{ request('amount_to') }}" 
                    placeholder="Đến..." 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div class="flex items-end space-x-2 col-span-2 md:col-span-2">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition-colors">
                    <i class="fas fa-search mr-2"></i>Tìm kiếm
                </button>
                <a href="{{ route('debt.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                    <i class="fas fa-redo mr-2"></i>Làm mới
                </a>
                
                <!-- Export Dropdown -->
                <div class="relative">
                    <button onclick="toggleExportDropdown()" type="button" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg transition-colors flex items-center">
                        <i class="fas fa-download mr-2"></i>Xuất file
                        <i class="fas fa-chevron-down ml-2 text-xs"></i>
                    </button>
                    <div id="exportDropdown" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl z-50 border border-gray-200">
                        <!-- Excel Export -->
                        <div class="py-2 border-b border-gray-200">
                            <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Excel</div>
                            <a href="{{ route('debt.export.excel', array_merge(request()->query(), ['scope' => 'current'])) }}" 
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 transition-colors">
                                <i class="fas fa-file-excel text-green-600 mr-2"></i>Trang hiện tại
                            </a>
                            <a href="{{ route('debt.export.excel', array_merge(request()->query(), ['scope' => 'all'])) }}" 
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 transition-colors">
                                <i class="fas fa-file-excel text-green-600 mr-2"></i>Tất cả kết quả
                            </a>
                        </div>
                        <!-- PDF Export -->
                        <div class="py-2">
                            <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">PDF</div>
                            <a href="{{ route('debt.export.pdf', array_merge(request()->query(), ['scope' => 'current'])) }}" 
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-red-50 transition-colors">
                                <i class="fas fa-file-pdf text-red-600 mr-2"></i>Trang hiện tại
                            </a>
                            <a href="{{ route('debt.export.pdf', array_merge(request()->query(), ['scope' => 'all'])) }}" 
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-red-50 transition-colors">
                                <i class="fas fa-file-pdf text-red-600 mr-2"></i>Tất cả kết quả
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Debts Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white">
                <tr>
                    <th class="px-4 py-3 text-center">STT</th>
                    <th class="px-4 py-3 text-left">Ngày trả tiền</th>
                    <th class="px-4 py-3 text-left">Mã hóa đơn</th>
                    <th class="px-4 py-3 text-left">Khách hàng</th>
                    <th class="px-4 py-3 text-left">Số điện thoại</th>
                    <th class="px-4 py-3 text-right">Tổng hóa đơn</th>
                    <th class="px-4 py-3 text-right">Số tiền trả lần này</th>
                    <th class="px-4 py-3 text-center">Phương thức</th>
                    <th class="px-4 py-3 text-center">Loại giao dịch</th>
                    <th class="px-4 py-3 text-right">Còn thiếu</th>
                    <th class="px-4 py-3 text-center">Tình trạng</th>
                    <th class="px-4 py-3 text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($payments as $index => $payment)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-center text-gray-600 font-medium">
                        {{ ($payments->currentPage() - 1) * $payments->perPage() + $index + 1 }}
                    </td>
                    <td class="px-4 py-3">
                        @php
                            // Debug: Log payment date info
                            \Log::info("Payment #{$payment->id} - Raw: {$payment->payment_date} | Formatted: " . $payment->payment_date->format('Y-m-d H:i:s'));
                        @endphp
                        <div class="text-gray-900 text-sm font-medium">{{ $payment->payment_date->format('d/m/Y') }}</div>
                        <div class="text-gray-500 text-xs">{{ $payment->payment_date->format('H:i:s') }}</div>
                        <!-- <div class="text-xs text-gray-400">Debug: {{ $payment->payment_date->format('Y-m-d H:i:s') }}</div> -->
                    </td>
                    <td class="px-4 py-3">
                        <span class="font-medium text-blue-600">{{ $payment->sale->invoice_code }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ $payment->sale->customer->name }}</div>
                    </td>
                    <td class="px-4 py-3">
                        {{ $payment->sale->customer->phone ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-right font-medium">
                        @php
                            $sale = $payment->sale;
                            $hasReturns = $sale->returns()->where('status', 'completed')->where('type', 'return')->exists();
                            $hasExchanges = $sale->returns()->where('status', 'completed')->where('type', 'exchange')->exists();
                            
                            // Lấy original_total, nếu không có thì tính từ items
                            if ($sale->original_total_vnd) {
                                $originalTotal = $sale->original_total_vnd;
                            } else {
                                $originalTotal = $sale->saleItems->sum('total_vnd');
                            }
                        @endphp
                        
                        @if($hasReturns && $sale->total_vnd == 0)
                            <!-- Trả hết - hiển thị giá gốc không gạch ngang -->
                            <div class="text-gray-900">{{ number_format($originalTotal, 0, ',', '.') }}đ</div>
                            <div class="text-xs text-red-600 mt-1">
                                <i class="fas fa-undo mr-1"></i>Đã trả hết
                            </div>
                        @elseif(($hasReturns || $hasExchanges) && $originalTotal != $sale->total_vnd)
                            <!-- Trả/Đổi một phần - hiển thị giá gốc gạch ngang và giá mới -->
                            <div class="text-xs text-gray-400 line-through">{{ number_format($originalTotal, 0, ',', '.') }}đ</div>
                            <div class="text-orange-600">{{ number_format($sale->total_vnd, 0, ',', '.') }}đ</div>
                        @else
                            <!-- Không có trả/đổi hàng -->
                            <div class="text-gray-900">{{ number_format($sale->total_vnd, 0, ',', '.') }}đ</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right text-green-600 font-bold">
                        {{ number_format($payment->amount, 0, ',', '.') }}đ
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($payment->payment_method === 'cash')
                            <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">
                                Tiền mặt
                            </span>
                        @elseif($payment->payment_method === 'bank_transfer')
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">
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
                                <i class="fas fa-shopping-cart mr-1"></i>Bán hàng
                            </span>
                        @elseif($transactionType === 'return')
                            <span class="px-3 py-1 bg-orange-100 text-orange-700 text-xs font-semibold rounded-full">
                                <i class="fas fa-undo mr-1"></i>Trả hàng
                            </span>
                        @elseif($transactionType === 'exchange')
                            <span class="px-3 py-1 bg-purple-100 text-purple-700 text-xs font-semibold rounded-full">
                                <i class="fas fa-exchange-alt mr-1"></i>Đổi hàng
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-bold">
                        @php
                            // Kiểm tra nếu sale đã bị hủy (trả hàng)
                            $isCancelled = $payment->sale->sale_status === 'cancelled';
                            
                            if ($isCancelled) {
                                // Đã hủy - không còn nợ
                                $remainingDebt = 0;
                            } else {
                                // Tính số nợ còn lại SAU khi thanh toán này
                                $paidUpToNow = $payment->sale->payments()
                                    ->where('id', '<=', $payment->id)
                                    ->sum('amount');
                                $remainingDebt = $payment->sale->total_vnd - $paidUpToNow;
                            }
                        @endphp
                        @if($isCancelled)
                            <span class="text-gray-500 text-xs">
                                (Đã hủy)
                            </span>
                        @else
                            <span class="{{ $remainingDebt > 0 ? 'text-red-600' : ($remainingDebt < 0 ? 'text-green-600' : 'text-gray-600') }}">
                                {{ number_format($remainingDebt, 0, ',', '.') }}đ
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @php
                            // Tính tổng đã trả TẠI THỜI ĐIỂM payment này (dùng ID)
                            $paidAtThisTime = $payment->sale->payments()
                                ->where('id', '<=', $payment->id)
                                ->sum('amount');
                            $totalAmount = $payment->sale->total_vnd;
                            
                            // Xác định trạng thái tại thời điểm đó
                            if ($payment->sale->sale_status == 'cancelled') {
                                $statusClass = 'bg-gray-100 text-gray-800';
                                $statusText = 'Đã hủy';
                            } elseif ($paidAtThisTime >= $totalAmount) {
                                $statusClass = 'bg-green-100 text-green-800';
                                $statusText = 'Đã Thanh Toán';
                            } elseif ($paidAtThisTime > 0) {
                                $statusClass = 'bg-yellow-100 text-yellow-800';
                                $statusText = 'Thanh Toán một phần';
                            } else {
                                $statusClass = 'bg-red-100 text-red-800';
                                $statusText = 'Chưa Thanh Toán';
                            }
                        @endphp
                        <span class="px-3 py-2 text-sm font-bold rounded-lg {{ $statusClass }}">{{ $statusText }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center space-x-2">
                            @if($payment->sale->debt)
                                <a href="{{ route('debt.show', $payment->sale->debt->id) }}" 
                                    class="w-8 h-8 flex items-center justify-center bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-colors" 
                                    title="Thanh toán">
                                    <i class="fas fa-money-bill-wave"></i>
                                </a>
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
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('exportDropdown');
    const button = event.target.closest('[onclick="toggleExportDropdown()"]');
    
    if (dropdown && !dropdown.contains(event.target) && !button) {
        dropdown.classList.add('hidden');
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const suggestionsBox = document.getElementById('searchSuggestions');
    let debounceTimer;

    searchInput.addEventListener('input', function() {
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
                        item.addEventListener('click', function() {
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
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.classList.add('hidden');
        }
    });

    // Handle keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
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
