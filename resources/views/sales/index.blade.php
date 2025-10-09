@extends('layouts.app')

@section('title', 'Bán hàng')
@section('page-title', 'Bán hàng')
@section('page-description', 'Quản lý tất cả các giao dịch bán hàng')

@section('header-actions')
<a href="{{ route('sales.create') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
    <i class="fas fa-plus mr-2"></i>Tạo hóa đơn
</a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
    <!-- Search and Filter -->
    <div class="bg-gray-50 p-4 rounded-lg mb-6">
        <form method="GET" action="{{ route('sales.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tìm kiếm</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}" class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Tìm theo mã HD, tên khách hàng, sản phẩm...">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Từ ngày</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Đến ngày</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái</label>
                    <select name="payment_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">Tất cả</option>
                        <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                        <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Thanh toán một phần</option>
                        <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Chưa thanh toán</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-between items-center mt-4">
                <button type="submit" class="bg-blue-600 text-white py-2 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-filter mr-2"></i>Lọc
                </button>
                <a href="{{ route('sales.index') }}" class="bg-gray-500 text-white py-2 px-6 rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-2"></i>Xóa lọc
                </a>
            </div>
        </form>
    </div>
    
    <!-- Sales Table -->
    <div class="overflow-x-auto">
        <table class="w-full table-auto">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã HD</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày bán</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Khách hàng</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Showroom</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tổng tiền</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Đã trả</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Còn nợ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($sales as $sale)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">{{ $sale->invoice_code }}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $sale->sale_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-4 whitespace-nowrap">
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $sale->customer->name }}</div>
                            <div class="text-sm text-gray-500">{{ $sale->customer->phone }}</div>
                        </div>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $sale->showroom->name }}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                        {{ number_format($sale->total_vnd) }}đ
                        <div class="text-xs text-gray-500">${{ number_format($sale->total_usd, 2) }}</div>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-blue-600">{{ number_format($sale->paid_amount) }}đ</td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-red-600">{{ number_format($sale->debt_amount) }}đ</td>
                    <td class="px-4 py-4 whitespace-nowrap">
                        @if($sale->payment_status == 'paid')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Đã thanh toán</span>
                        @elseif($sale->payment_status == 'partial')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Thanh toán một phần</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Chưa thanh toán</span>
                        @endif
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('sales.show', $sale->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Xem chi tiết">
                            <i class="fas fa-eye px-3 py-2 rounded-full bg-blue-100 text-blue-600"></i>
                        </a>
                        @if($sale->payment_status != 'paid')
                        <a href="{{ route('sales.edit', $sale->id) }}" class="text-yellow-600 hover:text-yellow-900 mr-3" title="Chỉnh sửa">
                            <i class="fas fa-edit px-3 py-2 rounded-full bg-yellow-100 text-yellow-600"></i>
                        </a>
                        @else
                        <span class="text-gray-400 cursor-not-allowed mr-3" title="Không thể chỉnh sửa hóa đơn đã thanh toán đủ">
                            <i class="fas fa-lock px-3 py-2 rounded-full bg-gray-100 text-gray-400"></i>
                        </span>
                        @endif
                        <a href="{{ route('sales.print', $sale->id) }}" target="_blank" class="text-green-600 hover:text-green-900 mr-3" title="In hóa đơn">
                            <i class="fas fa-print px-3 py-2 rounded-full bg-green-100 text-green-600"></i>
                        </a>
                        @if($sale->paid_amount == 0)
                        <button type="button" 
                                class="text-red-600 hover:text-red-900 delete-btn" 
                                title="Xóa"
                                data-url="{{ route('sales.destroy', $sale->id) }}"
                                data-message="Bạn có chắc chắn muốn xóa hóa đơn {{ $sale->invoice_code }}?">
                            <i class="fas fa-trash px-3 py-2 rounded-full bg-red-100 text-red-600"></i>
                        </button>
                        @else
                        <span class="text-gray-400 cursor-not-allowed" title="Không thể xóa hóa đơn đã có thanh toán ({{ number_format($sale->paid_amount) }}đ)">
                            <i class="fas fa-lock px-3 py-2 rounded-full bg-gray-100 text-gray-400"></i>
                        </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
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
