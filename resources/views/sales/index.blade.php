@extends('layouts.app')

@section('title', 'Bán hàng')
@section('page-title', 'Bán hàng')
@section('page-description', 'Quản lý tất cả các giao dịch bán hàng')

@section('header-actions')
@hasPermission('sales', 'can_create')
<a href="{{ route('sales.create') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
    <i class="fas fa-plus mr-2"></i>Tạo hóa đơn
</a>
@endhasPermission
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

<div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
    <!-- Search and Filter - Simplified for elderly users -->
    <div class="bg-gray-50 p-5 rounded-lg mb-6">
        <form method="GET" action="{{ route('sales.index') }}" id="filter-form">
            <!-- Main Row: Search + Date + Status -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <!-- Search with suggestions -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tìm kiếm
                    </label>
                    <div class="relative">
                        <input type="text" 
                               id="search-input" 
                               name="search" 
                               value="{{ request('search') }}" 
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                               placeholder="Nhập mã HD, tên khách hàng, SĐT..."
                               autocomplete="off">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        
                        <!-- Search suggestions dropdown -->
                        <div id="search-suggestions" class="hidden absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            <!-- Suggestions will be loaded here -->
                        </div>
                    </div>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Từ ngày
                    </label>
                    <input type="date" 
                           name="from_date" 
                           value="{{ request('from_date') }}" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Đến ngày
                    </label>
                    <input type="date" 
                           name="to_date" 
                           value="{{ request('to_date') }}" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            <!-- Second Row: Status + Dynamic Filter -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <!-- Payment Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Trạng thái TT
                    </label>
                    <select name="payment_status" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Tất cả --</option>
                        <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Đã Thanh Toán</option>
                        <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Thanh Toán một phần</option>
                        <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Chưa Thanh Toán</option>
                    </select>
                </div>

                <!-- Dynamic Filter Type Selector -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-filter mr-2"></i>Lọc thêm theo
                    </label>
                    <select id="filter-type" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            onchange="showFilterOptions(this.value)">
                        <option value="">-- Chọn loại lọc --</option>
                        <option value="amount" {{ request('min_amount') || request('max_amount') ? 'selected' : '' }}>Theo số tiền</option>
                        <option value="debt" {{ request('has_debt') !== null ? 'selected' : '' }}>Theo công nợ</option>
                        <option value="showroom" {{ request('showroom_id') ? 'selected' : '' }}>Theo showroom</option>
                        <option value="user" {{ request('user_id') ? 'selected' : '' }}>Theo nhân viên</option>
                    </select>
                </div>

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

                    <!-- User Filter -->
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
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between items-center pt-3 border-t">
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition-colors">
                        <i class="fas fa-search mr-2"></i>Tìm kiếm
                    </button>
                    <a href="{{ route('sales.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                        <i class="fas fa-redo mr-2"></i>Làm mới
                    </a>
                </div>
                <div class="text-sm text-gray-700">
                    Tìm thấy: <span class="text-blue-600 font-medium">{{ $sales->total() }}</span> đơn hàng
                </div>
            </div>
        </form>
    </div>
    
    <!-- Sales Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white">
                <tr>
                    <th class="px-4 py-3 text-left">Mã HD</th>
                    <th class="px-4 py-3 text-left">Ngày bán</th>
                    <th class="px-4 py-3 text-left">Khách hàng</th>
                    <th class="px-4 py-3 text-left">Showroom</th>
                    <th class="px-4 py-3 text-left">Nhân viên</th>
                    <th class="px-4 py-3 text-right">Tổng tiền</th>
                    <th class="px-4 py-3 text-right">Đã trả</th>
                    <th class="px-4 py-3 text-right">Còn nợ</th>
                    <th class="px-4 py-3 text-center">Tình trạng</th>
                    <th class="px-4 py-3 text-center">Thanh toán</th>
                    <th class="px-4 py-3 text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($sales as $sale)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3">
                        <span class="font-medium text-blue-600">{{ $sale->invoice_code }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-gray-900">{{ $sale->sale_date->format('d/m/Y') }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ $sale->customer->name }}</div>
                        <div class="text-sm text-gray-600">{{ $sale->customer->phone }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-gray-900">{{ $sale->showroom->name }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-gray-700">
                            {{ $sale->user->name }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="font-medium text-gray-900">{{ number_format($sale->total_vnd) }}đ</div>
                        <div class="text-xs text-gray-500">${{ number_format($sale->total_usd, 2) }}</div>
                    </td>
                    <td class="px-4 py-3 text-right text-green-600 font-bold">
                        {{ number_format($sale->paid_amount) }}đ
                    </td>
                    <td class="px-4 py-3 text-right text-red-600 font-bold">
                        {{ number_format($sale->debt_amount) }}đ
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($sale->sale_status == 'pending')
                            <span class="px-3 py-2 text-sm font-bold rounded-lg bg-yellow-100 text-yellow-800">
                                <i class="fas fa-clock mr-1"></i>Chờ duyệt
                            </span>
                        @elseif($sale->sale_status == 'completed')
                            <span class="px-3 py-2 text-sm font-bold rounded-lg bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Đã duyệt phiếu
                            </span>
                        @elseif($sale->sale_status == 'cancelled')
                            <span class="px-3 py-2 text-sm font-bold rounded-lg bg-gray-100 text-gray-800">
                                <i class="fas fa-ban mr-1"></i>Đã hủy
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($sale->payment_status == 'cancelled')
                            <span class="px-3 py-2 text-sm font-bold rounded-lg bg-gray-100 text-gray-800">Đã hủy</span>
                        @elseif($sale->payment_status == 'paid')
                            <span class="px-3 py-2 text-sm font-bold rounded-lg bg-green-100 text-green-800">Đã Thanh Toán</span>
                        @elseif($sale->payment_status == 'partial')
                            <span class="px-3 py-2 text-sm font-bold rounded-lg bg-yellow-100 text-yellow-800">Thanh Toán một phần</span>
                        @else
                            <span class="px-3 py-2 text-sm font-bold rounded-lg bg-red-100 text-red-800">Chưa Thanh Toán</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center space-x-2">
                            <!-- Approve button - chỉ hiện khi chờ duyệt -->
                            @if($sale->canApprove())
                            <form method="POST" action="{{ route('sales.approve', $sale->id) }}" class="inline">
                                @csrf
                                <button type="submit" 
                                        onclick="return confirm('Xác nhận duyệt phiếu {{ $sale->invoice_code }}?')"
                                        class="w-8 h-8 flex items-center justify-center bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-colors" 
                                        title="Duyệt phiếu">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                            </form>
                            @endif
                            
                            <!-- Cancel button - chỉ hiện khi chờ duyệt và chưa thanh toán -->
                            @if($sale->isPending() && $sale->paid_amount == 0)
                            <form method="POST" action="{{ route('sales.cancel', $sale->id) }}" class="inline">
                                @csrf
                                <button type="submit" 
                                        onclick="return confirm('Xác nhận hủy phiếu {{ $sale->invoice_code }}?')"
                                        class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors" 
                                        title="Hủy phiếu">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </form>
                            @endif
                            
                            <!-- Show button - luôn hiển thị nếu có quyền xem -->
                            @hasPermission('sales', 'can_view')
                            <a href="{{ route('sales.show', $sale->id) }}" 
                               class="w-8 h-8 flex items-center justify-center bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-colors" 
                               title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            @endhasPermission
                            
                            <!-- Edit button - chỉ hiện khi có quyền và chờ duyệt -->
                            @hasPermission('sales', 'can_edit')
                                @if($sale->canEdit())
                                <a href="{{ route('sales.edit', $sale->id) }}" 
                                   class="w-8 h-8 flex items-center justify-center bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-colors" 
                                   title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @else
                                <span class="w-8 h-8 flex items-center justify-center bg-gray-100 text-gray-400 rounded-lg cursor-not-allowed" 
                                      title="Không thể sửa">
                                    <i class="fas fa-lock"></i>
                                </span>
                                @endif
                            @endhasPermission
                            
                            <!-- Print button - ẩn khi đã hủy hoặc không có quyền -->
                            @hasPermission('sales', 'can_print')
                                @if($sale->payment_status != 'cancelled')
                                <a href="{{ route('sales.print', $sale->id) }}" 
                                   target="_blank" 
                                   class="w-8 h-8 flex items-center justify-center bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-colors" 
                                   title="In hóa đơn">
                                    <i class="fas fa-print"></i>
                                </a>
                                @endif
                            @endhasPermission
                            
                            <!-- Delete button - hiển thị khi có quyền và chưa thanh toán -->
                            @hasPermission('sales', 'can_delete')
                                @if($sale->paid_amount == 0)
                                <button type="button" 
                                        class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors delete-btn" 
                                        title="Xóa"
                                        data-url="{{ route('sales.destroy', $sale->id) }}"
                                        data-message="Bạn có chắc chắn muốn xóa hóa đơn {{ $sale->invoice_code }}?">
                                    <i class="fas fa-trash text-lg"></i>
                                </button>
                                @else
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed" 
                                      title="Đã có thanh toán">
                                    <i class="fas fa-lock text-lg"></i>
                                </span>
                                @endif
                            @endhasPermission
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
    @if($sales->hasPages())
    <div class="mt-4">
        {{ $sales->links() }}
    </div>
    @endif
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
    // Hide all filter options
    document.getElementById('filter-amount').classList.add('hidden');
    document.getElementById('filter-debt').classList.add('hidden');
    document.getElementById('filter-showroom').classList.add('hidden');
    document.getElementById('filter-user').classList.add('hidden');
    
    // Show selected filter option
    if (type) {
        document.getElementById('filter-' + type).classList.remove('hidden');
    }
}

// Initialize filter on page load
document.addEventListener('DOMContentLoaded', function() {
    const filterType = document.getElementById('filter-type').value;
    if (filterType) {
        showFilterOptions(filterType);
    }
});

// Search suggestions
let searchTimeout;
const searchInput = document.getElementById('search-input');
const suggestionsBox = document.getElementById('search-suggestions');

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

// Close suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
        suggestionsBox.classList.add('hidden');
    }
});

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
</script>
@endpush
