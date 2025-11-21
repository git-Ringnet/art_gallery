@extends('layouts.app')

@section('title', 'Tạo phiếu đổi/trả hàng')
@section('page-title', 'Tạo phiếu đổi/trả hàng')
@section('page-description', 'Tạo phiếu đổi hoặc trả hàng mới')

@section('content')
<x-alert />

<form action="{{ route('returns.store') }}" method="POST" id="return-form" onsubmit="return validateForm(event)">
    @csrf
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Left Column - Search Invoice -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-lg p-4 mb-4">
                <h3 class="text-base font-semibold mb-3">Tìm hóa đơn gốc</h3>
                
                <div class="flex gap-2">
                    <div class="flex-1">
                        <input type="text" id="invoice-search" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Nhập mã hóa đơn...">
                    </div>
                    <button type="button" onclick="searchInvoice()" class="bg-blue-600 text-white px-4 py-1.5 text-sm rounded-lg hover:bg-blue-700">
                        <i class="fas fa-search mr-1"></i>Tìm
                    </button>
                </div>
                
                <div id="invoice-info" class="hidden mt-3 p-3 bg-gray-50 rounded-lg">
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div><span class="text-gray-600">Mã HD:</span> <span id="inv-code" class="font-medium"></span></div>
                        <div><span class="text-gray-600">Ngày:</span> <span id="inv-date"></span></div>
                        <div colspan="2"><span class="text-gray-600">Khách hàng:</span> <span id="inv-customer" class="font-medium"></span></div>
                        <div class="border-t pt-1.5 mt-1.5 col-span-2">
                            <div class="bg-white p-2 rounded border border-gray-200">
                                <!-- Tổng HD -->
                                <div class="flex justify-between mb-1">
                                    <span class="text-gray-600 font-medium">Tổng cộng:</span>
                                    <div class="text-right">
                                        <div id="inv-total-usd" class="font-bold text-blue-600">$0.00</div>
                                        <div id="inv-total-vnd" class="text-xs text-gray-500">≈ 0đ</div>
                                    </div>
                                </div>
                                
                                <!-- Đã thanh toán -->
                                <div class="flex justify-between mb-1 border-t pt-1">
                                    <span class="text-green-600 font-medium">Đã Thanh toán:</span>
                                    <div class="text-right">
                                        <div id="inv-paid-usd" class="font-bold text-green-600">$0.00</div>
                                        <div id="inv-paid-vnd" class="text-xs text-gray-500">≈ 0đ</div>
                                    </div>
                                </div>
                                
                                <!-- Còn nợ -->
                                <div class="flex justify-between border-t pt-1">
                                    <span class="text-red-600 font-medium">Còn nợ:</span>
                                    <div class="text-right">
                                        <div id="inv-debt-usd" class="font-bold text-red-600">$0.00</div>
                                        <div id="inv-debt-vnd" class="text-xs text-gray-500">≈ 0đ</div>
                                    </div>
                                </div>
                                
                                <!-- Tỷ giá -->
                                <div class="mt-2 pt-2 border-t border-gray-200 text-xs text-gray-500">
                                    <i class="fas fa-exchange-alt mr-1"></i>
                                    Tỷ giá: <span id="inv-exchange-rate" class="font-medium">1 USD = 25,000đ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 p-1.5 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        <span>Chỉ hoàn tối đa số tiền Khách hàng đã Thanh toán (theo USD)</span>
                    </div>
                </div>
                
                <input type="hidden" name="sale_id" id="sale-id">
                <input type="hidden" name="customer_id" id="customer-id">
            </div>
            
            <!-- Products List - Return Items -->
            <div class="bg-white rounded-xl shadow-lg p-4 mb-4">
                <h3 class="text-base font-semibold mb-3">Sản phẩm trả lại</h3>
                
                <div id="products-container">
                    <div class="text-center text-gray-500 py-6">
                        <i class="fas fa-box-open text-3xl mb-2"></i>
                        <p class="text-sm">Vui lòng tìm hóa đơn trước</p>
                    </div>
                </div>
            </div>
            
            <!-- Exchange Products - Only show when type is exchange -->
            <div id="exchange-section" class="bg-white rounded-xl shadow-lg p-4 hidden">
                <h3 class="text-base font-semibold mb-3">Sản phẩm đổi mới</h3>
                
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tìm sản phẩm</label>
                    <div class="relative">
                        <input type="text" 
                               id="product-search" 
                               class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg" 
                               placeholder="Nhập tên sản phẩm..."
                               autocomplete="off"
                               oninput="searchProductsAuto()">
                        <div id="product-suggestions" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto hidden"></div>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-xs border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-blue-50 to-indigo-50 border-b-2 border-blue-200">
                                <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700">Ảnh</th>
                                <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700">Mã SP</th>
                                <th class="px-2 py-2 text-center text-xs font-semibold text-gray-700">SL</th>
                                <th class="px-2 py-2 text-right text-xs font-semibold text-gray-700">Giá</th>
                                <th class="px-2 py-2 text-center text-xs font-semibold text-gray-700">GG%</th>
                                <th class="px-2 py-2 text-center text-xs font-semibold text-gray-700">Xóa</th>
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
            <div class="bg-white rounded-xl shadow-lg p-4 sticky top-6">
                <h3 class="text-base font-semibold mb-3">Thông tin đổi/trả</h3>
                
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Loại *</label>
                        <select name="type" id="return-type" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required onchange="updateReturnType()">
                            <option value="return">Trả hàng</option>
                            <option value="exchange">Đổi hàng</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Ngày *</label>
                        <input type="date" name="return_date" value="{{ date('Y-m-d') }}" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Lý do</label>
                        <textarea name="reason" rows="2" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Lý do..."></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Ghi chú</label>
                        <textarea name="notes" rows="2" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Ghi chú..."></textarea>
                    </div>
                    
                    <div class="border-t pt-3">
                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-600">SL trả:</span>
                                <span id="summary-return-qty" class="font-medium">0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Giá trị:</span>
                                <div class="text-right">
                                    <div id="summary-return-value-usd" class="font-bold text-blue-600">$0.00</div>
                                    <div id="summary-return-value-vnd" class="text-xs text-gray-500">≈ 0đ</div>
                                </div>
                            </div>
                            <div class="flex justify-between border-t pt-1.5">
                                <span class="text-gray-700 font-semibold text-xs">Tiền hoàn:</span>
                                <div class="text-right">
                                    <div id="summary-return-amount-usd" class="font-bold text-green-600">$0.00</div>
                                    <div id="summary-return-amount-vnd" class="text-xs text-gray-500">≈ 0đ</div>
                                </div>
                            </div>
                            <div class="text-xs text-gray-500 italic">
                                <i class="fas fa-info-circle mr-1"></i>
                                <span id="refund-note">Tối đa = đã Thanh toán (USD)</span>
                            </div>
                            <div id="exchange-summary" class="hidden">
                                <div class="flex justify-between border-t pt-1.5 mt-1.5">
                                    <span class="text-gray-600">SL đổi:</span>
                                    <span id="summary-exchange-qty" class="font-medium">0</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tiền đổi:</span>
                                    <div class="text-right">
                                        <div id="summary-exchange-amount-usd" class="font-bold text-blue-600">$0.00</div>
                                        <div id="summary-exchange-amount-vnd" class="text-xs text-gray-500">≈ 0đ</div>
                                    </div>
                                </div>
                                <div class="flex justify-between border-t pt-1.5 mt-1.5">
                                    <span class="text-gray-700 font-medium">Chênh lệch:</span>
                                    <div class="text-right">
                                        <div id="summary-difference-usd" class="font-bold text-sm">$0.00</div>
                                        <div id="summary-difference-vnd" class="text-xs text-gray-500">≈ 0đ</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Section (chỉ hiện khi đổi hàng và khách phải trả thêm) -->
                    <!-- Payment Section (chỉ hiện khi đổi hàng và khách phải trả thêm) -->
                    <div id="payment-section" class="hidden mt-4 border-t pt-4">
                        <h4 class="font-semibold text-gray-800 mb-3 text-sm">Thanh toán chênh lệch</h4>
                        
                        <!-- Số tiền cần thanh toán -->
                        <div class="bg-red-50 border border-red-100 rounded-lg p-3 mb-4">
                            <div class="text-xs text-gray-600">Số tiền còn thanh toán:</div>
                            <div class="text-lg font-bold text-red-600" id="payment-due-usd">$0.00</div>
                            <div class="text-xs text-red-500" id="payment-due-vnd">0đ</div>
                        </div>

                        <!-- Tỷ giá -->
                        <div class="bg-yellow-50 border border-yellow-100 rounded-lg p-3 mb-4">
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                <i class="fas fa-exchange-alt mr-1"></i>Tỷ giá hiện tại (VND/USD)
                            </label>
                            <input type="text" id="payment-exchange-rate" name="payment_exchange_rate" 
                                   class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                   value="25,000" oninput="formatNumber(this); updatePaymentCalculations()" placeholder="25,000">
                            <div class="text-xs text-yellow-700 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>Tỷ giá ban đầu: <span id="original-exchange-rate">25.000</span> VND/USD
                            </div>
                        </div>

                        <!-- Inputs -->
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Trả bằng USD</label>
                                <input type="text" id="payment-usd" name="payment_usd" 
                                       class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="0.00" oninput="formatUSD(this); updatePaymentCalculations()">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Trả bằng VND</label>
                                <input type="text" id="payment-vnd" name="payment_vnd" 
                                       class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="0" oninput="formatNumber(this); updatePaymentCalculations()">
                            </div>
                        </div>

                        <!-- Tổng quy đổi -->
                        <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 mb-3">
                            <div class="text-xs font-medium text-blue-800 mb-2">Tổng thanh toán quy đổi</div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs text-gray-600">Quy đổi USD:</span>
                                <span class="font-bold text-blue-700 text-sm" id="total-converted-usd">$0.00</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-600">Quy đổi VND:</span>
                                <span class="font-medium text-gray-700 text-xs" id="total-converted-vnd">0đ</span>
                            </div>
                        </div>
                        
                        <!-- Phương thức thanh toán -->
                        <div class="mb-3">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Phương thức thanh toán <span class="text-red-500">*</span></label>
                            <select name="payment_method" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500">
                                <option value="cash">Tiền mặt</option>
                                <option value="bank_transfer">Chuyển khoản</option>
                                <option value="credit_card">Thẻ tín dụng</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex gap-2 pt-3">
                        <button type="submit" class="flex-1 bg-blue-600 text-white py-1.5 px-3 text-sm rounded-lg hover:bg-blue-700">
                            <i class="fas fa-save mr-1"></i>Lưu
                        </button>
                        <a href="{{ route('returns.index') }}" class="flex-1 bg-gray-500 text-white py-1.5 px-3 text-sm rounded-lg hover:bg-gray-600 text-center">
                            <i class="fas fa-times mr-1"></i>Hủy
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
    
    // Display USD (chính) và VND (tham khảo)
    const totalUsd = parseFloat(sale.total_usd || 0);
    const paidUsd = parseFloat(sale.paid_usd || 0);
    const debtUsd = parseFloat(sale.debt_usd || 0);
    
    const totalVnd = parseFloat(sale.total_vnd || 0);
    const paidVnd = parseFloat(sale.paid_amount || 0);
    const debtVnd = parseFloat(sale.debt_amount || 0);
    
    const exchangeRate = parseFloat(sale.exchange_rate || 25000);
    
    // Total
    document.getElementById('inv-total-usd').textContent = '$' + totalUsd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('inv-total-vnd').textContent = '≈ ' + totalVnd.toLocaleString('vi-VN') + 'đ';
    
    // Paid
    document.getElementById('inv-paid-usd').textContent = '$' + paidUsd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('inv-paid-vnd').textContent = '≈ ' + paidVnd.toLocaleString('vi-VN') + 'đ';
    
    // Debt
    document.getElementById('inv-debt-usd').textContent = '$' + debtUsd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('inv-debt-vnd').textContent = '≈ ' + debtVnd.toLocaleString('vi-VN') + 'đ';
    
    // Exchange rate
    document.getElementById('inv-exchange-rate').textContent = '1 USD = ' + exchangeRate.toLocaleString('vi-VN') + 'đ';
    
    document.getElementById('sale-id').value = sale.id;
    document.getElementById('customer-id').value = sale.customer_id;
}

