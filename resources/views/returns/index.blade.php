@extends('layouts.app')

@section('title', 'Đổi/Trả hàng')
@section('page-title', 'Đổi/Trả hàng')
@section('page-description', 'Quản lý các giao dịch đổi/trả hàng')

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
    <!-- Tabs -->
    <div class="flex justify-between items-center mb-6">
        <div class="flex space-x-3">
            <button onclick="showTab('search')" id="tab-search" class="tab-btn bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-search mr-2"></i>Tìm hóa đơn
            </button>
            <button onclick="showTab('list')" id="tab-list" class="tab-btn bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                <i class="fas fa-list mr-2"></i>Danh sách trả hàng
            </button>
        </div>
    </div>
    
    <!-- Tab: Search Invoice -->
    <div id="tab-content-search" class="tab-content">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Search Form -->
            <div>
                <h4 class="font-medium mb-4">Tìm hóa đơn cũ</h4>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mã hóa đơn</label>
                        <input type="text" id="invoice-code" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Nhập mã hóa đơn...">
                    </div>
                    
                    <button onclick="searchInvoice()" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>Tìm hóa đơn
                    </button>
                    
                    <div id="invoice-details" class="hidden bg-gray-50 p-4 rounded-lg">
                        <h5 class="font-medium mb-3">Chi tiết hóa đơn</h5>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>Mã HD:</span>
                                <span id="invoice-id" class="font-medium"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Ngày:</span>
                                <span id="invoice-date"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Khách hàng:</span>
                                <span id="customer-name"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Tổng tiền:</span>
                                <span id="invoice-total" class="font-semibold text-green-600"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Return Form -->
            <div>
                <h4 class="font-medium mb-4">Chi tiết sản phẩm cần trả</h4>
                <div id="products-list" class="space-y-3">
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-receipt text-4xl mb-2"></i>
                        <p>Vui lòng tìm hóa đơn trước</p>
                    </div>
                </div>
                
                <form id="return-form" action="{{ route('returns.process') }}" method="POST" class="hidden mt-6">
                    @csrf
                    <input type="hidden" name="invoice_id" id="return-invoice-id">
                    
                    <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                        <h5 class="font-medium mb-3">Tóm tắt trả hàng</h5>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>Số lượng trả:</span>
                                <span id="return-quantity" class="font-medium"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Tiền hoàn:</span>
                                <span id="return-amount" class="font-semibold text-red-600"></span>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Lý do trả hàng</label>
                            <textarea name="reason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nhập lý do..."></textarea>
                        </div>
                        
                        <div class="flex space-x-3 mt-4">
                            <button type="submit" class="flex-1 bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition-colors">
                                <i class="fas fa-undo mr-2"></i>Xác nhận trả hàng
                            </button>
                            <button type="button" onclick="cancelReturn()" class="flex-1 bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition-colors">
                                <i class="fas fa-times mr-2"></i>Hủy
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Tab: Returns List -->
    <div id="tab-content-list" class="tab-content hidden">
        <h4 class="text-lg font-semibold mb-4">Danh sách trả hàng</h4>
        
        <!-- Search and Filter -->
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <form method="GET" action="{{ route('returns.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tìm kiếm</label>
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}" class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Tìm theo mã HD, tên khách hàng, sản phẩm...">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="">Tất cả</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Đã hoàn thành</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Từ ngày</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Đến ngày</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                </div>
                <div class="flex justify-between items-center mt-4">
                    <button type="submit" class="bg-blue-600 text-white py-2 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-filter mr-2"></i>Lọc
                    </button>
                    <a href="{{ route('returns.index') }}" class="bg-gray-500 text-white py-2 px-6 rounded-lg hover:bg-gray-600 transition-colors">
                        <i class="fas fa-times mr-2"></i>Xóa lọc
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Returns Table -->
        <div class="overflow-x-auto">
            <table class="w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã HD</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày trả</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Khách hàng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản phẩm trả</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số lượng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiền hoàn</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($returns as $return)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">{{ $return['invoice_id'] }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $return['return_date'] }}</td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $return['customer_name'] }}</div>
                                <div class="text-sm text-gray-500">{{ $return['customer_phone'] }}</div>
                            </div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $return['products'] }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $return['quantity'] }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-red-600">{{ number_format($return['refund_amount']) }}đ</td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            @if($return['status'] == 'completed')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Đã hoàn thành</span>
                            @elseif($return['status'] == 'pending')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Chờ xử lý</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Đã hủy</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                            <button onclick="viewReturnDetail('{{ $return['invoice_id'] }}')" class="text-blue-600 hover:text-blue-900 mr-2" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </button>
                            @if($return['status'] == 'pending')
                            <button onclick="editReturn('{{ $return['invoice_id'] }}')" class="text-yellow-600 hover:text-yellow-900 mr-2" title="Chỉnh sửa">
                                <i class="fas fa-edit"></i>
                            </button>
                            @endif
                            <button onclick="deleteReturn('{{ $return['invoice_id'] }}')" class="text-red-600 hover:text-red-900" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>Không có dữ liệu</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function viewReturnDetail(returnId) {
    showNotification('Xem chi tiết trả hàng ' + returnId, 'info');
    // Implement view detail logic
}

