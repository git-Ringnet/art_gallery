@extends('layouts.app')

@section('title', 'Tạo phiếu đổi/trả hàng')
@section('page-title', 'Tạo phiếu đổi/trả hàng')
@section('page-description', 'Tạo phiếu đổi hoặc trả hàng mới')

@section('content')
<x-alert />

<form action="{{ route('returns.store') }}" method="POST" id="return-form" onsubmit="return validateForm(event)">
    @csrf
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Search Invoice -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-lg p-6  mb-6">
                <h3 class="text-lg font-semibold mb-4">Tìm hóa đơn gốc</h3>
                
                <div class="flex gap-3">
                    <div class="flex-1">
                        <input type="text" id="invoice-search" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Nhập mã hóa đơn...">
                    </div>
                    <button type="button" onclick="searchInvoice()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-search mr-2"></i>Tìm
                    </button>
                </div>
                
                <div id="invoice-info" class="hidden mt-4 p-4 bg-gray-50 rounded-lg">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div><span class="text-gray-600">Mã HD:</span> <span id="inv-code" class="font-medium"></span></div>
                        <div><span class="text-gray-600">Ngày:</span> <span id="inv-date"></span></div>
                        <div><span class="text-gray-600">Khách hàng:</span> <span id="inv-customer" class="font-medium"></span></div>
                        <div class="border-t pt-2 mt-2">
                            <div class="flex justify-between mb-1">
                                <span class="text-gray-600">Tổng hóa đơn:</span>
                                <span id="inv-total" class="font-medium"></span>
                            </div>
                            <div class="flex justify-between mb-1">
                                <span class="text-gray-600">Đã thanh toán:</span>
                                <span id="inv-paid" class="font-medium text-green-600"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Còn nợ:</span>
                                <span id="inv-debt" class="font-medium text-red-600"></span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 p-2 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        <span>Chỉ hoàn tối đa số tiền khách đã thanh toán</span>
                    </div>
                </div>
                
                <input type="hidden" name="sale_id" id="sale-id">
                <input type="hidden" name="customer_id" id="customer-id">
            </div>
            
            <!-- Products List - Return Items -->
            <div class="bg-white rounded-xl shadow-lg p-6  mb-6">
                <h3 class="text-lg font-semibold mb-4">Sản phẩm trả lại</h3>
                
                <div id="products-container">
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-box-open text-4xl mb-2"></i>
                        <p>Vui lòng tìm hóa đơn trước</p>
                    </div>
                </div>
            </div>
            
            <!-- Exchange Products - Only show when type is exchange -->
            <div id="exchange-section" class="bg-white rounded-xl shadow-lg p-6  hidden">
                <h3 class="text-lg font-semibold mb-4">Sản phẩm đổi mới</h3>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tìm sản phẩm</label>
                    <div class="relative">
                        <input type="text" 
                               id="product-search" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg" 
                               placeholder="Nhập tên sản phẩm..."
                               autocomplete="off"
                               oninput="searchProductsAuto()">
                        <div id="product-suggestions" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto hidden"></div>
                    </div>
                </div>
                
                <div class="#">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-blue-50 to-indigo-50 border-b-2 border-blue-200">
                                <th class="px-3 py-3 text-left font-semibold text-gray-700">Hình ảnh</th>
                                <th class="px-3 py-3 text-left font-semibold text-gray-700">Mã (Tranh/Khung)</th>
                                <th class="px-3 py-3 text-left font-semibold text-gray-700">Vật tư(Khung)</th>
                                <th class="px-3 py-3 text-center font-semibold text-gray-700">Số mét/Cây</th>
                                <th class="px-3 py-3 text-center font-semibold text-gray-700">Số lượng</th>
                                <th class="px-3 py-3 text-right font-semibold text-gray-700">Giá bán (đ)</th>
                                <th class="px-3 py-3 text-center font-semibold text-gray-700">Giảm giá (%)</th>
                                <th class="px-3 py-3 text-center font-semibold text-gray-700">Xóa</th>
                            </tr>
                        </thead>
                        <tbody id="exchange-products-container">
                            <tr>
                                <td colspan="8" class="text-center text-gray-500 py-8">
                                    <i class="fas fa-box-open text-3xl mb-2 text-gray-400"></i>
                                    <p class="text-sm">Tìm kiếm sản phẩm để đổi</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Return Info -->
        <div>
            <div class="bg-white rounded-xl shadow-lg p-6  sticky top-6">
                <h3 class="text-lg font-semibold mb-4">Thông tin đổi/trả</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Loại giao dịch *</label>
                        <select name="type" id="return-type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required onchange="updateReturnType()">
                            <option value="return">Trả hàng</option>
                            <option value="exchange">Đổi hàng</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ngày đổi/trả *</label>
                        <input type="date" name="return_date" value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lý do</label>
                        <textarea name="reason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Nhập lý do đổi/trả..."></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
                        <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Ghi chú thêm..."></textarea>
                    </div>
                    
                    <div class="border-t pt-4">
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">SL trả:</span>
                                <span id="summary-return-qty" class="font-medium">0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Giá trị hàng trả:</span>
                                <span id="summary-return-value" class="font-medium">0đ</span>
                            </div>
                            <div class="flex justify-between border-t pt-2">
                                <span class="text-gray-700 font-semibold">Tiền hoàn thực tế:</span>
                                <span id="summary-return-amount" class="font-bold text-red-600 text-lg">0đ</span>
                            </div>
                            <div class="text-xs text-gray-500 italic">
                                <i class="fas fa-info-circle mr-1"></i>
                                <span id="refund-note">Tối đa bằng số tiền đã thanh toán</span>
                            </div>
                            <div id="exchange-summary" class="hidden">
                                <div class="flex justify-between border-t pt-2 mt-2">
                                    <span class="text-gray-600">SL đổi:</span>
                                    <span id="summary-exchange-qty" class="font-medium">0</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tiền đổi:</span>
                                    <span id="summary-exchange-amount" class="font-semibold text-blue-600">0đ</span>
                                </div>
                                <div class="flex justify-between border-t pt-2 mt-2">
                                    <span class="text-gray-700 font-medium">Chênh lệch:</span>
                                    <span id="summary-difference" class="font-bold text-lg">0đ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 pt-4">
                        <button type="submit" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-save mr-2"></i>Lưu
                        </button>
                        <a href="{{ route('returns.index') }}" class="flex-1 bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-600 text-center">
                            <i class="fas fa-times mr-2"></i>Hủy
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
let saleData = null;
let exchangeProducts = [];

