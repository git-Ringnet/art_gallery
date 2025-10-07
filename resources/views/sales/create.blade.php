@extends('layouts.app')

@section('title', 'Tạo hóa đơn bán hàng')
@section('page-title', 'Tạo hóa đơn bán hàng')
@section('page-description', 'Tạo hóa đơn bán hàng mới')

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
    <form action="{{ route('sales.store') }}" method="POST" id="sales-form">
        @csrf
        
        <!-- Customer Information -->
        <div class="mb-6">
            <h4 class="font-medium mb-4">Thông tin khách hàng</h4>
            <div class="space-y-4">
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tên khách hàng <span class="text-red-500">*</span></label>
                        <input type="text" name="customer_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Nhập tên khách hàng...">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Số điện thoại <span class="text-red-500">*</span></label>
                        <input type="tel" name="customer_phone" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Nhập số điện thoại...">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Giảm giá (%)</label>
                        <input type="number" name="discount_percent" id="discount-percent" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Ví dụ: 10" min="0" max="100" value="0">
                    </div>
                </div>
              
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Địa chỉ</label>
                        <textarea name="customer_address" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent h-[40px]" rows="3" placeholder="Nhập địa chỉ..."></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phòng trưng bày <span class="text-red-500">*</span></label>
                        <select name="showroom_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Chọn phòng...</option>
                            @foreach($showrooms as $showroom)
                                <option value="{{ $showroom['id'] }}">{{ $showroom['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="mb-6 bg-white rounded-xl shadow-sm border">
            <div class="px-4 py-3 border-b">
                <h4 class="font-semibold">Danh sách sản phẩm</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full table-auto" id="sales-items-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hình ảnh</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mô tả (Mã tranh/Khung)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vật tư (khung)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số mét/1 cây</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số lượng</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại tiền</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Giá bán USD</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Giá bán VND</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Giảm giá</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="sales-items-body">
                        <tr>
                            <td class="px-4 py-3 text-sm">
                                <img src="https://bizweb.dktcdn.net/100/372/422/products/tranh-son-dau-dep-da-nang-4-3.jpg?v=1679906135817" width="100" height="50" alt="">
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <textarea name="items[0][description]" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nhập mô tả..."></textarea>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <select name="items[0][supply_code]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">Không dùng vật tư</option>
                                    @foreach($supplies as $supply)
                                        <option value="{{ $supply['code'] }}">{{ $supply['name'] }} ({{ $supply['qty'] }} {{ $supply['unit'] }})</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <input type="number" name="items[0][length_m]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus-border-transparent" placeholder="Số mét/1 SP" value="0" min="0" step="0.01">
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <input type="number" name="items[0][quantity]" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nhập số lượng..." onchange="calculateTotals()">
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <select name="items[0][currency]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="toggleCurrencyInputs(this)">
                                    <option value="USD">USD</option>
                                    <option value="VND">VND</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <input type="number" name="items[0][price]" class="price-usd w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nhập giá USD..." onchange="calculateTotals()">
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <input type="number" name="items[0][price_vnd]" class="price-vnd w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nhập giá VND..." onchange="calculateTotals()" style="display: none;">
                            </td>
                            <td class="px-4 py-3 text-sm"><input type="number" id="discount-percent" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nhập giảm giá..."></td>

                            <td class="px-4 py-3 text-sm">
                                <button type="button" class="text-red-600 hover:text-red-800" onclick="this.closest('tr').remove(); calculateTotals();">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <button type="button" onclick="addSalesItem()" class="w-25 bg-blue-600 m-4 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Thêm sản phẩm
            </button>
        </div>

        <!-- Totals -->
        <div class="mb-6">
            <div class="space-y-4">
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tổng tiền USD</label>
                        <input type="number" id="total-usd" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tỷ giá USD/VND <span class="text-red-500">*</span></label>
                        <input type="number" name="exchange_rate" id="exchange-rate" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="25000" value="25000" onchange="calculateTotals()">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tổng tiền VND</label>
                        <input type="number" id="total-vnd" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Số tiền khách trả</label>
                    <input type="number" name="payment_amount" id="payment-amount" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Nhập số tiền..." onchange="calculateRemainingDebt()">
                </div>
                
                <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                    <div class="flex justify-between">
                        <span>Công nợ còn lại:</span>
                        <span id="remaining-debt" class="font-semibold text-red-600">0đ</span>
                    </div>
                </div>
                
                <div class="flex space-x-3">
                    <button type="submit" name="action" value="save" class="flex-1 bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-file-invoice mr-2"></i>Tạo hóa đơn
                    </button>
                    <button type="submit" name="action" value="save_and_print" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-print mr-2"></i>Tạo & In hóa đơn
                    </button>
                    <a href="{{ route('sales.index') }}" class="flex-1 bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition-colors text-center">
                        <i class="fas fa-times mr-2"></i>Hủy
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
let itemIndex = 1;

function addSalesItem() {
    const tbody = document.getElementById('sales-items-body');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td class="px-4 py-3 text-sm">
            <img src="https://bizweb.dktcdn.net/100/372/422/products/tranh-son-dau-dep-da-nang-4-3.jpg?v=1679906135817" width="100" height="50" alt="">
        </td>
        <td class="px-4 py-3 text-sm">
            <textarea name="items[${itemIndex}][description]" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nhập mô tả..."></textarea>
        </td>
        <td class="px-4 py-3 text-sm">
            <select name="items[${itemIndex}][supply_code]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Không dùng vật tư</option>
                @foreach($supplies as $supply)
                    <option value="{{ $supply['code'] }}">{{ $supply['name'] }} ({{ $supply['qty'] }} {{ $supply['unit'] }})</option>
                @endforeach
            </select>
        </td>
        <td class="px-4 py-3 text-sm">
            <input type="number" name="items[${itemIndex}][length_m]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus-border-transparent" placeholder="Số mét/1 SP" value="0" min="0" step="0.01">
        </td>
        <td class="px-4 py-3 text-sm">
            <input type="number" name="items[${itemIndex}][quantity]" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nhập số lượng..." onchange="calculateTotals()">
        </td>
        <td class="px-4 py-3 text-sm">
            <select name="items[${itemIndex}][currency]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="toggleCurrencyInputs(this)">
                <option value="USD">USD</option>
                <option value="VND">VND</option>
            </select>
        </td>
        <td class="px-4 py-3 text-sm">
            <input type="number" name="items[${itemIndex}][price]" class="price-usd w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nhập giá USD..." onchange="calculateTotals()">
        </td>
        <td class="px-4 py-3 text-sm">
            <input type="number" name="items[${itemIndex}][price_vnd]" class="price-vnd w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nhập giá VND..." onchange="calculateTotals()" style="display: none;">
        </td>
        <td class="px-4 py-3 text-sm">
            <button type="button" class="text-red-600 hover:text-red-800" onclick="this.closest('tr').remove(); calculateTotals();">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    itemIndex++;
}

function toggleCurrencyInputs(selectElement) {
    const row = selectElement.closest('tr');
    const currencyType = selectElement.value;
    const priceUsdInput = row.querySelector('.price-usd');
    const priceVndInput = row.querySelector('.price-vnd');
    
    if (currencyType === 'USD') {
        priceUsdInput.style.display = 'block';
        priceVndInput.style.display = 'none';
        priceVndInput.value = '';
    } else {
        priceUsdInput.style.display = 'none';
        priceVndInput.style.display = 'block';
        priceUsdInput.value = '';
    }
    
    calculateTotals();
}

function calculateTotals() {
    const exchangeRate = parseFloat(document.getElementById('exchange-rate').value) || 25000;
    const rows = document.querySelectorAll('#sales-items-body tr');
    
    let totalUsd = 0;
    let totalVnd = 0;
    
    rows.forEach(row => {
        const quantity = parseFloat(row.querySelector('input[name*="[quantity]"]')?.value || 0);
        const currencySelect = row.querySelector('select[name*="[currency]"]');
        const currencyType = currencySelect?.value || 'USD';
        
        if (currencyType === 'USD') {
            const priceUsd = parseFloat(row.querySelector('.price-usd')?.value || 0);
            const itemTotalUsd = quantity * priceUsd;
            totalUsd += itemTotalUsd;
            totalVnd += itemTotalUsd * exchangeRate;
        } else {
            const priceVnd = parseFloat(row.querySelector('.price-vnd')?.value || 0);
            const itemTotalVnd = quantity * priceVnd;
            totalVnd += itemTotalVnd;
            totalUsd += itemTotalVnd / exchangeRate;
        }
    });
    
    document.getElementById('total-usd').value = totalUsd.toFixed(2);
    document.getElementById('total-vnd').value = totalVnd.toFixed(0);
    
    calculateRemainingDebt();
}

function calculateRemainingDebt() {
    const paymentAmount = parseFloat(document.getElementById('payment-amount').value) || 0;
    const totalVnd = parseFloat(document.getElementById('total-vnd').value) || 0;
    const remaining = Math.max(0, totalVnd - paymentAmount);
    
    document.getElementById('remaining-debt').textContent = remaining.toLocaleString('vi-VN') + 'đ';
}
</script>
@endpush
