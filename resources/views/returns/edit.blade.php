@extends('layouts.app')

@section('title', 'Chỉnh sửa phiếu đổi/trả hàng')
@section('page-title', 'Chỉnh sửa phiếu đổi/trả hàng')
@section('page-description', 'Chỉnh sửa thông tin phiếu đổi/trả hàng')

@section('content')
<x-alert />

<form action="{{ route('returns.update', $return->id) }}" method="POST" id="return-form" onsubmit="return validateForm(event)">
    @csrf
    @method('PUT')
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Left Column -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-lg p-4 mb-4">
                <h3 class="text-base font-semibold mb-3">Thông tin hóa đơn gốc</h3>
                
                <div class="p-3 bg-gray-50 rounded-lg">
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div><span class="text-gray-600">Mã HD:</span> <span class="font-medium">{{ $return->sale->invoice_code }}</span></div>
                        <div><span class="text-gray-600">Ngày:</span> <span>{{ $return->sale->sale_date->format('d/m/Y') }}</span></div>
                        <div><span class="text-gray-600">Khách hàng:</span> <span class="font-medium">{{ $return->customer->name }}</span></div>
                        <div class="border-t pt-1.5 mt-1.5">
                            <div class="flex justify-between mb-1">
                                <span class="text-gray-600">Tổng HD:</span>
                                <span class="font-medium">{{ number_format($return->sale->total_vnd, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="flex justify-between mb-1">
                                <span class="text-gray-600">Đã Thanh toán:</span>
                                <span class="font-medium text-green-600">{{ number_format($return->sale->paid_amount, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Còn nợ:</span>
                                <span class="font-medium text-red-600">{{ number_format($return->sale->debt_amount, 0, ',', '.') }}đ</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <input type="hidden" name="sale_id" id="sale-id" value="{{ $return->sale_id }}">
                <input type="hidden" name="customer_id" id="customer-id" value="{{ $return->customer_id }}">
            </div>
            
            <!-- Products List - Return Items -->
            <div class="bg-white rounded-xl shadow-lg p-4 mb-4">
                <h3 class="text-base font-semibold mb-3">Sản phẩm trả lại</h3>
                
                <div id="products-container">
                    @foreach($return->sale->items as $item)
                        @php
                            $returnedQty = \App\Models\ReturnItem::where('sale_item_id', $item->id)
                                ->whereHas('return', function($q) use ($return) {
                                    $q->whereIn('status', ['approved', 'completed'])
                                      ->where('id', '!=', $return->id);
                                })
                                ->sum('quantity');
                            $available = $item->quantity - $returnedQty;
                            $currentQty = $return->items->where('sale_item_id', $item->id)->first()->quantity ?? 0;
                            $itemName = $item->painting_id ? ($item->painting->name ?? 'N/A') : ($item->supply->name ?? 'N/A');
                            
                            $unitPrice = $item->price_vnd;
                            if ($item->discount_percent > 0) {
                                $unitPrice = $unitPrice * (1 - $item->discount_percent / 100);
                            }
                            if ($return->sale->discount_percent > 0) {
                                $unitPrice = $unitPrice * (1 - $return->sale->discount_percent / 100);
                            }
                        @endphp
                        
                        @if($available > 0 || $currentQty > 0)
                        <div class="border rounded-lg p-3 mb-2">
                            <div class="flex items-start gap-3 mb-1.5">
                                @if($item->painting_id && $item->painting && $item->painting->image)
                                    <img src="{{ asset('storage/' . $item->painting->image) }}" alt="{{ $item->painting->name }}" 
                                        class="w-16 h-16 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity flex-shrink-0"
                                        onclick="showImageModal('{{ asset('storage/' . $item->painting->image) }}', '{{ $item->painting->name }}')">
                                @else
                                    <div class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-image text-gray-400 text-sm"></i>
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <h4 class="font-medium text-sm">{{ $itemName }}</h4>
                                    <p class="text-xs text-gray-600">Đã mua: {{ $item->quantity }} | Đã trả: {{ $returnedQty }} | Còn lại: {{ $available }}</p>
                                    <p class="text-xs text-green-600">Đơn giá: {{ number_format($unitPrice, 0, ',', '.') }}đ</p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <label class="text-xs text-gray-600">Số lượng trả:</label>
                                    <input type="number" 
                                           name="items[{{ $item->id }}][quantity]" 
                                           class="w-20 px-2 py-1 text-sm border rounded text-center return-qty"
                                           min="0" 
                                           max="{{ $available + $currentQty }}" 
                                           value="{{ $currentQty }}"
                                           data-price="{{ $unitPrice }}"
                                           onchange="updateSummary()">
                                    <input type="hidden" name="items[{{ $item->id }}][sale_item_id]" value="{{ $item->id }}">
                                </div>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
            
            <!-- Exchange Products - Only show when type is exchange -->
            <div id="exchange-section" class="bg-white rounded-xl shadow-lg p-4 {{ $return->type == 'exchange' ? '' : 'hidden' }}">
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
                                <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700">VT</th>
                                <th class="px-2 py-2 text-center text-xs font-semibold text-gray-700">Mét</th>
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
                            <option value="return" {{ $return->type == 'return' ? 'selected' : '' }}>Trả hàng</option>
                            <option value="exchange" {{ $return->type == 'exchange' ? 'selected' : '' }}>Đổi hàng</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Ngày *</label>
                        <input type="date" name="return_date" value="{{ $return->return_date->format('Y-m-d') }}" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Lý do</label>
                        <textarea name="reason" rows="2" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Lý do...">{{ $return->reason }}</textarea>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Ghi chú</label>
                        <textarea name="notes" rows="2" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Ghi chú...">{{ $return->notes }}</textarea>
                    </div>
                    
                    <div class="border-t pt-3">
                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-600">SL trả:</span>
                                <span id="summary-return-qty" class="font-medium">0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Giá trị:</span>
                                <span id="summary-return-value" class="font-medium">0đ</span>
                            </div>
                            <div class="flex justify-between border-t pt-1.5">
                                <span class="text-gray-700 font-semibold text-xs">Tiền hoàn:</span>
                                <span id="summary-return-amount" class="font-bold text-red-600 text-sm">0đ</span>
                            </div>
                            <div class="text-xs text-gray-500 italic">
                                <i class="fas fa-info-circle mr-1"></i>
                                <span id="refund-note">Tối đa = đã TT</span>
                            </div>
                            <div id="exchange-summary" class="hidden">
                                <div class="flex justify-between border-t pt-1.5 mt-1.5">
                                    <span class="text-gray-600">SL đổi:</span>
                                    <span id="summary-exchange-qty" class="font-medium">0</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tiền đổi:</span>
                                    <span id="summary-exchange-amount" class="font-semibold text-blue-600">0đ</span>
                                </div>
                                <div class="flex justify-between border-t pt-1.5 mt-1.5">
                                    <span class="text-gray-700 font-medium">Chênh lệch:</span>
                                    <span id="summary-difference" class="font-bold text-sm">0đ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-2 pt-3">
                        <button type="submit" class="flex-1 bg-blue-600 text-white py-1.5 px-3 text-sm rounded-lg hover:bg-blue-700">
                            <i class="fas fa-save mr-1"></i>Lưu
                        </button>
                        <a href="{{ route('returns.show', $return->id) }}" class="flex-1 bg-gray-500 text-white py-1.5 px-3 text-sm rounded-lg hover:bg-gray-600 text-center">
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
let saleData = @json($return->sale);
let exchangeProducts = [];

// Load existing exchange items
@if($return->type == 'exchange' && $return->exchangeItems->count() > 0)
    @foreach($return->exchangeItems as $item)
        exchangeProducts.push({
            id: {{ $item->item_id }},
            type: '{{ $item->item_type }}',
            name: '{{ $item->item_type === "painting" ? ($item->painting->name ?? "N/A") : ($item->supply->name ?? "N/A") }}',
            code: '{{ $item->item_type === "painting" ? ($item->painting->code ?? "") : "" }}',
            image: '{{ $item->item_type === "painting" ? ($item->painting->image ?? "") : "" }}',
            price: {{ $item->unit_price }},
            defaultPrice: {{ $item->unit_price }},
            maxQty: {{ $item->item_type === 'painting' ? ($item->painting->quantity ?? 0) : ($item->supply->quantity ?? 0) }},
            quantity: {{ $item->quantity }},
            discount: {{ $item->discount_percent ?? 0 }},
            supplyId: {{ $item->supply_id ?? 'null' }},
            supplyName: '{{ $item->supply_id ? ($item->frameSupply->name ?? "") : "" }}',
            supplyLength: {{ $item->supply_length ?? 0 }}
        });
    @endforeach
    
    // Render loaded exchange products
    document.addEventListener('DOMContentLoaded', function() {
        renderExchangeProducts();
        updateSummary();
    });
@endif

// Calculate initial summary
document.addEventListener('DOMContentLoaded', function() {
    updateSummary();
});

// Copy all JavaScript functions from create.blade.php
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
        container.innerHTML = '<tr><td colspan="8" class="text-center text-gray-500 py-6"><i class="fas fa-box-open text-2xl mb-2 text-gray-400 block"></i><p class="text-xs">Chưa có sản phẩm đổi</p></td></tr>';
        return;
    }
    
    container.innerHTML = exchangeProducts.map((product, index) => {
        const finalPrice = product.price * (1 - (product.discount || 0) / 100);
        const stockWarning = product.quantity > product.maxQty;
        const rowClass = stockWarning ? 'bg-red-50 border-l-2 border-red-500' : 'hover:bg-blue-50 border-l-2 border-transparent';
        
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
            <td class="px-2 py-2">
                <div class="relative">
                    <input type="text" 
                           id="supply-search-${index}"
                           class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           placeholder="Tìm vật tư..."
                           value="${product.supplyName || ''}"
                           oninput="searchSupplyForExchange(${index}, this.value)"
                           autocomplete="off">
                    <div id="supply-suggestions-${index}" class="absolute z-20 w-full bg-white border border-gray-300 rounded shadow-lg mt-1 max-h-48 overflow-y-auto hidden"></div>
                </div>
            </td>
            <td class="px-2 py-2 text-center">
                <input type="number" 
                       class="w-16 px-2 py-1.5 border border-gray-300 rounded text-center text-xs font-medium focus:ring-2 focus:ring-blue-500"
                       min="0" 
                       step="0.1"
                       value="${product.supplyLength || 0}"
                       onchange="updateExchangeSupplyLength(${index}, this.value)"
                       placeholder="0">
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
                <input type="number" 
                       class="w-28 px-2 py-1.5 border border-gray-300 rounded text-right text-xs font-medium focus:ring-2 focus:ring-blue-500"
                       min="0" 
                       step="1000"
                       value="${product.price}"
                       onchange="updateExchangePrice(${index}, this.value)">
                <div class="text-xs text-gray-500 mt-0.5">${finalPrice.toLocaleString('vi-VN')}đ</div>
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
    exchangeProducts[index].supplyLength = 1;
    renderExchangeProducts();
    document.getElementById(`supply-suggestions-${index}`).classList.add('hidden');
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('[id^="supply-search-"]') && !e.target.closest('[id^="supply-suggestions-"]')) {
        document.querySelectorAll('[id^="supply-suggestions-"]').forEach(el => {
            el.classList.add('hidden');
        });
    }
});

function updateSummary() {
    const returnInputs = document.querySelectorAll('.return-qty');
    let returnQty = 0;
    let returnValue = 0;
    
    returnInputs.forEach(input => {
        const qty = parseInt(input.value) || 0;
        const price = parseFloat(input.dataset.price) || 0;
        returnQty += qty;
        returnValue += qty * price;
    });
    
    // LOGIC ĐÚNG: Tính theo tỷ lệ đã trả
    const paidAmount = saleData ? parseFloat(saleData.paid_amount) : 0;
    const currentTotal = saleData ? parseFloat(saleData.total_vnd) : 0;
    
    // Tính tỷ lệ đã trả của hóa đơn
    const paidRatio = currentTotal > 0 ? (paidAmount / currentTotal) : 0;
    
    // Tính số tiền đã trả cho các sản phẩm đang trả
    const paidForReturnedItems = returnValue * paidRatio;
    
    let actualRefund = 0;
    let exchangeAmount = 0;
    
    const type = document.getElementById('return-type').value;
    if (type === 'exchange') {
        let exchangeQty = 0;
        let totalExchange = 0;
        
        exchangeProducts.forEach(product => {
            exchangeQty += product.quantity;
            const finalPrice = product.price * (1 - (product.discount || 0) / 100);
            totalExchange += product.quantity * finalPrice;
        });
        
        // Tính chênh lệch giữa giá SP mới và số tiền đã trả cho SP cũ
        const difference = totalExchange - paidForReturnedItems;
        
        if (difference > 0) {
            // Khách trả thêm
            exchangeAmount = difference;
            actualRefund = 0;
        } else {
            // Hoàn lại khách
            exchangeAmount = 0;
            actualRefund = Math.abs(difference);
        }
        
        document.getElementById('exchange-summary').classList.remove('hidden');
        document.getElementById('summary-exchange-qty').textContent = exchangeQty;
        document.getElementById('summary-exchange-amount').textContent = totalExchange.toLocaleString('vi-VN') + 'đ';
        
        const diffEl = document.getElementById('summary-difference');
        if (actualRefund > 0) {
            diffEl.className = 'font-bold text-lg text-green-600';
            diffEl.textContent = actualRefund.toLocaleString('vi-VN') + 'đ (Hoàn lại)';
        } else if (exchangeAmount > 0) {
            diffEl.className = 'font-bold text-lg text-red-600';
            diffEl.textContent = '+' + exchangeAmount.toLocaleString('vi-VN') + 'đ (Khách trả thêm)';
        } else {
            diffEl.className = 'font-bold text-lg text-gray-600';
            diffEl.textContent = '0đ (Ngang giá)';
        }
    } else {
        // Trả hàng thuần túy: Hoàn số tiền đã trả cho SP này
        actualRefund = paidForReturnedItems;
        document.getElementById('exchange-summary').classList.add('hidden');
    }
    
    document.getElementById('summary-return-qty').textContent = returnQty;
    document.getElementById('summary-return-value').textContent = returnValue.toLocaleString('vi-VN') + 'đ';
    document.getElementById('summary-return-amount').textContent = actualRefund.toLocaleString('vi-VN') + 'đ';
    
    // Update refund note
    const paidPercent = (paidRatio * 100).toFixed(1);
    if (type === 'exchange') {
        if (actualRefund > 0) {
            document.getElementById('refund-note').textContent = `Hoàn ${actualRefund.toLocaleString('vi-VN')}đ (Đã trả ${paidPercent}% × ${returnValue.toLocaleString('vi-VN')}đ = ${paidForReturnedItems.toLocaleString('vi-VN')}đ > SP mới)`;
            document.getElementById('refund-note').className = 'text-xs text-red-600 italic font-medium';
        } else if (exchangeAmount > 0) {
            document.getElementById('refund-note').textContent = `Khách trả thêm ${exchangeAmount.toLocaleString('vi-VN')}đ (Đã trả ${paidPercent}% × ${returnValue.toLocaleString('vi-VN')}đ = ${paidForReturnedItems.toLocaleString('vi-VN')}đ bù vào SP mới)`;
            document.getElementById('refund-note').className = 'text-xs text-blue-600 italic font-medium';
        } else {
            document.getElementById('refund-note').textContent = 'Ngang giá';
            document.getElementById('refund-note').className = 'text-xs text-green-600 italic font-medium';
        }
    } else {
        if (actualRefund > 0) {
            document.getElementById('refund-note').textContent = `Hoàn ${actualRefund.toLocaleString('vi-VN')}đ (Đã trả ${paidPercent}% × ${returnValue.toLocaleString('vi-VN')}đ)`;
            document.getElementById('refund-note').className = 'text-xs text-red-600 italic font-medium';
        } else {
            document.getElementById('refund-note').textContent = 'Chưa thanh toán trước đó';
            document.getElementById('refund-note').className = 'text-xs text-gray-500 italic';
        }
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
    
    if (type === 'exchange') {
        if (exchangeProducts.length === 0) {
            event.preventDefault();
            showNotification('Vui lòng chọn sản phẩm để đổi', 'error');
            return false;
        }
        
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
