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
                    <i class="fas fa-search mr-2"></i>Tìm kiếm
                </label>
                <input type="text" name="search" id="searchInput" value="{{ request('search') }}" 
                    placeholder="Tên, SĐT, Mã HĐ..." 
                    autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <div id="searchSuggestions" class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-calendar mr-2"></i>Từ ngày
                </label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-calendar-alt mr-2"></i>Đến ngày
                </label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-filter mr-2"></i>Trạng thái TT
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
                    <i class="fas fa-dollar-sign mr-2"></i>Lọc theo tiền
                </label>
                <select name="amount_filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">-- Chọn loại lọc --</option>
                    <option value="under_10m" {{ request('amount_filter') == 'under_10m' ? 'selected' : '' }}>Dưới 10 triệu</option>
                    <option value="10m_50m" {{ request('amount_filter') == '10m_50m' ? 'selected' : '' }}>10-50 triệu</option>
                    <option value="50m_100m" {{ request('amount_filter') == '50m_100m' ? 'selected' : '' }}>50-100 triệu</option>
                    <option value="over_100m" {{ request('amount_filter') == 'over_100m' ? 'selected' : '' }}>Trên 100 triệu</option>
                </select>
            </div>

            <div class="flex items-end space-x-2 col-span-2">
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
                    <th class="px-4 py-3 text-left">Ngày TT</th>
                    <th class="px-4 py-3 text-left">Mã HĐ</th>
                    <th class="px-4 py-3 text-left">Khách hàng</th>
                    <th class="px-4 py-3 text-left">SĐT</th>
                    <th class="px-4 py-3 text-right">Tổng tiền</th>
                    <th class="px-4 py-3 text-right">Đã trả</th>
                    <th class="px-4 py-3 text-right">Còn nợ</th>
                    <th class="px-4 py-3 text-center">TT Thanh toán</th>
                    <th class="px-4 py-3 text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($payments as $payment)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3">
                        <span class="text-gray-900 text-sm">{{ $payment->payment_date->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y') }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="font-medium text-blue-600">{{ $payment->sale->invoice_code }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ $payment->sale->customer->name }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <i class="fas fa-phone text-blue-500 mr-2"></i>{{ $payment->sale->customer->phone ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-right font-medium text-gray-900">
                        {{ number_format($payment->sale->total_vnd, 0, ',', '.') }}đ
                    </td>
                    <td class="px-4 py-3 text-right text-green-600 font-bold">
                        {{ number_format($payment->amount, 0, ',', '.') }}đ
                    </td>
                    <td class="px-4 py-3 text-right text-red-600 font-bold">
                        @php
                            // Tính số nợ còn lại SAU khi thanh toán này (dùng ID để đảm bảo thứ tự)
                            $paidUpToNow = $payment->sale->payments()
                                ->where('id', '<=', $payment->id)
                                ->sum('amount');
                            $remainingDebt = $payment->sale->total_vnd - $paidUpToNow;
                        @endphp
                        {{ number_format($remainingDebt, 0, ',', '.') }}đ
                    </td>
                    <td class="px-4 py-3 text-center">
                        @php
                            // Tính tổng đã trả TẠI THỜI ĐIỂM payment này (dùng ID)
                            $paidAtThisTime = $payment->sale->payments()
                                ->where('id', '<=', $payment->id)
                                ->sum('amount');
                            $totalAmount = $payment->sale->total_vnd;
                            
                            // Xác định trạng thái tại thời điểm đó
                            if ($paidAtThisTime >= $totalAmount) {
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
                                    title="Trả nợ">
                                    <i class="fas fa-money-bill-wave"></i>
                                </a>
                            @endif
                            <a href="{{ route('sales.show', $payment->sale_id) }}" 
                                class="w-8 h-8 flex items-center justify-center bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-colors" 
                                title="Xem hóa đơn">
                                <i class="fas fa-file-invoice"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
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