function searchInvoice() {
    const code = document.getElementById('invoice-search').value.trim();
    if (!code) {
        showNotification('Vui lòng nhập mã hóa đơn', 'error');
        return;
    }
    
    fetch(`{{ route('returns.searchInvoice') }}?invoice_code=${code}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                showNotification(data.message || 'Không tìm thấy hóa đơn', 'error');
                return;
            }
            
            saleData = data.sale;
            displayInvoiceInfo(data.sale);
            displayProducts(data.sale.items, data.returned_quantities);
            showNotification('Tìm thấy hóa đơn', 'success');
        })
        .catch(err => {
            console.error(err);
            showNotification('Lỗi khi tìm hóa đơn', 'error');
        });
}

function displayInvoiceInfo(sale) {
    document.getElementById('invoice-info').classList.remove('hidden');
    document.getElementById('inv-code').textContent = sale.invoice_code;
    document.getElementById('inv-date').textContent = new Date(sale.sale_date).toLocaleDateString('vi-VN');
    document.getElementById('inv-customer').textContent = sale.customer.name;
    document.getElementById('inv-total').textContent = parseFloat(sale.total_vnd).toLocaleString('vi-VN') + 'đ';
    document.getElementById('inv-paid').textContent = parseFloat(sale.paid_amount).toLocaleString('vi-VN') + 'đ';
    document.getElementById('inv-debt').textContent = parseFloat(sale.debt_amount).toLocaleString('vi-VN') + 'đ';
    document.getElementById('sale-id').value = sale.id;
    document.getElementById('customer-id').value = sale.customer_id;
}

function displayProducts(items, returnedQty) {
    const container = document.getElementById('products-container');
    container.innerHTML = '';
    
    let hasItems = false;
    items.forEach(item => {
        const available = item.quantity - (returnedQty[item.id] || 0);
        if (available <= 0) return;
        
        hasItems = true;
        const div = document.createElement('div');
        div.className = 'border rounded-lg p-4 mb-3';
        
        // Build image HTML
        const imageHtml = item.painting_image ? 
            `<img src="/storage/${item.painting_image}" alt="${item.item_name}" class="w-20 h-20 object-cover rounded-lg cursor-pointer hover:opacity-80 transition-opacity flex-shrink-0" onclick="showImageModal('/storage/${item.painting_image}', '${item.item_name.replace(/'/g, "\\'")}')">` :
            '<div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center flex-shrink-0"><i class="fas fa-image text-gray-400"></i></div>';
        
        div.innerHTML = `
            <div class="flex items-start gap-4 mb-2">
                ${imageHtml}
                <div class="flex-1">
                    <h4 class="font-medium">${item.item_name}</h4>
                    <p class="text-sm text-gray-600">Đã mua: ${item.quantity} | Đã trả: ${returnedQty[item.id] || 0} | Còn lại: ${available}</p>
                    <p class="text-sm text-green-600">Đơn giá: ${parseFloat(item.unit_price).toLocaleString('vi-VN')}đ</p>
                </div>
                <div class="text-right flex-shrink-0">
                    <label class="text-sm text-gray-600">Số lượng trả:</label>
                    <input type="number" 
                           name="items[${item.id}][quantity]" 
                           class="w-20 px-2 py-1 border rounded text-center return-qty"
                           min="0" 
                           max="${available}" 
                           value="0"
                           data-price="${item.unit_price}"
                           onchange="updateSummary()">
                    <input type="hidden" name="items[${item.id}][sale_item_id]" value="${item.id}">
                </div>
            </div>
        `;
        container.appendChild(div);
    });
    
    if (!hasItems) {
        container.innerHTML = '<div class="text-center text-gray-500 py-8"><i class="fas fa-info-circle text-4xl mb-2"></i><p>Tất cả sản phẩm đã được trả</p></div>';
    }
}

