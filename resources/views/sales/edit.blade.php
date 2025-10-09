@extends('layouts.app')

@section('title', 'Sửa hóa đơn bán hàng')
@section('page-title', 'Sửa hóa đơn bán hàng')
@section('page-description', 'Chỉnh sửa hóa đơn bán hàng')

@section('content')
<x-alert />

<div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
    <form action="{{ route('sales.update', $sale->id) }}" method="POST" id="sales-form">
        @csrf
        @method('PUT')
         <!-- Hàng 4: Số hóa đơn -->
         <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 text-base">Số hóa đơn</label>
                <div class="flex gap-2">
                    <input type="text" 
                           name="invoice_code" 
                           id="invoice_code" 
                           class="px-4 py-3 border-2 rounded-lg font-bold text-indigo-700 text-lg" 
                           placeholder="Nhập số hóa đơn hoặc để trống để tự động tạo"
                           value="{{ $sale->invoice_code }}">
                    <button type="button" 
                            onclick="generateInvoiceCode()" 
                            class="bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors"
                            title="Tự động tạo số hóa đơn">
                        <i class="fas fa-magic"></i>
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">Để trống để tự động tạo, hoặc nhập số hóa đơn tùy chỉnh</p>
            </div>
        </div>
        <div class="mb-6">
            <h4 class="font-medium mb-4">Thông tin khách hàng</h4>
            <div class="grid grid-cols-3 gap-3 mb-3">
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tên khách hàng <span class="text-red-500">*</span></label>
                    <input type="text" 
                           name="customer_name" 
                           id="customer_name" 
                           required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                           placeholder="Nhập tên khách hàng..."
                           value="{{ $sale->customer->name }}"
                           autocomplete="off"
                           onkeyup="filterCustomers(this.value)">
                    <input type="hidden" name="customer_id" id="customer_id" value="{{ $sale->customer_id }}">
                    <div id="customer-suggestions" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-y-auto hidden shadow-lg"></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Số điện thoại <span class="text-red-500">*</span></label>
                    <input type="tel" name="customer_phone" id="customer_phone" required class="w-full px-3 py-2 border border-gray-300 rounded-lg" value="{{ $sale->customer->phone }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="customer_email" id="customer_email" class="w-full px-3 py-2 border border-gray-300 rounded-lg" value="{{ $sale->customer->email }}">
                </div>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Địa chỉ</label>
                    <input type="text" name="customer_address" id="customer_address" class="w-full px-3 py-2 border border-gray-300 rounded-lg" value="{{ $sale->customer->address }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Showroom <span class="text-red-500">*</span></label>
                    <select name="showroom_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">Chọn...</option>
                        @foreach($showrooms as $showroom)
                            <option value="{{ $showroom->id }}" {{ $sale->showroom_id == $showroom->id ? 'selected' : '' }}>{{ $showroom->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ngày bán <span class="text-red-500">*</span></label>
                    <input type="date" name="sale_date" required class="w-full px-3 py-2 border rounded-lg" value="{{ $sale->sale_date->format('Y-m-d') }}">
                </div>
            </div>
        </div>

        <div class="mb-6 border rounded-lg">
            <div class="px-4 py-3 bg-gray-50 flex justify-between items-center">
                <h4 class="font-semibold">Danh sách sản phẩm</h4>
                <button type="button" onclick="addItem()" class="bg-blue-600 text-white py-1 px-3 rounded hover:bg-blue-700">
                    <i class="fas fa-plus mr-1"></i>Thêm
                </button>
            </div>
            <div class="#">
                <table class="w-full">
                    <thead class="bg-gray-50 text-xs">
                        <tr>
                            <th class="px-2 py-2 text-left">Hình ảnh</th>
                            <th class="px-2 py-2 text-left">Mô tả (Mã tranh/Khung)</th>
                            <th class="px-2 py-2 text-left">Vật tư (khung)</th>
                            <th class="px-2 py-2 text-left">Số mét/1 cây</th>
                            <th class="px-2 py-2 text-left">Số lượng</th>
                            <th class="px-2 py-2 text-left">Loại tiền</th>
                            <th class="px-2 py-2 text-left">Giá bán USD</th>
                            <th class="px-2 py-2 text-left">Giá bán VND</th>
                            <th class="px-2 py-2 text-left">Giảm giá</th>
                            <th class="px-2 py-2 text-left">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="items-body"></tbody>
                </table>
            </div>
        </div>

        <!-- Hàng 1: Tỷ giá và Giảm giá -->
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 text-base">Tỷ giá <span class="text-red-500">*</span></label>
                <input type="number" name="exchange_rate" id="rate" required class="w-full px-4 py-3 border-2 rounded-lg text-lg" value="{{ $sale->exchange_rate }}" onchange="calc()">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 text-base">Giảm giá (%)</label>
                <input type="number" name="discount_percent" id="discount" class="w-full px-4 py-3 border-2 rounded-lg text-lg" value="{{ $sale->discount_percent }}" min="0" max="100" onchange="calc()">
            </div>
        </div>

        <!-- Hàng 2: Tổng tiền -->
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 text-base">Tổng USD</label>
                <input type="text" id="total_usd" readonly class="w-full px-4 py-3 border-2 rounded-lg bg-blue-50 font-bold text-blue-600 text-xl" value="${{ number_format($sale->total_usd, 2) }}">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 text-base">Tổng VND</label>
                <input type="text" id="total_vnd" readonly class="w-full px-4 py-3 border-2 rounded-lg bg-green-50 font-bold text-green-600 text-xl" value="{{ number_format($sale->total_vnd) }}đ">
            </div>
        </div>

        <!-- Hàng 3: Thanh toán -->
        <div class="grid grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 text-base">Số tiền trả</label>
                <input type="number" name="payment_amount" id="paid" class="w-full px-4 py-3 border-2 rounded-lg text-lg" value="{{ $sale->paid_amount }}" onchange="calcDebt()">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 text-base">Công nợ hiện tại</label>
                <input type="text" id="current_debt" readonly class="w-full px-4 py-3 border-2 rounded-lg bg-yellow-50 font-bold text-orange-600 text-xl" value="0đ">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 text-base">Còn nợ</label>
                <input type="text" id="debt" readonly class="w-full px-4 py-3 border-2 rounded-lg bg-red-50 font-bold text-red-600 text-xl" value="{{ number_format($sale->debt_amount) }}đ">
            </div>
        </div>

       
        
        <!-- Hidden field for payment method -->
        <input type="hidden" name="payment_method" value="cash">

        <!-- Ghi chú riêng -->
        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-2 text-base">Ghi chú</label>
            <textarea name="notes" rows="2" class="w-full px-4 py-3 border-2 rounded-lg text-lg" placeholder="Nhập ghi chú (nếu có)...">{{ $sale->notes }}</textarea>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="flex-1 bg-green-600 text-white py-2 rounded-lg hover:bg-green-700">
                <i class="fas fa-save mr-2"></i>Cập nhật hóa đơn
            </button>
            <a href="{{ route('sales.show', $sale->id) }}" class="flex-1 bg-gray-600 text-white py-2 rounded-lg hover:bg-gray-700 text-center">
                <i class="fas fa-times mr-2"></i>Hủy
            </a>
        </div>
    </form>
</div>

@push('scripts')
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
    tr.className = 'border-b text-sm';
    tr.innerHTML = `
        <td class="px-2 py-2">
            <img id="img-${idx}" src="https://via.placeholder.com/80x60?text=No+Image" class="w-20 h-16 object-cover rounded border">
        </td>
        <td class="px-2 py-2">
            <div class="relative">
                <input type="text" 
                       id="painting-search-${idx}"
                       class="w-full px-2 py-1 border rounded text-xs" 
                       placeholder="Tìm kiếm tranh..."
                       autocomplete="off"
                       onkeyup="filterPaintings(this.value, ${idx})"
                       onfocus="showPaintingSuggestions(${idx})">
                <input type="hidden" name="items[${idx}][painting_id]" id="painting-id-${idx}">
                <input type="hidden" name="items[${idx}][description]" id="desc-${idx}">
                <div id="painting-suggestions-${idx}" class="absolute z-20 w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-40 overflow-y-auto hidden shadow-lg"></div>
            </div>
        </td>
        <td class="px-2 py-2">
            <div class="relative">
                <input type="text" 
                       id="supply-search-${idx}"
                       class="w-full px-2 py-1 border rounded text-xs" 
                       placeholder="Tìm kiếm vật tư..."
                       autocomplete="off"
                       onkeyup="filterSupplies(this.value, ${idx})"
                       onfocus="showSupplySuggestions(${idx})">
                <input type="hidden" name="items[${idx}][supply_id]" id="supply-id-${idx}">
                <div id="supply-suggestions-${idx}" class="absolute z-20 w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-40 overflow-y-auto hidden shadow-lg"></div>
            </div>
        </td>
        <td class="px-2 py-2">
            <input type="number" name="items[${idx}][supply_length]" class="w-full px-2 py-1 border rounded text-xs" value="0" step="0.01">
        </td>
        <td class="px-2 py-2">
            <input type="number" name="items[${idx}][quantity]" required class="w-full px-2 py-1 border rounded text-xs" value="1" min="1" onchange="calc()">
        </td>
        <td class="px-2 py-2">
            <select name="items[${idx}][currency]" class="w-full px-2 py-1 border rounded text-xs" onchange="togCur(this, ${idx})">
                <option value="USD">USD</option>
                <option value="VND">VND</option>
                <option value="BOTH">Tất cả</option>
            </select>
        </td>
        <td class="px-2 py-2">
            <input type="number" name="items[${idx}][price_usd]" id="usd-input-${idx}" class="usd-${idx} w-full px-2 py-1 border rounded text-xs" value="0" step="0.01" onchange="calc()">
        </td>
        <td class="px-2 py-2">
            <input type="number" name="items[${idx}][price_vnd]" id="vnd-input-${idx}" class="vnd-${idx} w-full px-2 py-1 border rounded text-xs hidden" value="0" step="1000" onchange="calc()">
        </td>
        <td class="px-2 py-2">
            <input type="number" name="items[${idx}][discount]" class="w-full px-2 py-1 border rounded text-xs" value="0" min="0">
        </td>
        <td class="px-2 py-2">
            <button type="button" class="text-red-600" onclick="this.closest('tr').remove();calc()">
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
        
        if (!query || query.length < 2) {
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
        
        if (input && input.value.length >= 2) {
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
            document.querySelector(`.usd-${idx}`).value = painting.price_usd || 0;
            document.querySelector(`.vnd-${idx}`).value = painting.price_vnd || 0;
            
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
    
    if (!query || query.length < 2) {
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
    
    if (input.value.length >= 2) {
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
        vndInput.value = 0;
    } else if (cur === 'VND') {
        usdInput.classList.add('hidden');
        vndInput.classList.remove('hidden');
        usdInput.value = 0;
    } else { // BOTH
        usdInput.classList.remove('hidden');
        vndInput.classList.remove('hidden');
    }
    
    calc();
}

function calc() {
    const rate = parseFloat(document.getElementById('rate').value) || 25000;
    const disc = parseFloat(document.getElementById('discount').value) || 0;
    const rows = document.querySelectorAll('#items-body tr');
    
    let totUsd = 0;
    let totVnd = 0;
    
    rows.forEach((row, i) => {
        const qty = parseFloat(row.querySelector('[name*="[quantity]"]')?.value || 0);
        const cur = row.querySelector('[name*="[currency]"]')?.value || 'USD';
        
        if (cur === 'USD') {
            const usd = parseFloat(row.querySelector('[name*="[price_usd]"]')?.value || 0);
            totUsd += usd * qty;
            totVnd += usd * qty * rate;
        } else if (cur === 'VND') {
            const vnd = parseFloat(row.querySelector('[name*="[price_vnd]"]')?.value || 0);
            totVnd += vnd * qty;
            totUsd += (vnd * qty) / rate;
        } else { // BOTH
            const usd = parseFloat(row.querySelector('[name*="[price_usd]"]')?.value || 0);
            const vnd = parseFloat(row.querySelector('[name*="[price_vnd]"]')?.value || 0);
            totUsd += usd * qty;
            totVnd += vnd * qty;
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
    const paid = parseFloat(document.getElementById('paid').value) || 0;
    const debt = Math.max(0, tot - paid);
    
    document.getElementById('debt').value = debt.toLocaleString('vi-VN') + 'đ';
}

// Load công nợ hiện tại khi chọn khách hàng
function loadCurrentDebt(customerId) {
    if (customerId) {
        // TODO: Gọi API để lấy công nợ hiện tại
        // Tạm thời set 0
        document.getElementById('current_debt').value = '0đ';
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
    saleItems.forEach((item, index) => {
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
        document.querySelector(`[name="items[${index}][supply_length]"]`).value = item.supply_length || 0;
        document.querySelector(`[name="items[${index}][quantity]"]`).value = item.quantity;
        document.querySelector(`[name="items[${index}][currency]"]`).value = item.currency;
        document.querySelector(`[name="items[${index}][price_usd]"]`).value = item.price_usd || 0;
        document.querySelector(`[name="items[${index}][price_vnd]"]`).value = item.price_vnd || 0;
        
        // Set currency display
        togCur(document.querySelector(`[name="items[${index}][currency]"]`), index);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadExistingItems();
    calc();
});
</script>

@endpush
@endsection