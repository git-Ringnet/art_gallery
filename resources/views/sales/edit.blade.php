@extends('layouts.app')

@section('title', 'Sửa hóa đơn bán hàng')
@section('page-title', 'Sửa hóa đơn bán hàng')
@section('page-description', 'Chỉnh sửa hóa đơn bán hàng')

@section('content')
<x-alert />

@php
    $hasReturns = $sale->returns()->whereIn('status', ['approved', 'completed'])->exists();
@endphp

@if($hasReturns)
<div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6 rounded-lg">
    <div class="flex items-center">
        <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl mr-3"></i>
        <div>
            <h4 class="text-yellow-800 font-semibold">Lưu ý: Phiếu này đã có trả/đổi hàng</h4>
            <p class="text-yellow-700 text-sm">Không thể sửa danh sách sản phẩm. Chỉ có thể trả thêm tiền hoặc cập nhật thông tin khách hàng.</p>
        </div>
    </div>
</div>
@endif

<div class="bg-white rounded-xl shadow-lg p-8 glass-effect">
    <form action="{{ route('sales.update', $sale->id) }}" method="POST" id="sales-form">
        @csrf
        @method('PUT')
        
        <!-- BƯỚC 1: THÔNG TIN CƠ BẢN -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-6">
            <h3 class="text-xl font-bold text-blue-900 mb-4 flex items-center">
                <span class="bg-blue-500 text-white w-8 h-8 rounded-full flex items-center justify-center mr-3">1</span>
                Thông tin hóa đơn
            </h3>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Số hóa đơn</label>
                    <div class="flex gap-2">
                        <input type="text" 
                               name="invoice_code" 
                               id="invoice_code" 
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg font-medium text-blue-600" 
                               value="{{ $sale->invoice_code }}">
                        <button type="button" 
                                onclick="generateInvoiceCode()" 
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors"
                                title="Tự động tạo">
                            <i class="fas fa-magic"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Showroom <span class="text-red-500">*</span></label>
                    <select name="showroom_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Chọn showroom --</option>
                        @foreach($showrooms as $showroom)
                            <option value="{{ $showroom->id }}" {{ $sale->showroom_id == $showroom->id ? 'selected' : '' }}>{{ $showroom->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ngày bán <span class="text-red-500">*</span></label>
                    <input type="date" name="sale_date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" value="{{ $sale->sale_date->format('Y-m-d') }}">
                </div>
            </div>
        </div>

        <!-- BƯỚC 2: THÔNG TIN KHÁCH HÀNG -->
        <div class="bg-green-50 border-l-4 border-green-500 p-6 rounded-lg mb-6">
            <h3 class="text-xl font-bold text-green-900 mb-4 flex items-center">
                <span class="bg-green-500 text-white w-8 h-8 rounded-full flex items-center justify-center mr-3">2</span>
                Thông tin khách hàng
            </h3>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tên khách hàng <span class="text-red-500">*</span></label>
                    <input type="text" 
                           name="customer_name" 
                           id="customer_name" 
                           required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                           value="{{ $sale->customer->name }}"
                           autocomplete="off"
                           onkeyup="filterCustomers(this.value)">
                    <input type="hidden" name="customer_id" id="customer_id" value="{{ $sale->customer_id }}">
                    <div id="customer-suggestions" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-y-auto hidden shadow-lg"></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Số điện thoại <span class="text-red-500">*</span></label>
                    <input type="tel" name="customer_phone" id="customer_phone" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" value="{{ $sale->customer->phone }}">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="customer_email" id="customer_email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" value="{{ $sale->customer->email }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Địa chỉ</label>
                    <input type="text" name="customer_address" id="customer_address" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" value="{{ $sale->customer->address }}">
                </div>
            </div>
        </div>

        <!-- BƯỚC 3: DANH SÁCH SẢN PHẨM -->
        @if($hasReturns)
            <!-- Hiển thị readonly khi đã có return -->
            <div class="bg-gray-50 border-l-4 border-gray-400 p-6 rounded-lg mb-6">
                <h3 class="text-xl font-bold text-gray-700 mb-4 flex items-center">
                    <span class="bg-gray-400 text-white w-8 h-8 rounded-full flex items-center justify-center mr-3">3</span>
                    Danh sách sản phẩm (Chỉ xem)
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left">Hình ảnh</th>
                                <th class="px-4 py-3 text-left">Sản phẩm</th>
                                <th class="px-4 py-3 text-center">SL</th>
                                <th class="px-4 py-3 text-right">Đơn giá</th>
                                <th class="px-4 py-3 text-right">Giảm giá</th>
                                <th class="px-4 py-3 text-right">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y">
                            @foreach($sale->saleItems as $item)
                            <tr class="{{ $item->is_returned ? 'bg-red-50 opacity-60' : '' }}">
                                <td class="px-4 py-3">
                                    @if($item->painting_id && $item->painting && $item->painting->image)
                                        <img src="{{ asset('storage/' . $item->painting->image) }}" class="w-16 h-16 object-cover rounded" alt="{{ $item->description }}">
                                    @else
                                        <div class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center">
                                            <i class="fas fa-image text-gray-400"></i>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium {{ $item->is_returned ? 'line-through text-gray-500' : '' }}">
                                        {{ $item->description }}
                                    </div>
                                    @if($item->is_returned)
                                        <span class="text-xs text-red-600 font-semibold">
                                            <i class="fas fa-undo mr-1"></i>Đã trả
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if($item->currency === 'USD')
                                        ${{ number_format($item->price_usd, 2) }}
                                    @else
                                        {{ number_format($item->price_vnd, 0, ',', '.') }}đ
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">{{ $item->discount_percent }}%</td>
                                <td class="px-4 py-3 text-right font-semibold">
                                    {{ number_format($item->total_vnd, 0, ',', '.') }}đ
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <!-- Form edit bình thường khi chưa có return -->
            <div class="bg-purple-50 border-l-4 border-purple-500 p-6 rounded-lg mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-purple-900 flex items-center">
                        <span class="bg-purple-500 text-white w-8 h-8 rounded-full flex items-center justify-center mr-3">3</span>
                        Danh sách sản phẩm
                    </h3>
                    <button type="button" onclick="addItem()" class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-2 rounded-lg transition-colors font-medium">
                        <i class="fas fa-plus mr-2"></i>Thêm sản phẩm
                    </button>
                </div>
                <div class="#">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-purple-100">
                                <th class="px-3 py-3 text-left text-sm font-medium text-gray-700 border">Hình ảnh</th>
                                <th class="px-3 py-3 text-left text-sm font-medium text-gray-700 border">Mô tả(Mã tranh/Khung)</th>
                                <th class="px-3 py-3 text-left text-sm font-medium text-gray-700 border">Vật tư(Khung)</th>
                                <th class="px-3 py-3 text-left text-sm font-medium text-gray-700 border">Số mét/Cây</th>
                                <th class="px-3 py-3 text-center text-sm font-medium text-gray-700 border">Số lượng</th>
                                <th class="px-3 py-3 text-center text-sm font-medium text-gray-700 border">Loại tiền</th>
                                <th class="px-3 py-3 text-right text-sm font-medium text-gray-700 border">Giá USD</th>
                                <th class="px-3 py-3 text-right text-sm font-medium text-gray-700 border">Giá VND</th>
                                <th class="px-3 py-3 text-center text-sm font-medium text-gray-700 border">Giảm giá (%)</th>
                                <th class="px-3 py-3 text-center text-sm font-medium text-gray-700 border">Xóa</th>
                            </tr>
                        </thead>
                        <tbody id="items-body" class="bg-white"></tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- BƯỚC 4: TÍNH TOÁN & THANH TOÁN -->
        <div class="bg-orange-50 border-l-4 border-orange-500 p-6 rounded-lg mb-6">
            <h3 class="text-xl font-bold text-orange-900 mb-4 flex items-center">
                <span class="bg-orange-500 text-white w-8 h-8 rounded-full flex items-center justify-center mr-3">4</span>
                Tính toán & Thanh toán
            </h3>
            
            <!-- Tỷ giá và Giảm giá -->
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tỷ giá (VND/USD) <span class="text-red-500">*</span></label>
                    <input type="text" name="exchange_rate" id="rate" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" value="{{ number_format(round($sale->exchange_rate)) }}" oninput="formatVND(this)" onblur="formatVND(this)" onchange="calc()">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Giảm giá (%)</label>
                    <input type="number" name="discount_percent" id="discount" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" value="{{ round($sale->discount_percent) }}" min="0" max="100" step="1" onchange="calc()">
                </div>
            </div>

            <!-- Tổng tiền -->
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-blue-900 mb-2">Tổng tiền USD</label>
                    <input type="text" id="total_usd" readonly class="w-full px-4 py-2 border-2 border-blue-300 rounded-lg bg-white font-bold text-blue-600" value="${{ number_format($sale->total_usd, 2) }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-green-900 mb-2">Tổng tiền VND</label>
                    @php
                        $hasReturns = $sale->returns()->where('status', 'completed')->where('type', 'return')->exists();
                        $hasExchanges = $sale->returns()->where('status', 'completed')->where('type', 'exchange')->exists();
                        
                        // Lấy original_total
                        if ($sale->original_total_vnd) {
                            $originalTotal = $sale->original_total_vnd;
                        } else {
                            $originalTotal = $sale->saleItems->sum('total_vnd');
                        }
                        
                        $showStrikethrough = ($hasReturns || $hasExchanges) && $originalTotal != $sale->total_vnd;
                    @endphp
                    
                    @if($showStrikethrough)
                        <!-- Có trả/đổi hàng - hiển thị giá gốc gạch ngang -->
                        <div class="w-full px-4 py-2 border-2 border-green-300 rounded-lg bg-white">
                            <div class="text-xs text-gray-400 line-through">{{ number_format($originalTotal, 0, ',', '.') }}đ</div>
                            <div class="font-bold text-orange-600">{{ number_format($sale->total_vnd, 0, ',', '.') }}đ</div>
                        </div>
                    @else
                        <!-- Không có trả/đổi hàng -->
                        <input type="text" id="total_vnd" readonly class="w-full px-4 py-2 border-2 border-green-300 rounded-lg bg-white font-bold text-green-600" value="{{ number_format($sale->total_vnd) }}đ">
                    @endif
                </div>
            </div>

            <!-- Thanh toán -->
            <div class="grid grid-cols-3 gap-4">
                <div>
                    @if($sale->sale_status === 'completed')
                        <!-- Phiếu đã duyệt - cho phép trả thêm -->
                        <label class="block text-sm font-medium text-gray-700 mb-2">Khách trả thêm (VND)</label>
                        <input type="text" name="payment_amount" id="paid" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" value="0" oninput="formatVND(this)" onblur="formatVND(this)" onchange="calcDebt()" placeholder="Nhập số tiền trả thêm...">
                        
                        <!-- Lịch sử thanh toán -->
                        @if($sale->payments->count() > 0)
                        <div class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="text-xs font-semibold text-gray-600 mb-2 flex items-center">
                                <i class="fas fa-history mr-1"></i> Lịch sử thanh toán
                            </div>
                            <div class="space-y-1 max-h-32 overflow-y-auto">
                                @foreach($sale->payments as $payment)
                                <div class="flex justify-between items-center text-xs py-1">
                                    <span class="text-gray-600">{{ $payment->payment_date->format('d/m/Y') }}</span>
                                    <span class="font-semibold {{ $payment->amount < 0 ? 'text-red-600' : 'text-green-600' }}">
                                        {{ $payment->amount < 0 ? '' : '+' }}{{ number_format($payment->amount) }}đ
                                    </span>
                                </div>
                                @endforeach
                            </div>
                            <div class="mt-2 pt-2 border-t border-gray-300 flex justify-between items-center">
                                <span class="text-xs font-semibold text-gray-700">Tổng đã trả:</span>
                                <span class="text-sm font-bold text-blue-600">{{ number_format($sale->paid_amount) }}đ</span>
                            </div>
                        </div>
                        @endif
                    @else
                        <!-- Phiếu pending - hiển thị số tiền đã trả (chưa tạo payment) -->
                        <label class="block text-sm font-medium text-gray-700 mb-2">Đã trả (VND)</label>
                        <input type="text" name="payment_amount" id="paid" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 bg-blue-50" value="{{ number_format($sale->paid_amount) }}" oninput="formatVND(this)" onblur="formatVND(this)" onchange="calcDebt()" placeholder="Nhập số tiền đã trả...">
                        
                        <div class="mt-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="text-xs text-blue-800 flex items-center">
                                <i class="fas fa-info-circle mr-2"></i>
                                <span>Số tiền này sẽ được ghi vào lịch sử thanh toán khi duyệt phiếu</span>
                            </div>
                        </div>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-yellow-900 mb-2">Nợ cũ</label>
                    <input type="text" id="current_debt" readonly class="w-full px-4 py-2 border border-yellow-300 rounded-lg bg-white font-bold text-orange-600" value="0đ">
                </div>
                <div>
                    <label class="block text-sm font-medium text-red-900 mb-2">Còn nợ</label>
                    <input type="text" id="debt" readonly class="w-full px-4 py-2 border border-red-300 rounded-lg bg-white font-bold text-red-600" value="{{ number_format($sale->debt_amount) }}đ">
                </div>
            </div>
        </div>

        <!-- Hidden field for payment method -->
        <input type="hidden" name="payment_method" value="cash">

        <!-- Ghi chú -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
            <textarea name="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Nhập ghi chú (không bắt buộc)...">{{ $sale->notes }}</textarea>
        </div>

        <!-- Buttons -->
        <div class="flex gap-4 pt-6 border-t-2 border-gray-200">
            <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg transition-colors font-medium shadow-lg">
                <i class="fas fa-save mr-2"></i>Cập nhật hóa đơn
            </button>
            <a href="{{ route('sales.show', $sale->id) }}" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 rounded-lg transition-colors font-medium text-center shadow-lg">
                <i class="fas fa-times mr-2"></i>Hủy bỏ
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script src="{{ asset('js/number-format.js') }}"></script>
<script>
let idx = 0;
const paintings = @json($paintings);
const supplies = @json($supplies);
const customers = @json($customers);
const saleItems = @json($sale->saleItems);

// Xử lý autocomplete khách hàng
function filterCustomers(query) {
    const suggestions = document.getElementById('customer-suggestions');
    
    if (!query) {
        suggestions.classList.add('hidden');
        return;
    }
    
    const filtered = customers.filter(c => 
        c.name.toLowerCase().includes(query.toLowerCase()) || 
        c.phone.includes(query)
    );
    
    if (filtered.length > 0) {
        suggestions.innerHTML = filtered.map(c => `
            <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b" onclick="selectCustomer(${c.id})">
                <div class="font-medium">${c.name}</div>
                <div class="text-xs text-gray-500">${c.phone}</div>
            </div>
        `).join('');
        suggestions.classList.remove('hidden');
    } else {
        suggestions.classList.add('hidden');
    }
}

function selectCustomer(id) {
    const customer = customers.find(c => c.id == id);
    if (customer) {
        document.getElementById('customer_id').value = customer.id;
        document.getElementById('customer_name').value = customer.name;
        document.getElementById('customer_phone').value = customer.phone || '';
        document.getElementById('customer_email').value = customer.email || '';
        document.getElementById('customer_address').value = customer.address || '';
        document.getElementById('customer-suggestions').classList.add('hidden');
        
        // Load công nợ hiện tại
        loadCurrentDebt(customer.id);
    }
}

// Ẩn suggestions khi click bên ngoài
document.addEventListener('click', function(e) {
    // Hide customer suggestions
    if (!e.target.closest('#customer_name') && !e.target.closest('#customer-suggestions')) {
        document.getElementById('customer-suggestions').classList.add('hidden');
    }
    
    // Hide painting suggestions for all rows
    document.querySelectorAll('[id^="painting-suggestions-"]').forEach(suggestion => {
        const idx = suggestion.id.replace('painting-suggestions-', '');
        if (!e.target.closest(`#painting-search-${idx}`) && !e.target.closest(`#painting-suggestions-${idx}`)) {
            suggestion.classList.add('hidden');
        }
    });
    
    // Hide supply suggestions for all rows
    document.querySelectorAll('[id^="supply-suggestions-"]').forEach(suggestion => {
        const idx = suggestion.id.replace('supply-suggestions-', '');
        if (!e.target.closest(`#supply-search-${idx}`) && !e.target.closest(`#supply-suggestions-${idx}`)) {
            suggestion.classList.add('hidden');
        }
    });
});

function addItem() {
    const tbody = document.getElementById('items-body');
    const tr = document.createElement('tr');
    tr.className = 'border hover:bg-purple-50';
    tr.innerHTML = `
        <td class="px-3 py-3 border">
            <img id="img-${idx}" src="https://via.placeholder.com/80x60?text=No+Image" class="w-20 h-16 object-cover rounded border shadow-sm">
        </td>
        <td class="px-3 py-3 border">
            <div class="relative">
                <input type="text" 
                       id="painting-search-${idx}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" 
                       placeholder="Tìm tranh..."
                       autocomplete="off"
                       onkeyup="filterPaintings(this.value, ${idx})"
                       onfocus="showPaintingSuggestions(${idx})">
                <input type="hidden" name="items[${idx}][painting_id]" id="painting-id-${idx}">
                <input type="hidden" name="items[${idx}][description]" id="desc-${idx}">
                <div id="painting-suggestions-${idx}" class="absolute z-20 w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-40 overflow-y-auto hidden shadow-lg"></div>
            </div>
        </td>
        <td class="px-3 py-3 border">
            <div class="relative">
                <input type="text" 
                       id="supply-search-${idx}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" 
                       placeholder="Tìm vật tư..."
                       autocomplete="off"
                       onkeyup="filterSupplies(this.value, ${idx})"
                       onfocus="showSupplySuggestions(${idx})">
                <input type="hidden" name="items[${idx}][supply_id]" id="supply-id-${idx}">
                <div id="supply-suggestions-${idx}" class="absolute z-20 w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-40 overflow-y-auto hidden shadow-lg"></div>
            </div>
        </td>
        <td class="px-3 py-3 border">
            <input type="number" name="items[${idx}][supply_length]" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-center" value="0" step="1">
        </td>
        <td class="px-3 py-3 border">
            <input type="number" name="items[${idx}][quantity]" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-center font-medium" value="1" min="1" onchange="calc()">
        </td>
       <td class="px-3 py-3 border">
            <select name="items[${idx}][currency]" class="w-full px-3 py-2 border border-gray-300 rounded-lg" onchange="togCur(this, ${idx})">
                <option value="USD">USD</option>
                <option value="VND">VND</option>
                <option value="BOTH" selected>Cả 2</option>
            </select>
        </td>
        <td class="px-3 py-3 border">
            <input type="text" name="items[${idx}][price_usd]" id="usd-input-${idx}" class="usd-${idx} w-full px-3 py-2 border border-gray-300 rounded-lg text-right" value="0.00" oninput="formatUSD(this)" onblur="formatUSD(this)" onchange="calc()">
        </td>
        <td class="px-3 py-3 border">
            <input type="text" name="items[${idx}][price_vnd]" id="vnd-input-${idx}" class="vnd-${idx} w-full px-3 py-2 border border-gray-300 rounded-lg text-right" value="0" oninput="formatVND(this)" onblur="formatVND(this)" onchange="calc()">
        </td>
        <td class="px-3 py-3 border text-center">
            <input type="number" name="items[${idx}][discount_percent]" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-center" value="0" min="0" max="100" step="1" onchange="calc()">
        </td>
        <td class="px-3 py-3 border text-center">
            <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors" onclick="this.closest('tr').remove();calc()">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    idx++;
}

// Search functions for paintings
    function filterPaintings(query, idx) {
        const suggestions = document.getElementById(`painting-suggestions-${idx}`);
        
        if (!query || query.length < 1) {
            suggestions.classList.add('hidden');
            return;
        }
    
    fetch(`{{ route('sales.api.search.paintings') }}?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(paintings => {
            if (paintings.length > 0) {
                suggestions.innerHTML = paintings.map(p => `
                    <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b" onclick="selectPainting(${p.id}, ${idx})">
                        <div class="font-medium text-sm">${p.code} - ${p.name}</div>
                        <div class="text-xs text-gray-500">USD: $${p.price_usd || 0} | VND: ${(p.price_vnd || 0).toLocaleString()}đ</div>
                    </div>
                `).join('');
                suggestions.classList.remove('hidden');
            } else {
                suggestions.classList.add('hidden');
            }
        })
        .catch(error => {
            console.error('Error searching paintings:', error);
            suggestions.classList.add('hidden');
        });
}

    function showPaintingSuggestions(idx) {
        const input = document.getElementById(`painting-search-${idx}`);
        const suggestions = document.getElementById(`painting-suggestions-${idx}`);
        
        if (input && input.value.length >= 1) {
            filterPaintings(input.value, idx);
        }
    }

function selectPainting(paintingId, idx) {
    fetch(`{{ route('sales.api.painting', '') }}/${paintingId}`)
        .then(response => response.json())
        .then(painting => {
            document.getElementById(`painting-id-${idx}`).value = painting.id;
            document.getElementById(`painting-search-${idx}`).value = `${painting.code} - ${painting.name}`;
            document.getElementById(`desc-${idx}`).value = painting.name;
            
            const usdInput = document.querySelector(`.usd-${idx}`);
            const vndInput = document.querySelector(`.vnd-${idx}`);
            
            if (usdInput) {
                const usdValue = parseFloat(painting.price_usd) || 0;
                usdInput.value = usdValue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
            if (vndInput) {
                const vndValue = parseInt(painting.price_vnd) || 0;
                vndInput.value = vndValue.toLocaleString('en-US');
            }
            
            const imgUrl = painting.image ? `/storage/${painting.image}` : 'https://via.placeholder.com/80x60?text=No+Image';
            document.getElementById(`img-${idx}`).src = imgUrl;
            
            document.getElementById(`painting-suggestions-${idx}`).classList.add('hidden');
            calc();
        })
        .catch(error => {
            console.error('Error fetching painting:', error);
        });
}

// Search functions for supplies
function filterSupplies(query, idx) {
    const suggestions = document.getElementById(`supply-suggestions-${idx}`);
    
    if (!query || query.length < 1) {
        suggestions.classList.add('hidden');
        return;
    }
    
    fetch(`{{ route('sales.api.search.supplies') }}?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(supplies => {
            if (supplies.length > 0) {
                suggestions.innerHTML = supplies.map(s => `
                    <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b" onclick="selectSupply(${s.id}, ${idx})">
                        <div class="font-medium text-sm">${s.name}</div>
                        <div class="text-xs text-gray-500">Đơn vị: ${s.unit || 'N/A'}</div>
                    </div>
                `).join('');
                suggestions.classList.remove('hidden');
            } else {
                suggestions.classList.add('hidden');
            }
        })
        .catch(error => {
            console.error('Error searching supplies:', error);
            suggestions.classList.add('hidden');
        });
}

function showSupplySuggestions(idx) {
    const input = document.getElementById(`supply-search-${idx}`);
    const suggestions = document.getElementById(`supply-suggestions-${idx}`);
    
    if (input.value.length >= 1) {
        filterSupplies(input.value, idx);
    }
}

function selectSupply(supplyId, idx) {
    fetch(`{{ route('sales.api.supply', '') }}/${supplyId}`)
        .then(response => response.json())
        .then(supply => {
            document.getElementById(`supply-id-${idx}`).value = supply.id;
            document.getElementById(`supply-search-${idx}`).value = supply.name;
            
            document.getElementById(`supply-suggestions-${idx}`).classList.add('hidden');
        })
        .catch(error => {
            console.error('Error fetching supply:', error);
        });
}

function togCur(sel, i) {
    const cur = sel.value;
    const usdInput = document.getElementById(`usd-input-${i}`);
    const vndInput = document.getElementById(`vnd-input-${i}`);
    
    if (!usdInput || !vndInput) {
        console.error('Inputs not found!');
        return;
    }
    
    if (cur === 'USD') {
        usdInput.classList.remove('hidden');
        vndInput.classList.add('hidden');
    } else if (cur === 'VND') {
        usdInput.classList.add('hidden');
        vndInput.classList.remove('hidden');
    } else { // BOTH
        usdInput.classList.remove('hidden');
        vndInput.classList.remove('hidden');
    }
    
    calc();
}

function calc() {
    const rateVal = unformatNumber(document.getElementById('rate').value);
    const rate = parseFloat(rateVal) || 25000;
    const disc = parseFloat(document.getElementById('discount').value) || 0;
    const rows = document.querySelectorAll('#items-body tr');
    
    let totUsd = 0;
    let totVnd = 0;
    
    rows.forEach((row, i) => {
        const qty = parseFloat(row.querySelector('[name*="[quantity]"]')?.value || 0);
        const cur = row.querySelector('[name*="[currency]"]')?.value || 'USD';
        const itemDiscountPercent = parseFloat(row.querySelector('[name*="[discount_percent]"]')?.value || 0);
        
        if (cur === 'USD') {
            const usdVal = unformatNumber(row.querySelector('[name*="[price_usd]"]')?.value || '0');
            const usd = parseFloat(usdVal);
            const subtotal = usd * qty;
            const itemDiscountAmt = subtotal * (itemDiscountPercent / 100);
            const itemTotalUsd = subtotal - itemDiscountAmt;
            const itemTotalVnd = itemTotalUsd * rate;
            totUsd += itemTotalUsd;
            totVnd += itemTotalVnd;
        } else if (cur === 'VND') {
            const vndVal = unformatNumber(row.querySelector('[name*="[price_vnd]"]')?.value || '0');
            const vnd = parseFloat(vndVal);
            const subtotal = vnd * qty;
            const itemDiscountAmt = subtotal * (itemDiscountPercent / 100);
            const itemTotalVnd = subtotal - itemDiscountAmt;
            const itemTotalUsd = itemTotalVnd / rate;
            totVnd += itemTotalVnd;
            totUsd += itemTotalUsd;
        } else { // BOTH
            const usdVal = unformatNumber(row.querySelector('[name*="[price_usd]"]')?.value || '0');
            const vndVal = unformatNumber(row.querySelector('[name*="[price_vnd]"]')?.value || '0');
            const usd = parseFloat(usdVal);
            const vnd = parseFloat(vndVal);
            const subtotalUsd = usd * qty;
            const subtotalVnd = vnd * qty;
            const itemDiscountAmtUsd = subtotalUsd * (itemDiscountPercent / 100);
            const itemDiscountAmtVnd = subtotalVnd * (itemDiscountPercent / 100);
            totUsd += subtotalUsd - itemDiscountAmtUsd;
            totVnd += subtotalVnd - itemDiscountAmtVnd;
        }
    });
    
    const discAmtUsd = totUsd * (disc / 100);
    const discAmtVnd = totVnd * (disc / 100);
    const finalUsd = totUsd - discAmtUsd;
    const finalVnd = totVnd - discAmtVnd;
    
    document.getElementById('total_usd').value = '$' + finalUsd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('total_vnd').value = finalVnd.toLocaleString('vi-VN') + 'đ';
    
    calcDebt();
}

function calcDebt() {
    const totTxt = document.getElementById('total_vnd').value.replace(/[^\d]/g, '');
    const tot = parseFloat(totTxt) || 0;
    
    // Tổng đã trả (từ database) + số tiền trả thêm (từ input)
    const currentPaid = {{ $sale->paid_amount }};
    const paidVal = unformatNumber(document.getElementById('paid').value);
    const additionalPaid = parseFloat(paidVal) || 0;
    const totalPaid = currentPaid + additionalPaid;
    
    const debt = Math.max(0, tot - totalPaid);
    
    document.getElementById('debt').value = debt.toLocaleString('vi-VN') + 'đ';
}

// Load công nợ hiện tại khi chọn khách hàng
function loadCurrentDebt(customerId) {
    if (customerId) {
        fetch(`/sales/api/customers/${customerId}/debt`)
            .then(response => response.json())
            .then(data => {
                const currentDebt = data.total_debt || 0;
                const formattedDebt = Math.round(currentDebt).toLocaleString('en-US').replace(/,/g, '.');
                document.getElementById('current_debt').value = formattedDebt + 'đ';
            })
            .catch(error => {
                console.error('Error loading customer debt:', error);
                document.getElementById('current_debt').value = '0đ';
            });
    } else {
        document.getElementById('current_debt').value = '0đ';
    }
}

// Generate invoice code
function generateInvoiceCode() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const time = String(now.getHours()).padStart(2, '0') + String(now.getMinutes()).padStart(2, '0');
    
    const invoiceCode = `HD${year}${month}${day}${time}`;
    document.getElementById('invoice_code').value = invoiceCode;
}

// Load existing sale items
function loadExistingItems() {
    let displayIndex = 0;
    saleItems.forEach((item, originalIndex) => {
        // Bỏ qua sản phẩm đã trả
        if (item.is_returned) {
            return; // Skip this item
        }
        
        const index = displayIndex;
        addItem();
        
        // Set painting
        if (item.painting_id) {
            document.getElementById(`painting-id-${index}`).value = item.painting_id;
            document.getElementById(`painting-search-${index}`).value = `${item.painting?.code || ''} - ${item.description}`;
            document.getElementById(`desc-${index}`).value = item.description;
            document.querySelector(`.usd-${index}`).value = item.price_usd || 0;
            document.querySelector(`.vnd-${index}`).value = item.price_vnd || 0;
            
            if (item.painting?.image) {
                document.getElementById(`img-${index}`).src = `/storage/${item.painting.image}`;
            }
        } else {
            document.getElementById(`desc-${index}`).value = item.description;
        }
        
        // Set supply
        if (item.supply_id) {
            document.getElementById(`supply-id-${index}`).value = item.supply_id;
            document.getElementById(`supply-search-${index}`).value = item.supply?.name || '';
        }
        
        // Set other fields
        document.querySelector(`[name="items[${index}][supply_length]"]`).value = Math.round(item.supply_length || 0);
        document.querySelector(`[name="items[${index}][quantity]"]`).value = item.quantity;
        document.querySelector(`[name="items[${index}][currency]"]`).value = item.currency;
        
        // Format prices
        const usdInput = document.querySelector(`[name="items[${index}][price_usd]"]`);
        const vndInput = document.querySelector(`[name="items[${index}][price_vnd]"]`);
        if (usdInput && vndInput) {
            const usdVal = parseFloat(item.price_usd) || 0;
            const vndVal = parseInt(item.price_vnd) || 0;
            usdInput.value = usdVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            vndInput.value = vndVal.toLocaleString('en-US');
        }
        
        document.querySelector(`[name="items[${index}][discount_percent]"]`).value = Math.round(item.discount_percent || 0);
        
        // Set currency display
        togCur(document.querySelector(`[name="items[${index}][currency]"]`), index);
        
        displayIndex++; // Tăng index cho item tiếp theo
    });
}

// Before form submit, remove formatting
document.getElementById('sales-form').addEventListener('submit', function(e) {
    document.getElementById('rate').value = unformatNumber(document.getElementById('rate').value);
    document.getElementById('paid').value = unformatNumber(document.getElementById('paid').value);
    
    document.querySelectorAll('[name*="[price_usd]"]').forEach(input => {
        input.value = unformatNumber(input.value);
    });
    document.querySelectorAll('[name*="[price_vnd]"]').forEach(input => {
        input.value = unformatNumber(input.value);
    });
    
    // Convert BOTH to USD or VND
    document.querySelectorAll('[name*="[currency]"]').forEach(select => {
        if (select.value === 'BOTH') {
            const row = select.closest('tr');
            const usdVal = parseFloat(unformatNumber(row.querySelector('[name*="[price_usd]"]').value)) || 0;
            const vndVal = parseFloat(unformatNumber(row.querySelector('[name*="[price_vnd]"]').value)) || 0;
            select.value = (usdVal > 0) ? 'USD' : 'VND';
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    loadExistingItems();
    calc();
    calcDebt(); // Tính còn nợ ngay khi load trang
    
    // Load công nợ hiện tại của khách hàng
    const customerId = document.getElementById('customer_id').value;
    if (customerId) {
        loadCurrentDebt(customerId);
    }
});
</script>

@endpush
@endsection