let searchTimeout;
function searchProductsAuto() {
    clearTimeout(searchTimeout);
    const query = document.getElementById('product-search').value.trim();
    
    if (query.length < 1) {
        document.getElementById('product-suggestions').classList.add('hidden');
        return;
    }
    
    searchTimeout = setTimeout(() => {
        // Chỉ tìm tranh, không tìm vật tư
        fetch(`{{ route('sales.api.search.paintings') }}?q=${query}`)
            .then(r => r.json())
            .then(paintings => {
                const products = paintings.map(p => ({...p, type: 'painting'}));
                displaySuggestions(products);
            })
            .catch(err => {
                console.error(err);
            });
    }, 300);
}

function displaySuggestions(products) {
    const container = document.getElementById('product-suggestions');
    
    if (products.length === 0) {
        container.classList.add('hidden');
        return;
    }
    
    container.innerHTML = products.map(product => {
        const code = product.code || '';
        const image = product.image || '';
        const escapedName = product.name.replace(/'/g, "\\'").replace(/"/g, '&quot;');
        const escapedCode = code.replace(/'/g, "\\'").replace(/"/g, '&quot;');
        const escapedImage = image.replace(/'/g, "\\'").replace(/"/g, '&quot;');
        
        return `
        <div class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b last:border-b-0" onclick='selectProduct(${product.id}, "${product.type}", "${escapedName}", ${product.price_vnd}, ${product.quantity}, "${escapedCode}", "${escapedImage}")'>
            <div class="flex justify-between items-center">
                <div class="flex-1">
                    <p class="font-medium text-sm">${product.name}</p>
                    <p class="text-xs text-gray-600">
                        <span class="px-2 py-0.5 rounded-full ${product.type === 'painting' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'}">${product.type === 'painting' ? 'Tranh' : 'Vật tư'}</span>
                        ${code ? `| Mã: ${code}` : ''} | Tồn: ${product.quantity} | Giá: ${parseFloat(product.price_vnd).toLocaleString('vi-VN')}đ
                    </p>
                </div>
                <i class="fas fa-plus text-blue-600"></i>
            </div>
        </div>
        `;
    }).join('');
    
    container.classList.remove('hidden');
}

function selectProduct(id, type, name, price, maxQty, code = '', image = '') {
    addExchangeProduct(id, type, name, price, maxQty, code, image);
    document.getElementById('product-search').value = '';
    document.getElementById('product-suggestions').classList.add('hidden');
}

// Hide suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('#product-search') && !e.target.closest('#product-suggestions')) {
        document.getElementById('product-suggestions').classList.add('hidden');
    }
});

function addExchangeProduct(id, type, name, price, maxQty, code = '', image = '') {
    const existing = exchangeProducts.find(p => p.id === id && p.type === type);
    if (existing) {
        showNotification('Sản phẩm đã được thêm', 'warning');
        return;
    }
    
    // Ensure maxQty is a valid number
    const availableQty = parseInt(maxQty) || 0;
    
    if (availableQty <= 0) {
        showNotification('Sản phẩm này đã hết hàng', 'error');
        return;
    }
    
    exchangeProducts.push({
        id, 
        type, 
        name,
        code,
        image,
        price, 
        defaultPrice: price,
        maxQty: availableQty, 
        quantity: 1,
        discount: 0,
        supplyId: null,
        supplyName: '',
        supplyLength: 0
    });
    renderExchangeProducts();
    updateSummary();
}

