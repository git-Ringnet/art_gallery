@extends('layouts.app')

@section('title', 'Tạo hóa đơn bán hàng')
@section('page-title', 'Tạo hóa đơn bán hàng')
@section('page-description', 'Tạo hóa đơn bán hàng mới')

@section('content')
<x-alert />

<div class="bg-white rounded-xl shadow-lg p-8 glass-effect">
    <form action="{{ route('sales.store') }}" method="POST" id="sales-form">
        @csrf
        
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
                               placeholder="Tự động tạo...">
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
                            <option value="{{ $showroom->id }}">{{ $showroom->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ngày bán <span class="text-red-500">*</span></label>
                    <input type="date" name="sale_date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" value="{{ date('Y-m-d') }}">
                </div>
            </div>
        </div>

        <!-- BƯỚC 2: THÔNG TIN KHÁCH HÀNG -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-6">
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
                           placeholder="Nhập tên khách hàng..."
                           autocomplete="off"
                           onkeyup="filterCustomers(this.value)">
                    <input type="hidden" name="customer_id" id="customer_id">
                    <div id="customer-suggestions" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-y-auto hidden shadow-lg"></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Số điện thoại <span class="text-red-500">*</span></label>
                    <input type="tel" name="customer_phone" id="customer_phone" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" placeholder="Nhập số điện thoại...">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="customer_email" id="customer_email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" placeholder="Nhập email (không bắt buộc)">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Địa chỉ</label>
                    <input type="text" name="customer_address" id="customer_address" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" placeholder="Nhập địa chỉ (không bắt buộc)">
                </div>
            </div>
        </div>

        <!-- BƯỚC 3: DANH SÁCH SẢN PHẨM -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-6">
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

        <!-- BƯỚC 4: TÍNH TOÁN & THANH TOÁN -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-6">
            <h3 class="text-xl font-bold text-orange-900 mb-4 flex items-center">
                <span class="bg-orange-500 text-white w-8 h-8 rounded-full flex items-center justify-center mr-3">4</span>
                Tính toán & Thanh toán
            </h3>
            
            <!-- Tỷ giá và Giảm giá -->
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tỷ giá (VND/USD) <span class="text-red-500">*</span></label>
                    <input type="number" name="exchange_rate" id="rate" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" value="{{ round($currentRate->rate ?? 25000) }}" step="1" onchange="calc()">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Giảm giá (%)</label>
                    <input type="number" name="discount_percent" id="discount" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" value="0" min="0" max="100" step="1" onchange="calc()">
                </div>
            </div>

            <!-- Tổng tiền -->
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-blue-900 mb-2">Tổng tiền USD</label>
                    <input type="text" id="total_usd" readonly class="w-full px-4 py-2 border-2 border-blue-300 rounded-lg bg-white font-bold text-blue-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-green-900 mb-2">Tổng tiền VND</label>
                    <input type="text" id="total_vnd" readonly class="w-full px-4 py-2 border-2 border-green-300 rounded-lg bg-white font-bold text-green-600">
                </div>
            </div>

            <!-- Thanh toán -->
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Khách trả (VND)</label>
                    <input type="number" name="payment_amount" id="paid" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" value="0" step="1000" onchange="calcDebt()" placeholder="Nhập số tiền...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-yellow-900 mb-2">Công nợ hiện tại</label>
                    <input type="text" id="current_debt" readonly class="w-full px-4 py-2 border border-yellow-300 rounded-lg bg-white font-bold text-orange-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-red-900 mb-2">Còn nợ</label>
                    <input type="text" id="debt" readonly class="w-full px-4 py-2 border border-red-300 rounded-lg bg-white font-bold text-red-600">
                </div>
            </div>
        </div>

        <!-- Hidden field for payment method -->
        <input type="hidden" name="payment_method" value="cash">

        <!-- Ghi chú -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
            <textarea name="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Nhập ghi chú (không bắt buộc)..."></textarea>
        </div>

        <!-- Buttons -->
        <div class="flex gap-4 pt-6 border-t-2 border-gray-200">
            <button type="submit" name="action" value="save" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg transition-colors font-medium shadow-lg">
                <i class="fas fa-save mr-2"></i>Lưu hóa đơn
            </button>
            <button type="submit" name="action" value="save_and_print" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg transition-colors font-medium shadow-lg">
                <i class="fas fa-print mr-2"></i>Lưu & In
            </button>
            <a href="{{ route('sales.index') }}" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 rounded-lg transition-colors font-medium text-center shadow-lg">
                <i class="fas fa-times mr-2"></i>Hủy bỏ
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
                <option value="BOTH">Cả 2</option>
            </select>
        </td>
        <td class="px-3 py-3 border">
            <input type="number" name="items[${idx}][price_usd]" id="usd-input-${idx}" class="usd-${idx} w-full px-3 py-2 border border-gray-300 rounded-lg text-right" value="0" step="0.01" onchange="calc()">
        </td>
        <td class="px-3 py-3 border">
            <input type="number" name="items[${idx}][price_vnd]" id="vnd-input-${idx}" class="vnd-${idx} w-full px-3 py-2 border border-gray-300 rounded-lg text-right hidden" value="0" step="1000" onchange="calc()">
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
            document.querySelector(`.usd-${idx}`).value = painting.price_usd || 0;
            document.querySelector(`.vnd-${idx}`).value = painting.price_vnd || 0;
            
            const imgUrl = painting.image ? `/storage/${painting.image}` : 'https://via.placeholder.com/80x60?text=No+Image';
            const imgElement = document.getElementById(`img-${idx}`);
            imgElement.src = imgUrl;
            imgElement.onclick = () => showImageModal(imgUrl, painting.name);
            imgElement.classList.add('cursor-pointer', 'hover:opacity-80', 'transition-opacity');
            
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
        const itemDiscountPercent = parseFloat(row.querySelector('[name*="[discount_percent]"]')?.value || 0);
        
        if (cur === 'USD') {
            const usd = parseFloat(row.querySelector('[name*="[price_usd]"]')?.value || 0);
            const subtotal = usd * qty;
            const itemDiscountAmt = subtotal * (itemDiscountPercent / 100);
            const itemTotalUsd = subtotal - itemDiscountAmt;
            const itemTotalVnd = itemTotalUsd * rate;
            totUsd += itemTotalUsd;
            totVnd += itemTotalVnd;
        } else if (cur === 'VND') {
            const vnd = parseFloat(row.querySelector('[name*="[price_vnd]"]')?.value || 0);
            const subtotal = vnd * qty;
            const itemDiscountAmt = subtotal * (itemDiscountPercent / 100);
            const itemTotalVnd = subtotal - itemDiscountAmt;
            const itemTotalUsd = itemTotalVnd / rate;
            totVnd += itemTotalVnd;
            totUsd += itemTotalUsd;
        } else { // BOTH
            const usd = parseFloat(row.querySelector('[name*="[price_usd]"]')?.value || 0);
            const vnd = parseFloat(row.querySelector('[name*="[price_vnd]"]')?.value || 0);
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

document.addEventListener('DOMContentLoaded', () => addItem());

// Image modal functions
function showImageModal(imageSrc, imageTitle) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const modalTitle = document.getElementById('modalImageTitle');
    
    modalImage.src = imageSrc;
    modalTitle.textContent = imageTitle;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal with ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeImageModal();
    }
});
</script>


@endpush

<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center p-4" onclick="closeImageModal()">
    <div class="relative max-w-4xl max-h-full" onclick="event.stopPropagation()">
        <button onclick="closeImageModal()" class="absolute -top-10 right-0 text-white hover:text-gray-300">
            <i class="fas fa-times text-2xl"></i>
        </button>
        <img id="modalImage" src="" alt="" class="max-w-full max-h-[90vh] object-contain rounded-lg">
        <p id="modalImageTitle" class="text-white text-center mt-4 text-lg"></p>
    </div>
</div>

@endsection
