@extends('layouts.app')

@section('title', 'Tạo hóa đơn bán hàng')
@section('page-title', 'Tạo hóa đơn bán hàng')
@section('page-description', 'Tạo hóa đơn bán hàng mới')

@section('content')
<x-alert />

<div class="bg-white rounded-xl shadow-lg p-4 glass-effect">
    <form action="{{ route('sales.store') }}" method="POST" id="sales-form">
        @csrf
        
        <!-- BƯỚC 1: THÔNG TIN CƠ BẢN -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg mb-4">
            <h3 class="text-base font-bold text-blue-900 mb-3 flex items-center">
                <span class="bg-blue-500 text-white w-7 h-7 rounded-full flex items-center justify-center mr-2 text-sm">1</span>
                Thông tin hóa đơn
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Số hóa đơn</label>
                    <div class="flex gap-2">
                        <input type="text" 
                               name="invoice_code" 
                               id="invoice_code" 
                               class="flex-1 px-3 py-1.5 text-sm border border-gray-300 rounded-lg font-medium text-blue-600" 
                               placeholder="Tự động tạo...">
                        <button type="button" 
                                onclick="generateInvoiceCode()" 
                                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-lg transition-colors"
                                title="Tự động tạo">
                            <i class="fas fa-magic text-sm"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Showroom <span class="text-red-500">*</span></label>
                    <select name="showroom_id" id="showroom_id" required class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Chọn showroom --</option>
                        @foreach($showrooms as $showroom)
                            <option value="{{ $showroom->id }}" data-code="{{ $showroom->code }}">{{ $showroom->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Ngày bán <span class="text-red-500">*</span></label>
                    <input type="date" name="sale_date" required class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" value="{{ date('Y-m-d') }}">
                </div>
            </div>
        </div>

        <!-- BƯỚC 2: THÔNG TIN KHÁCH HÀNG -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg mb-4">
            <h3 class="text-base font-bold text-green-900 mb-3 flex items-center">
                <span class="bg-green-500 text-white w-7 h-7 rounded-full flex items-center justify-center mr-2 text-sm">2</span>
                Thông tin khách hàng
            </h3>
            <div class="grid grid-cols-1 gap-3 mb-3">
                <div class="relative">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tên khách hàng <span class="text-red-500">*</span></label>
                    <input type="text" 
                           name="customer_name" 
                           id="customer_name" 
                           required 
                           class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                           placeholder="Nhập tên khách hàng..."
                           autocomplete="off"
                           onkeyup="filterCustomers(this.value)"
                           onfocus="showAllCustomers()"
                           onclick="showAllCustomers()">
                    <input type="hidden" name="customer_id" id="customer_id">
                    <div id="customer-suggestions" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-y-auto hidden shadow-lg"></div>
                </div>
            </div>
            <!-- Các trường ẩn sẽ hiển thị khi chọn khách hàng -->
            <div id="customer-details" class="grid grid-cols-1 md:grid-cols-3 gap-3 hidden">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Số điện thoại <span class="text-red-500">*</span></label>
                    <input type="tel" name="customer_phone" id="customer_phone" required readonly class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-green-500" placeholder="Tự động điền...">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="customer_email" id="customer_email" readonly class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-green-500" placeholder="Tự động điền...">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Địa chỉ</label>
                    <input type="text" name="customer_address" id="customer_address" readonly class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-green-500" placeholder="Tự động điền...">
                </div>
            </div>
        </div>

        <!-- BƯỚC 3: DANH SÁCH SẢN PHẨM -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg mb-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3 gap-2">
                <h3 class="text-base font-bold text-purple-900 flex items-center">
                    <span class="bg-purple-500 text-white w-7 h-7 rounded-full flex items-center justify-center mr-2 text-sm">3</span>
                    Danh sách sản phẩm
                </h3>
                <button type="button" onclick="addItem()" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-1.5 rounded-lg transition-colors font-medium text-sm whitespace-nowrap">
                    <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
                </button>
            </div>
            <div class="#">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr class="bg-purple-100">
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 border">Hình</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 border">Mô tả(Mã tranh/Khung)</th>
                            <th class="px-2 py-2 text-center text-xs font-medium text-gray-700 border">SL</th>
                            <th class="px-2 py-2 text-center text-xs font-medium text-gray-700 border">Loại tiền</th>
                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-700 border">Giá USD</th>
                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-700 border">Giá VND</th>
                            <th class="px-2 py-2 text-center text-xs font-medium text-gray-700 border">Giảm(%)</th>
                            <th class="px-2 py-2 text-center text-xs font-medium text-gray-700 border">Xóa</th>
                        </tr>
                    </thead>
                    <tbody id="items-body" class="bg-white"></tbody>
                </table>
            </div>
        </div>

        <!-- BƯỚC 4: TÍNH TOÁN & THANH TOÁN -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg mb-4">
            <h3 class="text-base font-bold text-orange-900 mb-3 flex items-center">
                <span class="bg-orange-500 text-white w-7 h-7 rounded-full flex items-center justify-center mr-2 text-sm">4</span>
                Tính toán & Thanh toán
            </h3>
            
            <!-- Giảm giá và Tổng tiền -->
            <div class="grid grid-cols-3 gap-3 mb-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Giảm giá (%)</label>
                    <input type="number" name="discount_percent" id="discount" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" value="0" min="0" max="100" step="1" onchange="calc()">
                </div>
                <div>
                    <label class="block text-xs font-medium text-blue-900 mb-1">Tổng USD</label>
                    <input type="text" id="total_usd" readonly class="w-full px-3 py-1.5 text-sm border-2 border-blue-300 rounded-lg bg-white font-bold text-blue-600">
                </div>
                <div>
                    <label class="block text-xs font-medium text-green-900 mb-1">Tổng VND</label>
                    <input type="text" id="total_vnd" readonly class="w-full px-3 py-1.5 text-sm border-2 border-green-300 rounded-lg bg-white font-bold text-green-600">
                </div>
            </div>

            <!-- Thanh toán -->
            <div class="bg-white p-3 rounded-lg border border-orange-200">
                <h4 class="text-sm font-semibold text-gray-700 mb-2">Thanh toán</h4>
                
                <!-- Tỷ giá (CHỈ dùng khi trả VND) -->
                <div class="mb-3 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                    <label class="block text-xs font-medium text-yellow-900 mb-1">
                        <i class="fas fa-exchange-alt mr-1"></i>Tỷ giá (VND/USD) <span class="text-red-500">*</span>
                        <span class="text-xs font-normal">(CHỈ dùng khi trả VND)</span>
                    </label>
                    <input type="text" name="exchange_rate" id="rate" required class="w-full px-3 py-1.5 text-sm border border-yellow-300 rounded-lg focus:ring-2 focus:ring-yellow-500 bg-white" value="{{ number_format(round($currentRate->rate ?? 25000)) }}" oninput="formatVND(this); calcTotalPaid()" onblur="formatVND(this)">
                    <p class="text-xs text-yellow-700 mt-1">
                        <i class="fas fa-info-circle mr-1"></i>Tỉ giá này CHỈ dùng để quy đổi VND → USD khi thanh toán
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Trả bằng USD</label>
                        <input type="text" id="paid_usd_display" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" value="0.00" oninput="formatPaymentUSD(this)" placeholder="0.00">
                        <input type="hidden" name="payment_usd" id="paid_usd" value="0">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Trả bằng VND</label>
                        <input type="text" id="paid_vnd_display" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" value="0" oninput="formatPaymentVND(this)" placeholder="0">
                        <input type="hidden" name="payment_vnd" id="paid_vnd" value="0">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Phương thức</label>
                        <select name="payment_method" id="payment_method" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                            <option value="cash">Tiền mặt</option>
                            <option value="bank_transfer">Chuyển khoản</option>
                            <option value="card">Thẻ</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-blue-900 mb-1">Tổng đã trả (VND)</label>
                        <input type="text" id="total_paid_display" readonly class="w-full px-3 py-1.5 text-sm border border-blue-300 rounded-lg bg-blue-50 font-bold text-blue-600">
                        <input type="hidden" name="payment_amount" id="total_paid_value">
                        <div id="total_paid_usd_display" class="text-xs text-blue-700 font-medium mt-1"></div>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-yellow-900 mb-1">Nợ cũ của khách hàng</label>
                        <input type="text" id="current_debt" readonly class="w-full px-3 py-1.5 text-sm border border-yellow-300 rounded-lg bg-white font-bold text-orange-600">
                    </div>
                </div>
            </div>
        </div>

        <!-- Ghi chú -->
        <div class="mb-4">
            <label class="block text-xs font-medium text-gray-700 mb-1">Ghi chú</label>
            <textarea name="notes" rows="2" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Nhập ghi chú (không bắt buộc)..."></textarea>
        </div>

        <!-- Buttons -->
        <div class="flex flex-col sm:flex-row gap-2 pt-4 border-t-2 border-gray-200">
            <button type="submit" name="action" value="save" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg transition-colors font-medium shadow-lg text-sm">
                <i class="fas fa-save mr-1"></i>Lưu hóa đơn
            </button>
            <button type="submit" name="action" value="save_and_print" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition-colors font-medium shadow-lg text-sm">
                <i class="fas fa-print mr-1"></i>Lưu & In
            </button>
            <a href="{{ route('sales.index') }}" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-lg transition-colors font-medium text-center shadow-lg text-sm">
                <i class="fas fa-times mr-1"></i>Hủy bỏ
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

// Hiển thị tất cả khách hàng khi hover/focus
function showAllCustomers() {
    const suggestions = document.getElementById('customer-suggestions');
    const input = document.getElementById('customer_name');
    
    // Nếu đã có khách hàng được chọn, không hiển thị dropdown
    if (document.getElementById('customer_id').value) {
        return;
    }
    
    if (customers.length > 0) {
        suggestions.innerHTML = customers.map(c => `
            <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b" onclick="selectCustomer(${c.id})">
                <div class="font-medium">${c.name}</div>
                <div class="text-xs text-gray-500">${c.phone}</div>
            </div>
        `).join('');
        suggestions.classList.remove('hidden');
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
        
        // Hiển thị các trường thông tin khách hàng
        document.getElementById('customer-details').classList.remove('hidden');
        
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
    
    // Hide item suggestions for all rows
    document.querySelectorAll('[id^="item-suggestions-"]').forEach(suggestion => {
        const idx = suggestion.id.replace('item-suggestions-', '');
        if (!e.target.closest(`#item-search-${idx}`) && !e.target.closest(`#item-suggestions-${idx}`)) {
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
                       id="item-search-${idx}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 mb-2" 
                       placeholder="Tìm tranh hoặc khung..."
                       autocomplete="off"
                       onkeyup="filterItems(this.value, ${idx})"
                       onfocus="showItemSuggestions(${idx})">
                <input type="hidden" name="items[${idx}][painting_id]" id="painting-id-${idx}">
                <input type="hidden" name="items[${idx}][frame_id]" id="frame-id-${idx}">
                <input type="hidden" name="items[${idx}][description]" id="desc-${idx}">
                <div id="item-suggestions-${idx}" class="absolute z-20 w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-y-auto hidden shadow-lg"></div>
                <div id="item-details-${idx}" class="text-xs text-gray-600 space-y-0.5 hidden"></div>
            </div>
        </td>
        <td class="px-3 py-3 border">
            <input type="number" name="items[${idx}][quantity]" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-center font-medium" value="1" min="1" onchange="calc()">
        </td>
         <td class="px-3 py-3 border">
            <select name="items[${idx}][currency]" class="w-full px-3 py-2 border border-gray-300 rounded-lg" onchange="togCur(this, ${idx})">
                <option value="USD">USD</option>
                <option value="VND" selected>VND</option>
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

// Search functions for frames
function filterFrames(query, idx) {
    const suggestions = document.getElementById(`frame-suggestions-${idx}`);
    
    if (!query || query.length < 1) {
        suggestions.classList.add('hidden');
        return;
    }
    
    fetch(`{{ route('sales.api.search.frames') }}?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(frames => {
            if (frames.length > 0) {
                suggestions.innerHTML = frames.map(f => `
                    <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b" onclick="selectFrame(${f.id}, ${idx})">
                        <div class="font-medium text-sm">${f.name}</div>
                        <div class="text-xs text-gray-500">Giá: ${(f.cost_price || 0).toLocaleString()}đ</div>
                    </div>
                `).join('');
                suggestions.classList.remove('hidden');
            } else {
                suggestions.classList.add('hidden');
            }
        })
        .catch(error => {
            console.error('Error searching frames:', error);
            suggestions.classList.add('hidden');
        });
}

function showFrameSuggestions(idx) {
    const input = document.getElementById(`frame-search-${idx}`);
    const suggestions = document.getElementById(`frame-suggestions-${idx}`);
    
    if (input && input.value.length >= 1) {
        filterFrames(input.value, idx);
    }
}

function selectFrame(frameId, idx) {
    fetch(`{{ route('frames.show', '') }}/${frameId}`)
        .then(response => response.json())
        .then(frame => {
            document.getElementById(`frame-id-${idx}`).value = frame.id;
            document.getElementById(`frame-search-${idx}`).value = frame.name;
            
            document.getElementById(`frame-suggestions-${idx}`).classList.add('hidden');
        })
        .catch(error => {
            console.error('Error fetching frame:', error);
        });
}

// NEW: Search function for both paintings and frames
function filterItems(query, idx) {
    const suggestions = document.getElementById(`item-suggestions-${idx}`);
    
    if (!query || query.length < 1) {
        suggestions.classList.add('hidden');
        return;
    }
    
    // Fetch both paintings and frames
    Promise.all([
        fetch(`{{ route('sales.api.search.paintings') }}?q=${encodeURIComponent(query)}`).then(r => r.json()),
        fetch(`{{ route('sales.api.search.frames') }}?q=${encodeURIComponent(query)}`).then(r => r.json())
    ])
    .then(([paintings, frames]) => {
        let html = '';
        
        // Add paintings section
        if (paintings.length > 0) {
            html += '<div class="px-3 py-1 bg-gray-100 text-xs font-bold text-gray-600">TRANH</div>';
            html += paintings.map(p => {
                const stock = p.quantity || 0;
                const isOutOfStock = stock <= 0;
                const bgClass = isOutOfStock ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-blue-50';
                const stockColor = isOutOfStock ? 'text-red-600 font-bold' : (stock < 5 ? 'text-orange-600' : 'text-green-600');
                const stockText = isOutOfStock ? '❌ HẾT HÀNG' : `Tồn: ${stock}`;
                
                return `
                    <div class="px-3 py-2 ${bgClass} cursor-pointer border-b" onclick="selectPainting(${p.id}, ${idx})">
                        <div class="flex justify-between items-start">
                            <div class="font-medium text-sm">${p.code} - ${p.name}</div>
                            <span class="text-xs ${stockColor} ml-2 whitespace-nowrap">${stockText}</span>
                        </div>
                        <div class="text-xs text-gray-500">USD: ${p.price_usd || 0} | VND: ${(p.price_vnd || 0).toLocaleString()}đ</div>
                    </div>
                `;
            }).join('');
        }
        
        // Add frames section
        if (frames.length > 0) {
            html += '<div class="px-3 py-1 bg-gray-100 text-xs font-bold text-gray-600">KHUNG</div>';
            html += frames.map(f => `
                <div class="px-3 py-2 hover:bg-green-50 cursor-pointer border-b" onclick="selectFrame(${f.id}, ${idx})">
                    <div class="font-medium text-sm">${f.name}</div>
                    <div class="text-xs text-gray-500">Giá: ${(f.cost_price || 0).toLocaleString()}đ</div>
                </div>
            `).join('');
        }
        
        if (html) {
            suggestions.innerHTML = html;
            suggestions.classList.remove('hidden');
        } else {
            suggestions.classList.add('hidden');
        }
    })
    .catch(error => {
        console.error('Error searching items:', error);
        suggestions.classList.add('hidden');
    });
}

function showItemSuggestions(idx) {
    const input = document.getElementById(`item-search-${idx}`);
    
    if (input && input.value.length >= 1) {
        filterItems(input.value, idx);
    }
}

function selectPainting(paintingId, idx) {
    fetch(`{{ route('sales.api.painting', '') }}/${paintingId}`)
        .then(response => response.json())
        .then(painting => {
            // Clear frame selection
            document.getElementById(`frame-id-${idx}`).value = '';
            
            // Set painting data
            document.getElementById(`painting-id-${idx}`).value = painting.id;
            document.getElementById(`item-search-${idx}`).value = `${painting.code} - ${painting.name}`;
            document.getElementById(`desc-${idx}`).value = painting.name;
            
            const usdInput = document.querySelector(`.usd-${idx}`);
            const vndInput = document.querySelector(`.vnd-${idx}`);
            const currencySelect = document.querySelector(`select[name="items[${idx}][currency]"]`);
            
            const hasUsd = painting.price_usd && parseFloat(painting.price_usd) > 0;
            const hasVnd = painting.price_vnd && parseFloat(painting.price_vnd) > 0;
            
            // Tự động chọn loại tiền dựa vào giá sản phẩm
            if (hasUsd && hasVnd) {
                // Có cả 2 giá → Chọn BOTH
                if (currencySelect) {
                    currencySelect.value = 'BOTH';
                    togCur(currencySelect, idx);
                }
                if (usdInput) {
                    usdInput.value = parseFloat(painting.price_usd).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
                if (vndInput) {
                    vndInput.value = parseInt(painting.price_vnd).toLocaleString('en-US');
                }
            } else if (hasUsd) {
                // Chỉ có giá USD → Chọn USD
                if (currencySelect) {
                    currencySelect.value = 'USD';
                    togCur(currencySelect, idx);
                }
                if (usdInput) {
                    usdInput.value = parseFloat(painting.price_usd).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
                if (vndInput) {
                    vndInput.value = '0';
                }
            } else if (hasVnd) {
                // Chỉ có giá VND → Chọn VND
                if (currencySelect) {
                    currencySelect.value = 'VND';
                    togCur(currencySelect, idx);
                }
                if (usdInput) {
                    usdInput.value = '0.00';
                }
                if (vndInput) {
                    vndInput.value = parseInt(painting.price_vnd).toLocaleString('en-US');
                }
            } else {
                // Không có giá nào → Mặc định VND
                if (currencySelect) {
                    currencySelect.value = 'VND';
                    togCur(currencySelect, idx);
                }
                if (usdInput) usdInput.value = '0.00';
                if (vndInput) vndInput.value = '0';
            }
            
            const imgUrl = painting.image ? `/storage/${painting.image}` : 'https://via.placeholder.com/80x60?text=No+Image';
            const imgElement = document.getElementById(`img-${idx}`);
            imgElement.src = imgUrl;
            imgElement.onclick = () => showImageModal(imgUrl, painting.name);
            imgElement.classList.add('cursor-pointer', 'hover:opacity-80', 'transition-opacity');
            
            // Display painting details
            const detailsDiv = document.getElementById(`item-details-${idx}`);
            if (detailsDiv) {
                let detailsHTML = '';
                if (painting.code) detailsHTML += `<div><span class="font-semibold">Mã:</span> ${painting.code}</div>`;
                if (painting.artist) detailsHTML += `<div><span class="font-semibold">Họa sĩ:</span> ${painting.artist}</div>`;
                if (painting.material) detailsHTML += `<div><span class="font-semibold">Chất liệu:</span> ${painting.material}</div>`;
                if (painting.width && painting.height) detailsHTML += `<div><span class="font-semibold">Kích thước:</span> ${painting.width} x ${painting.height} cm</div>`;
                if (painting.paint_year) detailsHTML += `<div><span class="font-semibold">Năm:</span> ${painting.paint_year}</div>`;
                
                if (detailsHTML) {
                    detailsDiv.innerHTML = detailsHTML;
                    detailsDiv.classList.remove('hidden');
                } else {
                    detailsDiv.classList.add('hidden');
                }
            }
            
            // Kiểm tra tồn kho
            const stock = painting.quantity || 0;
            const itemSearchInput = document.getElementById(`item-search-${idx}`);
            
            if (stock <= 0) {
                // Tranh hết hàng - cảnh báo
                itemSearchInput.classList.add('border-red-500', 'bg-red-50');
                itemSearchInput.title = '⚠️ Tranh này đã hết hàng!';
                
                if (typeof showWarning === 'function') {
                    showWarning(itemSearchInput, '❌ Tranh "' + painting.name + '" đã HẾT HÀNG! Tồn kho: 0');
                }
                
                // Thêm badge hết hàng vào input
                itemSearchInput.value = `${painting.code} - ${painting.name} [HẾT HÀNG]`;
            } else if (stock < 5) {
                // Sắp hết hàng - cảnh báo nhẹ
                itemSearchInput.classList.add('border-orange-400', 'bg-orange-50');
                itemSearchInput.title = ' Tranh này  Còn: ' + stock;
                
                if (typeof showWarning === 'function') {
                    showWarning(itemSearchInput, ' Tranh "' + painting.name + '"  Còn: ' + stock);
                }
            } else {
                itemSearchInput.classList.remove('border-red-500', 'bg-red-50', 'border-orange-400', 'bg-orange-50');
                itemSearchInput.title = '';
            }
            
            document.getElementById(`item-suggestions-${idx}`).classList.add('hidden');
            calc();
        })
        .catch(error => {
            console.error('Error fetching painting:', error);
        });
}

function selectFrame(frameId, idx) {
    fetch(`{{ route('frames.api.frame', '') }}/${frameId}`)
        .then(response => response.json())
        .then(frame => {
            // Clear painting selection
            document.getElementById(`painting-id-${idx}`).value = '';
            
            // Set frame data
            document.getElementById(`frame-id-${idx}`).value = frame.id;
            document.getElementById(`item-search-${idx}`).value = frame.name;
            document.getElementById(`desc-${idx}`).value = frame.name;
            
            // Set price from frame cost_price
            const vndInput = document.querySelector(`.vnd-${idx}`);
            if (vndInput) {
                const vndValue = parseInt(frame.cost_price) || 0;
                vndInput.value = vndValue.toLocaleString('en-US');
            }
            
            // Set currency dropdown to VND and hide USD input
            const currencySelect = document.querySelector(`select[name="items[${idx}][currency]"]`);
            if (currencySelect) {
                currencySelect.value = 'VND';
                // Trigger the togCur function to hide/show appropriate inputs
                togCur(currencySelect, idx);
            }
            
            // Clear image for frame
            const imgElement = document.getElementById(`img-${idx}`);
            imgElement.src = 'https://via.placeholder.com/80x60?text=Khung';
            
            document.getElementById(`item-suggestions-${idx}`).classList.add('hidden');
            calc();
        })
        .catch(error => {
            console.error('Error fetching frame:', error);
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
    const disc = parseFloat(document.getElementById('discount').value) || 0;
    const rows = document.querySelectorAll('#items-body tr');
    
    // Lấy tỷ giá hiện tại
    const rateEl = document.getElementById('rate');
    const exchangeRate = parseFloat(unformatNumber(rateEl?.value || '25000')) || 25000;
    
    let totUsd = 0;
    let totVnd = 0;
    
    rows.forEach((row, i) => {
        const qty = parseFloat(row.querySelector('[name*="[quantity]"]')?.value || 0);
        const cur = row.querySelector('[name*="[currency]"]')?.value || 'USD';
        const itemDiscountPercent = parseFloat(row.querySelector('[name*="[discount_percent]"]')?.value || 0);
        
        if (cur === 'USD') {
            // Sản phẩm giá USD - USD là chính
            const usdVal = unformatNumber(row.querySelector('[name*="[price_usd]"]')?.value || '0');
            const usd = parseFloat(usdVal);
            const subtotal = usd * qty;
            const itemDiscountAmt = subtotal * (itemDiscountPercent / 100);
            const itemTotalUsd = subtotal - itemDiscountAmt;
            totUsd += itemTotalUsd;
            // Quy đổi sang VND để hiển thị
            totVnd += itemTotalUsd * exchangeRate;
        } else if (cur === 'VND') {
            // Sản phẩm giá VND - Quy đổi sang USD (USD là tiền tệ chính)
            const vndVal = unformatNumber(row.querySelector('[name*="[price_vnd]"]')?.value || '0');
            const vnd = parseFloat(vndVal);
            const subtotal = vnd * qty;
            const itemDiscountAmt = subtotal * (itemDiscountPercent / 100);
            const itemTotalVnd = subtotal - itemDiscountAmt;
            totVnd += itemTotalVnd;
            // Quy đổi VND sang USD (USD là đơn vị chính)
            totUsd += itemTotalVnd / exchangeRate;
        } else { // BOTH
            // Sản phẩm có cả 2 giá - tính riêng từng loại
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
    
    // Áp dụng giảm giá chung
    const discAmtUsd = totUsd * (disc / 100);
    const discAmtVnd = totVnd * (disc / 100);
    const finalUsd = totUsd - discAmtUsd;
    const finalVnd = totVnd - discAmtVnd;
    
    // Hiển thị tổng tiền
    document.getElementById('total_usd').value = '$' + finalUsd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('total_vnd').value = finalVnd.toLocaleString('vi-VN') + 'đ';
    
    calcDebt();
}

// Generate invoice code from API
function generateInvoiceCode() {
    const showroomSelect = document.getElementById('showroom_id');
    const showroomId = showroomSelect.value;
    
    if (!showroomId) {
        alert('Vui lòng chọn showroom trước!');
        showroomSelect.focus();
        return;
    }
    
    // Show loading
    const invoiceInput = document.getElementById('invoice_code');
    invoiceInput.value = 'Đang tạo...';
    invoiceInput.disabled = true;
    
    // Call API to generate invoice code
    fetch(`{{ route('sales.api.generate-invoice-code') }}?showroom_id=${showroomId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                invoiceInput.value = data.invoice_code;
            } else {
                alert('Lỗi: ' + data.message);
                invoiceInput.value = '';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi tạo mã hóa đơn');
            invoiceInput.value = '';
        })
        .finally(() => {
            invoiceInput.disabled = false;
        });
}

// Auto-generate invoice code when showroom changes
document.addEventListener('DOMContentLoaded', function() {
    const showroomSelect = document.getElementById('showroom_id');
    if (showroomSelect) {
        showroomSelect.addEventListener('change', function() {
            if (this.value) {
                generateInvoiceCode();
            }
        });
        
        // Auto-generate on page load if showroom is already selected
        if (showroomSelect.value) {
            generateInvoiceCode();
        }
    }
});

// Format payment USD (giống format giá USD)
function formatPaymentUSD(input) {
    let value = input.value.replace(/[^\d.]/g, '');
    const parts = value.split('.');
    
    // Chỉ cho phép 1 dấu chấm
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
        parts.length = 2;
        parts[0] = value.split('.')[0];
        parts[1] = value.split('.')[1];
    }
    
    // Lưu giá trị số thuần vào hidden input
    const rawValue = value;
    const hiddenInput = document.getElementById('paid_usd');
    if (hiddenInput) {
        hiddenInput.value = rawValue || '0';
    }
    
    // Format phần nguyên với dấu phẩy
    if (parts[0]) {
        parts[0] = parseInt(parts[0]).toLocaleString('en-US');
    }
    
    // Giới hạn 2 chữ số thập phân
    if (parts[1]) {
        parts[1] = parts[1].substring(0, 2);
    }
    
    // Ghép lại và hiển thị
    input.value = parts.length > 1 ? parts[0] + '.' + (parts[1] || '') : parts[0];
    
    // Tính tổng đã trả
    calcTotalPaid();
}

// Format payment VND
function formatPaymentVND(input) {
    let value = input.value.replace(/[^\d]/g, '');
    
    // Lưu giá trị số thuần vào hidden input
    const hiddenInput = document.getElementById('paid_vnd');
    if (hiddenInput) {
        hiddenInput.value = value || '0';
    }
    
    // Format và hiển thị
    if (value) {
        input.value = parseInt(value).toLocaleString('vi-VN');
    }
    
    // Tính tổng đã trả
    calcTotalPaid();
}

// Calculate total paid (USD + VND converted)
function calcTotalPaid() {
    // Kiểm tra các element tồn tại
    const rateEl = document.getElementById('rate');
    const paidUsdEl = document.getElementById('paid_usd'); // hidden input
    const paidVndEl = document.getElementById('paid_vnd'); // hidden input
    const totalPaidDisplayEl = document.getElementById('total_paid_display');
    const totalPaidValueEl = document.getElementById('total_paid_value');
    const totalPaidUsdDisplayEl = document.getElementById('total_paid_usd_display');
    
    if (!rateEl || !paidUsdEl || !paidVndEl || !totalPaidDisplayEl || !totalPaidValueEl) {
        return; // Nếu thiếu element thì không tính
    }
    
    // Lấy tỉ giá - CHỈ dùng khi trả VND
    const exchangeRate = parseFloat(unformatNumber(rateEl.value)) || 25000;
    
    // Get USD paid từ hidden input (đã là số thuần)
    const paidUsd = parseFloat(paidUsdEl.value) || 0;
    
    // Get VND paid từ hidden input (đã là số thuần)
    const paidVnd = parseFloat(paidVndEl.value) || 0;
    
    // Tính tổng USD (đơn vị chính):
    // - USD: cộng trực tiếp (KHÔNG cần tỉ giá)
    // - VND: quy đổi sang USD theo tỉ giá hiện tại
    const totalPaidUsd = paidUsd + (paidVnd / exchangeRate);
    
    // Tính tổng VND (chỉ để hiển thị tham khảo):
    // - USD: quy đổi sang VND theo tỉ giá
    // - VND: cộng trực tiếp
    const totalPaidVnd = (paidUsd * exchangeRate) + paidVnd;
    
    // Display total paid VND (tham khảo)
    totalPaidDisplayEl.value = totalPaidVnd.toLocaleString('vi-VN') + 'đ';
    totalPaidValueEl.value = Math.round(totalPaidVnd);
    
    // Hiển thị tổng USD (đơn vị chính) với chi tiết
    if (totalPaidUsdDisplayEl) {
        if (paidUsd > 0 || paidVnd > 0) {
            let displayText = '≈ $' + totalPaidUsd.toFixed(2) + ' USD';
            if (paidUsd > 0 && paidVnd > 0) {
                displayText += ' ($' + paidUsd.toFixed(2) + ' + ' + paidVnd.toLocaleString('vi-VN') + 'đ ÷ ' + exchangeRate.toLocaleString('vi-VN') + ')';
            } else if (paidVnd > 0) {
                displayText += ' (' + paidVnd.toLocaleString('vi-VN') + 'đ ÷ ' + exchangeRate.toLocaleString('vi-VN') + ')';
            } else if (paidUsd > 0) {
                displayText = '$' + paidUsd.toFixed(2) + ' USD';
            }
            totalPaidUsdDisplayEl.textContent = displayText;
        } else {
            totalPaidUsdDisplayEl.textContent = '';
        }
    }
    
    // Calculate debt
    calcDebt();
}

function calcDebt() {
    const totalUsdEl = document.getElementById('total_usd');
    const totalVndEl = document.getElementById('total_vnd');
    const totalPaidValueEl = document.getElementById('total_paid_value');
    const debtUsdEl = document.getElementById('debt_usd');
    const debtVndEl = document.getElementById('debt_vnd');
    const totalPaidDisplayEl = document.getElementById('total_paid_display');
    const rateEl = document.getElementById('rate');
    const paidUsdEl = document.getElementById('paid_usd');
    const paidVndEl = document.getElementById('paid_vnd');
    
    if (!totalUsdEl || !totalVndEl || !totalPaidValueEl || !debtUsdEl || !rateEl) {
        return; // Nếu thiếu element thì không tính
    }
    
    // Lấy tổng tiền USD
    const totalUsdTxt = totalUsdEl.value.replace(/[^\d.]/g, '');
    const totalUsd = parseFloat(totalUsdTxt) || 0;
    
    // Lấy tổng tiền VND
    const totalVndTxt = totalVndEl.value.replace(/[^\d]/g, '');
    const totalVnd = parseFloat(totalVndTxt) || 0;
    
    // Lấy tỉ giá
    const rateTxt = rateEl.value.replace(/[^\d]/g, '');
    const exchangeRate = parseFloat(rateTxt) || 25000;
    
    // Lấy số tiền đã trả USD và VND
    const paidUsdValue = parseFloat(paidUsdEl.value) || 0;
    const paidVndValue = parseFloat(paidVndEl.value) || 0;
    
    // Tính tổng đã trả theo USD:
    // - USD: cộng trực tiếp (không cần tỉ giá)
    // - VND: quy đổi sang USD theo tỉ giá
    const totalPaidUsd = paidUsdValue + (paidVndValue / exchangeRate);
    
    // Tính công nợ theo USD (USD làm chuẩn)
    const debtUsd = Math.max(0, totalUsd - totalPaidUsd);
    
    // Tính công nợ VND (để tham khảo)
    const debtVnd = debtUsd * exchangeRate;
    
    // Hiển thị công nợ USD
    debtUsdEl.value = '$' + debtUsd.toFixed(2);
    
    // Hiển thị công nợ VND (quy đổi từ USD)
    if (debtVndEl) {
        if (debtUsd > 0) {
            debtVndEl.value = '≈ ' + debtVnd.toLocaleString('vi-VN') + 'đ (theo tỉ giá ' + exchangeRate.toLocaleString('vi-VN') + ')';
        } else {
            debtVndEl.value = '';
        }
    }
    
    // Warning if overpaid
    const totalPaidVnd = parseFloat(totalPaidValueEl.value) || 0;
    if (totalPaidVnd > totalVnd && totalPaidVnd > 0 && totalPaidDisplayEl) {
        totalPaidDisplayEl.classList.add('border-orange-500', 'bg-orange-100');
        totalPaidDisplayEl.title = 'Số tiền trả vượt quá tổng tiền hóa đơn';
        setTimeout(() => {
            totalPaidDisplayEl.classList.remove('border-orange-500', 'bg-orange-100');
            totalPaidDisplayEl.title = '';
        }, 3000);
    }
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
    addItem();
    calcDebt(); // Tính công nợ ngay khi load trang
    
    // Form validation và unformat trước khi submit
    const form = document.getElementById('sales-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Unformat tất cả giá USD và VND của items trước khi submit
            const priceUsdInputs = document.querySelectorAll('input[name*="[price_usd]"]');
            const priceVndInputs = document.querySelectorAll('input[name*="[price_vnd]"]');
            
            priceUsdInputs.forEach(input => {
                input.value = unformatNumber(input.value);
            });
            
            priceVndInputs.forEach(input => {
                input.value = unformatNumber(input.value);
            });
            
            // Validation - chặn submit nếu trả vượt quá (tính theo USD)
            const totalUsdEl = document.getElementById('total_usd');
            const totalVndEl = document.getElementById('total_vnd');
            const totalPaidValueEl = document.getElementById('total_paid_value');
            const paidUsdHiddenEl = document.getElementById('paid_usd');
            const paidVndHiddenEl = document.getElementById('paid_vnd');
            const rateEl = document.getElementById('rate');
            
            if (!totalUsdEl || !totalVndEl || !totalPaidValueEl || !rateEl) return;
            
            // Lấy tổng USD
            const totalUsdTxt = totalUsdEl.value.replace(/[^\d.]/g, '');
            const totalUsd = parseFloat(totalUsdTxt) || 0;
            
            // Lấy tổng VND
            const totalVndTxt = totalVndEl.value.replace(/[^\d]/g, '');
            const totalVnd = parseFloat(totalVndTxt) || 0;
            
            // Lấy số tiền đã trả
            const paidUsd = parseFloat(paidUsdHiddenEl?.value || 0);
            const paidVnd = parseFloat(paidVndHiddenEl?.value || 0);
            const rate = parseFloat(unformatNumber(rateEl.value)) || 25000;
            
            // Tính tổng đã trả theo USD
            const totalPaidUsd = paidUsd + (paidVnd / rate);
            
            // Tính tổng đã trả theo VND
            const totalPaidVnd = parseFloat(totalPaidValueEl.value) || 0;
            
            if (totalPaidUsd > totalUsd) {
                e.preventDefault();
                
                // Kiểm tra xem phiếu có item USD không
                const rows = document.querySelectorAll('#items-table tbody tr');
                let hasUsdItem = false;
                let hasVndItem = false;
                
                rows.forEach(row => {
                    const currency = row.querySelector('[name*="[currency]"]')?.value;
                    if (currency === 'USD' || currency === 'BOTH') {
                        hasUsdItem = true;
                    }
                    if (currency === 'VND' || currency === 'BOTH') {
                        hasVndItem = true;
                    }
                });
                
                // Tạo thông báo dựa vào loại tiền tệ của items
                let message = '⚠️ Cảnh báo!\n\nSố tiền trả vượt quá tổng hóa đơn!\n\n';
                
                if (hasUsdItem) {
                    // Phiếu có item USD → hiển thị USD là chính
                    message += 'Tổng hóa đơn: $' + totalUsd.toFixed(2) + '\n';
                    message += 'Đã trả trước: $0.00\n';
                    message += 'Trả thêm: $' + totalPaidUsd.toFixed(2) + '\n';
                    message += 'Tổng trả: $' + totalPaidUsd.toFixed(2) + '\n\n';
                } else if (hasVndItem) {
                    // Phiếu chỉ có item VND → hiển thị VND và quy đổi sang USD
                    message += 'Tổng hóa đơn: ' + totalVnd.toLocaleString('vi-VN') + 'đ (≈$' + totalUsd.toFixed(2) + ')\n';
                    message += 'Đã trả trước: 0đ\n';
                    message += 'Trả thêm: ' + totalPaidVnd.toLocaleString('vi-VN') + 'đ (≈$' + totalPaidUsd.toFixed(2) + ')\n';
                    message += 'Tổng trả: ' + totalPaidVnd.toLocaleString('vi-VN') + 'đ (≈$' + totalPaidUsd.toFixed(2) + ')\n\n';
                }
                
                message += 'Vui lòng điều chỉnh số tiền!';
                alert(message);
                
                // Highlight các ô nhập
                const paidUsdDisplayEl = document.getElementById('paid_usd_display');
                const paidVndDisplayEl = document.getElementById('paid_vnd_display');
                if (paidUsdDisplayEl) paidUsdDisplayEl.classList.add('border-red-500', 'bg-red-50');
                if (paidVndDisplayEl) paidVndDisplayEl.classList.add('border-red-500', 'bg-red-50');
                
                return false;
            }
        });
    }
});

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

// Form validation không cần thiết nữa vì số tiền đã tự động điều chỉnh
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