function displayProducts(items, returnedQty) {
    const container = document.getElementById('products-container');
    container.innerHTML = '';
    
    const exchangeRate = saleData ? parseFloat(saleData.exchange_rate || 25000) : 25000;
    
    let hasItems = false;
    items.forEach(item => {
        const available = item.quantity - (returnedQty[item.id] || 0);
        if (available <= 0) return;
        
        hasItems = true;
        const div = document.createElement('div');
        div.className = 'border rounded-lg p-3 mb-2';
        
        // Calculate USD price
        const priceVnd = parseFloat(item.unit_price);
        const priceUsd = priceVnd / exchangeRate;
        
        // Build image HTML
        const imageHtml = item.painting_image ? 
            `<img src="/storage/${item.painting_image}" alt="${item.item_name}" class="w-16 h-16 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity flex-shrink-0" onclick="showImageModal('/storage/${item.painting_image}', '${item.item_name.replace(/'/g, "\\'")}')">` :
            '<div class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center flex-shrink-0"><i class="fas fa-image text-gray-400 text-sm"></i></div>';
        
        div.innerHTML = `
            <div class="flex items-start gap-3 mb-1.5">
                ${imageHtml}
                <div class="flex-1">
                    <h4 class="font-medium text-sm">${item.item_name}</h4>
                    <p class="text-xs text-gray-600">Đã mua: ${item.quantity} | Đã trả: ${returnedQty[item.id] || 0} | Còn lại: ${available}</p>
                    <div class="text-xs mt-1">
                        <div class="font-bold text-blue-600">Đơn giá: $${priceUsd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                        <div class="text-gray-500">≈ ${priceVnd.toLocaleString('vi-VN')}đ</div>
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    <label class="text-xs text-gray-600">Số lượng trả:</label>
                    <input type="number" 
                           name="items[${item.id}][quantity]" 
                           class="w-20 px-2 py-1 text-sm border rounded text-center return-qty"
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
        container.innerHTML = '<div class="text-center text-gray-500 py-6"><i class="fas fa-info-circle text-3xl mb-2"></i><p class="text-sm">Tất cả sản phẩm đã được trả</p></div>';
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
    
    const exchangeRate = saleData ? parseFloat(saleData.exchange_rate || 25000) : 25000;
    
    container.innerHTML = products.map(product => {
        const code = product.code || '';
        const image = product.image || '';
        const escapedName = product.name.replace(/'/g, "\\'").replace(/"/g, '&quot;');
        const escapedCode = code.replace(/'/g, "\\'").replace(/"/g, '&quot;');
        const escapedImage = image.replace(/'/g, "\\'").replace(/"/g, '&quot;');
        
        // Handle price - show original currency first (bold), converted currency in parentheses
        let priceDisplay = '';
        let priceVnd = 0;
        let isUsd = false;
        let priceUsdVal = 0;
        
        if (product.price_usd) {
            // Painting with USD price - show USD first (bold)
            isUsd = true;
            priceUsdVal = parseFloat(product.price_usd);
            priceVnd = priceUsdVal * exchangeRate;
            priceDisplay = `<strong>$${priceUsdVal.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong> (≈${priceVnd.toLocaleString('vi-VN')}đ)`;
        } else if (product.price_vnd) {
            // Supply or item with VND price - show VND first (bold)
            isUsd = false;
            priceVnd = parseFloat(product.price_vnd);
            priceUsdVal = priceVnd / exchangeRate;
            priceDisplay = `<strong>${priceVnd.toLocaleString('vi-VN')}đ</strong> (≈$${priceUsdVal.toLocaleString('en-US', {minimumFractionDigits: 2})})`;
        }
        
        return `
        <div class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b last:border-b-0" onclick='selectProduct(${product.id}, "${product.type}", "${escapedName}", ${priceVnd}, ${product.quantity}, "${escapedCode}", "${escapedImage}", ${isUsd}, ${priceUsdVal})'>
            <div class="flex justify-between items-center">
                <div class="flex-1">
                    <p class="font-medium text-sm">${product.name}</p>
                    <p class="text-xs text-gray-600">
                        <span class="px-2 py-0.5 rounded-full ${product.type === 'painting' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'}">${product.type === 'painting' ? 'Tranh' : 'Vật tư'}</span>
                        ${code ? `| Mã: ${code}` : ''} | Tồn: ${product.quantity} | Giá: ${priceDisplay}
                    </p>
                </div>
                <i class="fas fa-plus text-blue-600"></i>
            </div>
        </div>
        `;
    }).join('');
    
    container.classList.remove('hidden');
}

function selectProduct(id, type, name, price, maxQty, code = '', image = '', isUsd = false, priceUsd = 0) {
    addExchangeProduct(id, type, name, price, maxQty, code, image, isUsd, priceUsd);
    document.getElementById('product-search').value = '';
    document.getElementById('product-suggestions').classList.add('hidden');
}

// Hide suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('#product-search') && !e.target.closest('#product-suggestions')) {
        document.getElementById('product-suggestions').classList.add('hidden');
    }
});

function addExchangeProduct(id, type, name, price, maxQty, code = '', image = '', isUsd = false, priceUsd = 0) {
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
        priceUsd: priceUsd,
        defaultPriceUsd: priceUsd,
        isUsd: isUsd,
        maxQty: availableQty, 
        quantity: 1,
        discount: 0
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
        container.innerHTML = '<tr><td colspan="6" class="text-center text-gray-500 py-6"><i class="fas fa-box-open text-2xl mb-2 text-gray-400 block"></i><p class="text-xs">Chưa có sản phẩm đổi</p></td></tr>';
        return;
    }
    
    const exchangeRate = saleData ? parseFloat(saleData.exchange_rate || 25000) : 25000;
    
    container.innerHTML = exchangeProducts.map((product, index) => {
        const finalPrice = product.price * (1 - (product.discount || 0) / 100);
        const stockWarning = product.quantity > product.maxQty;
        const rowClass = stockWarning ? 'bg-red-50 border-l-2 border-red-500' : 'hover:bg-blue-50 border-l-2 border-transparent';
        
        let priceInputHtml = '';
        let priceDisplayHtml = '';
        
        if (product.isUsd) {
            const finalPriceUsd = product.priceUsd * (1 - (product.discount || 0) / 100);
            priceInputHtml = `<input type="number" 
                       class="w-28 px-2 py-1.5 border border-gray-300 rounded text-right text-xs font-medium focus:ring-2 focus:ring-blue-500"
                       min="0" 
                       step="0.01"
                       value="${product.priceUsd}"
                       onchange="updateExchangePriceUsd(${index}, this.value)">`;
            priceDisplayHtml = `<div class="text-xs text-gray-500 mt-0.5">≈ ${finalPrice.toLocaleString('vi-VN')}đ</div>`;
        } else {
            const finalPriceUsd = finalPrice / exchangeRate;
            priceInputHtml = `<input type="number" 
                       class="w-28 px-2 py-1.5 border border-gray-300 rounded text-right text-xs font-medium focus:ring-2 focus:ring-blue-500"
                       min="0" 
                       step="1000"
                       value="${product.price}"
                       onchange="updateExchangePrice(${index}, this.value)">`;
            priceDisplayHtml = `<div class="text-xs text-gray-500 mt-0.5">≈ $${finalPriceUsd.toLocaleString('en-US', {minimumFractionDigits: 2})}</div>`;
        }
        
        return `
        <tr class="${rowClass} border-b transition-colors">
            <td class="px-2 py-2">
                ${product.image ? 
                    `<img src="/storage/${product.image}" class="w-12 h-12 object-cover rounded shadow-sm border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity" alt="${product.name}" onclick="showImageModal('/storage/${product.image}', '${product.name.replace(/'/g, "\\'")}')">` : 
                    '<div class="w-12 h-12 bg-gradient-to-br from-gray-200 to-gray-300 rounded flex items-center justify-center shadow-sm"><i class="fas fa-image text-gray-400 text-sm"></i></div>'}
            </td>
            <td class="px-2 py-2">
                <div class="text-xs font-semibold text-gray-800">${product.name}</div>
                ${product.code ? `<div class="text-xs text-gray-500 mt-0.5"><i class="fas fa-barcode mr-1"></i>${product.code}</div>` : ''}
                <div class="mt-0.5">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium ${product.maxQty > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                        <i class="fas fa-box mr-1"></i>Tồn: ${product.maxQty}
                    </span>
                </div>
            </td>
            <td class="px-2 py-2 text-center">
                <input type="number" 
                       class="w-16 px-2 py-1.5 border ${stockWarning ? 'border-red-500 bg-red-50' : 'border-gray-300'} rounded text-center text-xs font-medium focus:ring-2 focus:ring-blue-500"
                       min="1" 
                       max="${product.maxQty}" 
                       value="${product.quantity}"
                       onchange="updateExchangeQty(${index}, this.value)">
                ${stockWarning ? '<div class="text-xs text-red-600 font-medium mt-0.5"><i class="fas fa-exclamation-triangle mr-1"></i>Vượt tồn!</div>' : ''}
            </td>
            <td class="px-2 py-2 text-right">
                ${priceInputHtml}
                ${priceDisplayHtml}
            </td>
            <td class="px-2 py-2 text-center">
                <input type="number" 
                       class="w-16 px-2 py-1.5 border border-gray-300 rounded text-center text-xs font-medium focus:ring-2 focus:ring-blue-500"
                       min="0" 
                       max="100"
                       value="${product.discount || 0}"
                       onchange="updateExchangeDiscount(${index}, this.value)">
            </td>
            <td class="px-2 py-2 text-center">
                <button type="button" 
                        onclick="removeExchangeProduct(${index})" 
                        class="text-red-600 hover:text-red-800 hover:bg-red-100 p-1.5 rounded transition-colors">
                    <i class="fas fa-trash-alt text-xs"></i>
                </button>
            </td>
        </tr>
        <input type="hidden" name="exchange_items[${index}][item_type]" value="${product.type}">
        <input type="hidden" name="exchange_items[${index}][item_id]" value="${product.id}">
        <input type="hidden" name="exchange_items[${index}][quantity]" value="${product.quantity}">
        <input type="hidden" name="exchange_items[${index}][unit_price]" value="${finalPrice}">
        <input type="hidden" name="exchange_items[${index}][discount_percent]" value="${product.discount || 0}">
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
    // Also update USD price for reference if needed
    const exchangeRate = saleData ? parseFloat(saleData.exchange_rate || 25000) : 25000;
    exchangeProducts[index].priceUsd = exchangeProducts[index].price / exchangeRate;
    
    renderExchangeProducts();
    updateSummary();
}

function updateExchangePriceUsd(index, priceUsd) {
    const exchangeRate = saleData ? parseFloat(saleData.exchange_rate || 25000) : 25000;
    exchangeProducts[index].priceUsd = parseFloat(priceUsd) || 0;
    exchangeProducts[index].price = exchangeProducts[index].priceUsd * exchangeRate;
    
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



function updateSummary() {
    if (!saleData) return;
    
    // Get exchange rate from sale
    const exchangeRate = parseFloat(saleData.exchange_rate || 25000);
    
    // Calculate return amount in VND first (from sale items which are in VND)
    const returnInputs = document.querySelectorAll('.return-qty');
    let returnQty = 0;
    let returnValueVnd = 0;
    
    returnInputs.forEach(input => {
        const qty = parseInt(input.value) || 0;
        const priceVnd = parseFloat(input.dataset.price) || 0;
        returnQty += qty;
        returnValueVnd += qty * priceVnd;
    });
    
    // Convert return value to USD
    const returnValueUsd = returnValueVnd / exchangeRate;
    
    // LOGIC ĐÚNG: Tính theo tỷ lệ đã trả (dùng USD)
    const paidUsd = saleData ? parseFloat(saleData.paid_usd || 0) : 0;
    const totalUsd = saleData ? parseFloat(saleData.total_usd || 0) : 0;
    
    // Tính tỷ lệ đã trả của hóa đơn
    const paidRatio = totalUsd > 0 ? (paidUsd / totalUsd) : 0;
    
    // Tính số tiền đã trả cho các sản phẩm đang trả (USD)
    const paidForReturnedItemsUsd = returnValueUsd * paidRatio;
    const paidForReturnedItemsVnd = paidForReturnedItemsUsd * exchangeRate;
    
    let actualRefundUsd = 0;
    let actualRefundVnd = 0;
    let exchangeAmountUsd = 0;
    let exchangeAmountVnd = 0;
    
    const type = document.getElementById('return-type').value;
    if (type === 'exchange') {
        let exchangeQty = 0;
        let totalExchangeVnd = 0;
        
        exchangeProducts.forEach(product => {
            exchangeQty += product.quantity;
            const finalPrice = product.price * (1 - (product.discount || 0) / 100);
            totalExchangeVnd += product.quantity * finalPrice;
        });
        
        const totalExchangeUsd = totalExchangeVnd / exchangeRate;
        
        // Tính chênh lệch giữa giá SP mới và số tiền đã trả cho SP cũ (USD)
        const differenceUsd = totalExchangeUsd - paidForReturnedItemsUsd;
        const differenceVnd = differenceUsd * exchangeRate;
        
        if (differenceUsd > 0) {
            // Khách trả thêm
            exchangeAmountUsd = differenceUsd;
            exchangeAmountVnd = differenceVnd;
            actualRefundUsd = 0;
            actualRefundVnd = 0;
        } else {
            // Hoàn lại khách
            exchangeAmountUsd = 0;
            exchangeAmountVnd = 0;
            actualRefundUsd = Math.abs(differenceUsd);
            actualRefundVnd = Math.abs(differenceVnd);
        }
        
        document.getElementById('exchange-summary').classList.remove('hidden');
        document.getElementById('summary-exchange-qty').textContent = exchangeQty;
        document.getElementById('summary-exchange-amount-usd').textContent = '$' + totalExchangeUsd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('summary-exchange-amount-vnd').textContent = '≈ ' + totalExchangeVnd.toLocaleString('vi-VN') + 'đ';
        
        // Update difference display
        if (actualRefundUsd > 0) {
            document.getElementById('summary-difference-usd').className = 'font-bold text-sm text-green-600';
            document.getElementById('summary-difference-usd').textContent = '$' + actualRefundUsd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' (Hoàn lại)';
            document.getElementById('summary-difference-vnd').textContent = '≈ ' + actualRefundVnd.toLocaleString('vi-VN') + 'đ';
        } else if (exchangeAmountUsd > 0) {
            document.getElementById('summary-difference-usd').className = 'font-bold text-sm text-red-600';
            document.getElementById('summary-difference-usd').textContent = '+$' + exchangeAmountUsd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' (Khách trả thêm)';
            document.getElementById('summary-difference-vnd').textContent = '≈ ' + exchangeAmountVnd.toLocaleString('vi-VN') + 'đ';
        } else {
            document.getElementById('summary-difference-usd').className = 'font-bold text-sm text-gray-600';
            document.getElementById('summary-difference-usd').textContent = '$0.00 (Ngang giá)';
            document.getElementById('summary-difference-vnd').textContent = '≈ 0đ';
        }
    } else {
        // Trả hàng thuần túy: Hoàn số tiền đã trả cho SP này
        actualRefundUsd = paidForReturnedItemsUsd;
        actualRefundVnd = paidForReturnedItemsVnd;
        document.getElementById('exchange-summary').classList.add('hidden');
    }
    
    // Update summary display
    document.getElementById('summary-return-qty').textContent = returnQty;
    document.getElementById('summary-return-value-usd').textContent = '$' + returnValueUsd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('summary-return-value-vnd').textContent = '≈ ' + returnValueVnd.toLocaleString('vi-VN') + 'đ';
    document.getElementById('summary-return-amount-usd').textContent = '$' + actualRefundUsd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('summary-return-amount-vnd').textContent = '≈ ' + actualRefundVnd.toLocaleString('vi-VN') + 'đ';
    
    // Update refund note
    const paidPercent = (paidRatio * 100).toFixed(1);
    if (type === 'exchange') {
        if (actualRefundUsd > 0) {
            document.getElementById('refund-note').textContent = `Hoàn $${actualRefundUsd.toLocaleString('en-US', {minimumFractionDigits: 2})} (Đã trả ${paidPercent}%)`;
            document.getElementById('refund-note').className = 'text-xs text-green-600 italic font-medium';
        } else if (exchangeAmountUsd > 0) {
            document.getElementById('refund-note').textContent = `Khách trả thêm $${exchangeAmountUsd.toLocaleString('en-US', {minimumFractionDigits: 2})} (Chênh lệch)`;
            document.getElementById('refund-note').className = 'text-xs text-blue-600 italic font-medium';
        } else {
            document.getElementById('refund-note').textContent = 'Ngang giá';
            document.getElementById('refund-note').className = 'text-xs text-green-600 italic font-medium';
        }
    } else {
        if (actualRefundUsd > 0) {
            document.getElementById('refund-note').textContent = `Hoàn $${actualRefundUsd.toLocaleString('en-US', {minimumFractionDigits: 2})} (Đã trả ${paidPercent}% × $${returnValueUsd.toLocaleString('en-US', {minimumFractionDigits: 2})})`;
            document.getElementById('refund-note').className = 'text-xs text-green-600 italic font-medium';
        } else {
            document.getElementById('refund-note').textContent = 'Ch ua thanh toán trước đó';
            document.getElementById('refund-note').className = 'text-xs text-gray-500 italic';
        }
    }
    
    // Hiển thị payment section nếu đổi hàng và khách phải trả thêm
    const paymentSection = document.getElementById('payment-section');
    if (type === 'exchange' && exchangeAmountUsd > 0) {
        paymentSection.classList.remove('hidden');
        
        // Update payment due display
        document.getElementById('payment-due-usd').textContent = '$' + exchangeAmountUsd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('payment-due-vnd').textContent = exchangeAmountVnd.toLocaleString('vi-VN') + 'đ';
        
        // Store the due amount for calculation reference
        paymentSection.dataset.dueUsd = exchangeAmountUsd;
        paymentSection.dataset.dueVnd = exchangeAmountVnd;
        
        // Update original exchange rate display
        document.getElementById('original-exchange-rate').textContent = exchangeRate.toLocaleString('vi-VN');
        
        // Call calculation update
        updatePaymentCalculations();
    } else {
        paymentSection.classList.add('hidden');
    }
}

function updatePaymentCalculations() {
    const exchangeRate = parseFloat(document.getElementById('payment-exchange-rate').value.replace(/,/g, '')) || 25000;
    const usdAmount = parseFloat(document.getElementById('payment-usd').value.replace(/,/g, '')) || 0;
    const vndAmount = parseFloat(document.getElementById('payment-vnd').value.replace(/,/g, '')) || 0;
    
    const convertedUsd = usdAmount + (vndAmount / exchangeRate);
    const convertedVnd = (usdAmount * exchangeRate) + vndAmount;
    
    document.getElementById('total-converted-usd').textContent = '$' + convertedUsd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('total-converted-vnd').textContent = convertedVnd.toLocaleString('vi-VN') + 'đ';
}

function formatNumber(input) {
    let value = input.value.replace(/[^\d]/g, '');
    if (value) {
        input.value = parseInt(value).toLocaleString('en-US');
    } else {
        input.value = '';
    }
}

function formatUSD(input) {
    let value = input.value.replace(/[^0-9.]/g, '');
    const parts = value.split('.');
    
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }
    
    if (parts[0]) {
        parts[0] = parseInt(parts[0]).toLocaleString('en-US');
    }
    
    input.value = parts.join('.');
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