function editReturn(returnId) {
    showNotification('Chỉnh sửa trả hàng ' + returnId, 'info');
    // Implement edit logic
}

function deleteReturn(returnId) {
    if (confirm('Bạn có chắc chắn muốn xóa giao dịch trả hàng này?')) {
        showNotification('Đã xóa trả hàng ' + returnId, 'success');
        // Implement delete logic
    }
}
</script>
<script>
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('bg-blue-600', 'bg-green-600');
        btn.classList.add('bg-gray-500');
    });
    
    // Show selected tab
    document.getElementById('tab-content-' + tabName).classList.remove('hidden');
    const btn = document.getElementById('tab-' + tabName);
    btn.classList.remove('bg-gray-500');
    btn.classList.add(tabName === 'search' ? 'bg-blue-600' : 'bg-green-600');
}

function searchInvoice() {
    const invoiceCode = document.getElementById('invoice-code').value;
    if (!invoiceCode) {
        showNotification('Vui lòng nhập mã hóa đơn', 'error');
        return;
    }

    fetch(`{{ route('returns.search') }}?invoice_code=${invoiceCode}`)
        .then(response => response.json())
        .then(data => {
            // Show invoice details
            document.getElementById('invoice-details').classList.remove('hidden');
            document.getElementById('invoice-id').textContent = data.id;
            document.getElementById('invoice-date').textContent = data.date;
            document.getElementById('customer-name').textContent = data.customer.name;
            document.getElementById('invoice-total').textContent = data.total.toLocaleString('vi-VN') + 'đ';

            // Show products list
            const productsList = document.getElementById('products-list');
            productsList.innerHTML = '';
            
            data.products.forEach(product => {
                const productDiv = document.createElement('div');
                productDiv.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-lg';
                productDiv.innerHTML = `
                    <div class="flex items-center space-x-3">
                        <img src="${product.image}" alt="${product.name}" class="w-12 h-12 rounded-lg object-cover">
                        <div>
                            <p class="font-medium">${product.name}</p>
                            <p class="text-sm text-gray-600">Số lượng: ${product.quantity}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <label class="text-sm">Số lượng trả:</label>
                        <input type="number" name="items[${product.code}][return_quantity]" class="w-20 px-2 py-1 border border-gray-300 rounded text-center" min="0" max="${product.quantity}" value="0" onchange="updateReturnSummary()">
                        <input type="hidden" name="items[${product.code}][product_code]" value="${product.code}">
                    </div>
                `;
                productsList.appendChild(productDiv);
            });

            document.getElementById('return-invoice-id').value = data.id;
            showNotification('Tìm thấy hóa đơn', 'success');
        })
        .catch(error => {
            showNotification('Không tìm thấy hóa đơn', 'error');
        });
}

function updateReturnSummary() {
    const returnInputs = document.querySelectorAll('#products-list input[type="number"]');
    let totalQuantity = 0;
    let totalAmount = 0;

    returnInputs.forEach(input => {
        const quantity = parseInt(input.value) || 0;
        totalQuantity += quantity;
        totalAmount += quantity * 2500000; // Mock price
    });

    if (totalQuantity > 0) {
        document.getElementById('return-form').classList.remove('hidden');
        document.getElementById('return-quantity').textContent = totalQuantity;
        document.getElementById('return-amount').textContent = totalAmount.toLocaleString('vi-VN') + 'đ';
    } else {
        document.getElementById('return-form').classList.add('hidden');
    }
}

function cancelReturn() {
    document.getElementById('return-form').classList.add('hidden');
    document.getElementById('invoice-code').value = '';
    document.getElementById('invoice-details').classList.add('hidden');
    document.getElementById('products-list').innerHTML = `
        <div class="text-center text-gray-500 py-8">
            <i class="fas fa-receipt text-4xl mb-2"></i>
            <p>Vui lòng tìm hóa đơn trước</p>
        </div>
    `;
    showNotification('Đã hủy trả hàng', 'info');
}
</script>
@endpush