function removeExchangeProduct(index) {
    exchangeProducts.splice(index, 1);
    renderExchangeProducts();
    updateSummary();
}

function renderExchangeProducts() {
    const container = document.getElementById('exchange-products-container');
    if (exchangeProducts.length === 0) {
        container.innerHTML = '<tr><td colspan="8" class="text-center text-gray-500 py-8"><i class="fas fa-box-open text-3xl mb-2 text-gray-400 block"></i><p class="text-sm">Chưa có sản phẩm đổi</p></td></tr>';
        return;
    }
    
    container.innerHTML = exchangeProducts.map((product, index) => {
        const finalPrice = product.price * (1 - (product.discount || 0) / 100);
        const stockWarning = product.quantity > product.maxQty;
        const rowClass = stockWarning ? 'bg-red-50 border-l-4 border-red-500' : 'hover:bg-blue-50 border-l-4 border-transparent';
        
        return `
        <tr class="${rowClass} border-b transition-colors">
            <td class="px-3 py-3">
                ${product.image ? 
                    `<img src="/storage/${product.image}" class="w-14 h-14 object-cover rounded-lg shadow-sm border-2 border-gray-200 cursor-pointer hover:opacity-80 transition-opacity" alt="${product.name}" onclick="showImageModal('/storage/${product.image}', '${product.name.replace(/'/g, "\\'")}')">` : 
                    '<div class="w-14 h-14 bg-gradient-to-br from-gray-200 to-gray-300 rounded-lg flex items-center justify-center shadow-sm"><i class="fas fa-image text-gray-400 text-xl"></i></div>'}
            </td>
            <td class="px-3 py-3">
                <div class="text-sm font-semibold text-gray-800">${product.name}</div>
                ${product.code ? `<div class="text-xs text-gray-500 mt-1"><i class="fas fa-barcode mr-1"></i>${product.code}</div>` : ''}
                <div class="mt-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${product.maxQty > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                        <i class="fas fa-box mr-1"></i>Tồn: ${product.maxQty}
                    </span>
                </div>
            </td>
            <td class="px-3 py-3">
                <div class="relative">
                    <input type="text" 
                           id="supply-search-${index}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           placeholder="Tìm vật tư..."
                           value="${product.supplyName || ''}"
                           oninput="searchSupplyForExchange(${index}, this.value)"
                           autocomplete="off">
                    <div id="supply-suggestions-${index}" class="absolute z-20 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto hidden"></div>
                </div>
            </td>
            <td class="px-3 py-3 text-center">
                <input type="number" 
                       class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-center text-sm font-medium focus:ring-2 focus:ring-blue-500"
                       min="0" 
                       step="0.1"
                       value="${product.supplyLength || 0}"
                       onchange="updateExchangeSupplyLength(${index}, this.value)"
                       placeholder="0">
            </td>
            <td class="px-3 py-3 text-center">
                <input type="number" 
                       class="w-20 px-3 py-2 border ${stockWarning ? 'border-red-500 bg-red-50' : 'border-gray-300'} rounded-lg text-center text-sm font-medium focus:ring-2 focus:ring-blue-500"
                       min="1" 
                       max="${product.maxQty}" 
                       value="${product.quantity}"
                       onchange="updateExchangeQty(${index}, this.value)">
                ${stockWarning ? '<div class="text-xs text-red-600 font-medium mt-1"><i class="fas fa-exclamation-triangle mr-1"></i>Vượt tồn!</div>' : ''}
            </td>
            <td class="px-3 py-3 text-right">
                <input type="number" 
                       class="w-32 px-3 py-2 border border-gray-300 rounded-lg text-right text-sm font-medium focus:ring-2 focus:ring-blue-500"
                       min="0" 
                       step="1000"
                       value="${product.price}"
                       onchange="updateExchangePrice(${index}, this.value)">
                <div class="text-xs text-gray-500 mt-1">${finalPrice.toLocaleString('vi-VN')}đ</div>
            </td>
            <td class="px-3 py-3 text-center">
                <input type="number" 
                       class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-center text-sm font-medium focus:ring-2 focus:ring-blue-500"
                       min="0" 
                       max="100"
                       value="${product.discount || 0}"
                       onchange="updateExchangeDiscount(${index}, this.value)">
            </td>
            <td class="px-3 py-3 text-center">
                <button type="button" 
                        onclick="removeExchangeProduct(${index})" 
                        class="text-red-600 hover:text-red-800 hover:bg-red-100 p-2 rounded-lg transition-colors">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>
        <input type="hidden" name="exchange_items[${index}][item_type]" value="${product.type}">
        <input type="hidden" name="exchange_items[${index}][item_id]" value="${product.id}">
        <input type="hidden" name="exchange_items[${index}][quantity]" value="${product.quantity}">
        <input type="hidden" name="exchange_items[${index}][unit_price]" value="${finalPrice}">
        <input type="hidden" name="exchange_items[${index}][discount_percent]" value="${product.discount || 0}">
        ${product.supplyId ? `<input type="hidden" name="exchange_items[${index}][supply_id]" value="${product.supplyId}">` : ''}
        ${product.supplyLength ? `<input type="hidden" name="exchange_items[${index}][supply_length]" value="${product.supplyLength}">` : ''}
        `;
    }).join('');
}

function updateExchangeQty(index, qty) {
    exchangeProducts[index].quantity = parseInt(qty) || 1;
    renderExchangeProducts();
    updateSummary();
}

function updateExchangePrice(index, price) {
    exchangeProducts[index].price = parseFloat(price) || 0;
    renderExchangeProducts();
    updateSummary();
}

function updateExchangeDiscount(index, discount) {
    exchangeProducts[index].discount = parseFloat(discount) || 0;
    renderExchangeProducts();
    updateSummary();
}

function updateExchangeFrameQty(index, frameQty) {
    exchangeProducts[index].frameQty = parseInt(frameQty) || 0;
    renderExchangeProducts();
}

function updateExchangeSupplyLength(index, length) {
    exchangeProducts[index].supplyLength = parseFloat(length) || 0;
    renderExchangeProducts();
}

let supplySearchTimeout;
function searchSupplyForExchange(index, query) {
    clearTimeout(supplySearchTimeout);
    const container = document.getElementById(`supply-suggestions-${index}`);
    
    if (query.length < 1) {
        container.classList.add('hidden');
        return;
    }
    
    supplySearchTimeout = setTimeout(() => {
        fetch(`{{ route('sales.api.search.supplies') }}?q=${encodeURIComponent(query)}`)
            .then(r => r.json())
            .then(supplies => {
                if (supplies.length === 0) {
                    container.classList.add('hidden');
                    return;
                }
                
                container.innerHTML = supplies.map(supply => `
                    <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b last:border-b-0 transition-colors" 
                         onclick="selectSupplyForExchange(${index}, ${supply.id}, '${supply.name.replace(/'/g, "\\'")}')">
                        <div class="text-sm font-medium text-gray-800">${supply.name}</div>
                        <div class="text-xs text-gray-500">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">
                                <i class="fas fa-box mr-1"></i>Tồn: ${supply.quantity} ${supply.unit || 'm'}
                            </span>
                        </div>
                    </div>
                `).join('');
                
                container.classList.remove('hidden');
            })
            .catch(err => console.error(err));
    }, 300);
}

function selectSupplyForExchange(index, supplyId, supplyName) {
    exchangeProducts[index].supplyId = supplyId;
    exchangeProducts[index].supplyName = supplyName;
    exchangeProducts[index].supplyLength = 1; // Default length
    renderExchangeProducts();
    document.getElementById(`supply-suggestions-${index}`).classList.add('hidden');
}

// Hide supply suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('[id^="supply-search-"]') && !e.target.closest('[id^="supply-suggestions-"]')) {
        document.querySelectorAll('[id^="supply-suggestions-"]').forEach(el => {
            el.classList.add('hidden');
        });
    }
});

function updateSummary() {
    // Calculate return amount
    const returnInputs = document.querySelectorAll('.return-qty');
    let returnQty = 0;
    let returnValue = 0;
    
    returnInputs.forEach(input => {
        const qty = parseInt(input.value) || 0;
        const price = parseFloat(input.dataset.price) || 0;
        returnQty += qty;
        returnValue += qty * price;
    });
    
    // Calculate actual refund (limited by paid amount)
    const paidAmount = saleData ? parseFloat(saleData.paid_amount) : 0;
    const actualRefund = Math.min(returnValue, paidAmount);
    
    document.getElementById('summary-return-qty').textContent = returnQty;
    document.getElementById('summary-return-value').textContent = returnValue.toLocaleString('vi-VN') + 'đ';
    document.getElementById('summary-return-amount').textContent = actualRefund.toLocaleString('vi-VN') + 'đ';
    
    // Update refund note
    if (returnValue > paidAmount && paidAmount > 0) {
        document.getElementById('refund-note').textContent = 'Giới hạn bởi số tiền đã thanh toán (' + paidAmount.toLocaleString('vi-VN') + 'đ)';
        document.getElementById('refund-note').classList.add('text-red-600', 'font-medium');
    } else {
        document.getElementById('refund-note').textContent = 'Tối đa bằng số tiền đã thanh toán';
        document.getElementById('refund-note').classList.remove('text-red-600', 'font-medium');
    }
    
    // Calculate exchange amount if type is exchange
    const type = document.getElementById('return-type').value;
    if (type === 'exchange') {
        let exchangeQty = 0;
        let exchangeAmount = 0;
        
        exchangeProducts.forEach(product => {
            exchangeQty += product.quantity;
            const finalPrice = product.price * (1 - (product.discount || 0) / 100);
            exchangeAmount += product.quantity * finalPrice;
        });
        
        document.getElementById('exchange-summary').classList.remove('hidden');
        document.getElementById('summary-exchange-qty').textContent = exchangeQty;
        document.getElementById('summary-exchange-amount').textContent = exchangeAmount.toLocaleString('vi-VN') + 'đ';
        
        // Calculate difference based on actual credit (what customer paid)
        const difference = exchangeAmount - actualRefund;
        const diffEl = document.getElementById('summary-difference');
        diffEl.textContent = Math.abs(difference).toLocaleString('vi-VN') + 'đ';
        
        if (difference > 0) {
            diffEl.className = 'font-bold text-lg text-red-600';
            diffEl.textContent = '+' + diffEl.textContent + ' (Khách trả thêm)';
        } else if (difference < 0) {
            diffEl.className = 'font-bold text-lg text-green-600';
            diffEl.textContent = '-' + diffEl.textContent + ' (Hoàn lại)';
        } else {
            diffEl.className = 'font-bold text-lg text-gray-600';
            diffEl.textContent = '0đ (Ngang giá)';
        }
    } else {
        document.getElementById('exchange-summary').classList.add('hidden');
    }
}

function updateReturnType() {
    const type = document.getElementById('return-type').value;
    const exchangeSection = document.getElementById('exchange-section');
    
    if (type === 'exchange') {
        exchangeSection.classList.remove('hidden');
    } else {
        exchangeSection.classList.add('hidden');
        exchangeProducts = [];
        renderExchangeProducts();
    }
    
    updateSummary();
}

function validateForm(event) {
    const type = document.getElementById('return-type').value;
    
    // Validate: Must have return items
    const returnInputs = document.querySelectorAll('.return-qty');
    let hasReturnItems = false;
    returnInputs.forEach(input => {
        if (parseInt(input.value) > 0) {
            hasReturnItems = true;
        }
    });
    
    if (!hasReturnItems) {
        event.preventDefault();
        showNotification('Vui lòng chọn ít nhất một sản phẩm để trả', 'error');
        return false;
    }
    
    // Validate: If exchange type, must have exchange products
    if (type === 'exchange') {
        if (exchangeProducts.length === 0) {
            event.preventDefault();
            showNotification('Vui lòng chọn sản phẩm để đổi', 'error');
            return false;
        }
        
        // Validate: Check inventory for each exchange product
        let hasInventoryError = false;
        exchangeProducts.forEach(product => {
            if (product.quantity > product.maxQty) {
                hasInventoryError = true;
                showNotification(`Sản phẩm "${product.name}" không đủ tồn kho. Tồn: ${product.maxQty}, Yêu cầu: ${product.quantity}`, 'error');
            }
        });
        
        if (hasInventoryError) {
            event.preventDefault();
            return false;
        }
    }
    
    return true;
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transform transition-all duration-300 ${
        type === 'error' ? 'bg-red-500 text-white' :
        type === 'warning' ? 'bg-yellow-500 text-white' :
        type === 'success' ? 'bg-green-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center gap-3">
            <i class="fas ${
                type === 'error' ? 'fa-exclamation-circle' :
                type === 'warning' ? 'fa-exclamation-triangle' :
                type === 'success' ? 'fa-check-circle' :
                'fa-info-circle'
            }"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

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
