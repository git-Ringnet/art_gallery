@extends('layouts.app')

@section('title', 'Lịch sử công nợ')
@section('page-title', 'Lịch sử công nợ')
@section('page-description', 'Quản lý tất cả các khoản công nợ')

@section('header-actions')
@hasPermission('debt', 'can_export')
<a href="{{ route('debt.export') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
    <i class="fas fa-file-excel mr-2"></i>Xuất báo cáo
</a>
@endhasPermission
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
    <!-- Search and Filter -->
    <div class="bg-gray-50 p-4 rounded-lg mb-6">
        <form method="GET" action="{{ route('debt.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tìm kiếm</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}" class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Tìm theo tên khách hàng, số điện thoại, mã HD...">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Tất cả</option>
                        <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Quá hạn</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chưa đến hạn</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Từ ngày</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>
            <div class="flex justify-between items-center mt-4">
                <button type="submit" class="bg-blue-600 text-white py-2 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-filter mr-2"></i>Lọc
                </button>
                <a href="{{ route('debt.index') }}" class="bg-gray-500 text-white py-2 px-6 rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-2"></i>Xóa lọc
                </a>
            </div>
        </form>
    </div>
    
    <!-- Debt Table -->
    <div class="overflow-x-auto">
        <table class="w-full table-auto">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Khách hàng</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hóa đơn</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tổng tiền</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Đã trả</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Còn nợ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày tạo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($debts as $debt)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-4 whitespace-nowrap">
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $debt['customer_name'] }}</div>
                            <div class="text-sm text-gray-500">{{ $debt['customer_phone'] }}</div>
                        </div>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-blue-600">{{ $debt['invoice_id'] }}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ number_format($debt['total']) }}đ</td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-green-600">{{ number_format($debt['paid']) }}đ</td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-red-600">{{ number_format($debt['debt']) }}đ</td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $debt['created_at'] }}</td>
                    <td class="px-4 py-4 whitespace-nowrap">
                        @if($debt['status'] == 'paid')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Đã thanh toán hết</span>
                        @elseif($debt['status'] == 'overdue')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Quá hạn</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Chưa thanh toán hết</span>
                        @endif
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm">
                        @hasPermission('debt', 'can_view')
                        <a href="{{ route('debt.show', $debt['invoice_id']) }}" class="text-blue-600 hover:text-blue-900 mr-3" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </a>
                        @endhasPermission
                        
                        @hasPermission('debt', 'can_edit')
                        @if($debt['debt'] > 0)
                        <button onclick="showCollectDebtModal('{{ $debt['invoice_id'] }}', {{ $debt['debt'] }})" class="text-green-600 hover:text-green-900" title="Thu nợ">
                            <i class="fas fa-money-bill-wave"></i>
                        </button>
                        @endif
                        @endhasPermission
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

<!-- Collect Debt Modal -->
<div id="collect-debt-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Thu nợ</h3>
            <form id="collect-debt-form" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Số tiền thu</label>
                    <input type="number" name="amount" id="collect-amount" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nhập số tiền...">
                    <p class="text-sm text-gray-500 mt-1">Còn nợ: <span id="remaining-debt-amount" class="font-semibold text-red-600"></span></p>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phương thức thanh toán</label>
                    <select name="payment_method" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="cash">Tiền mặt</option>
                        <option value="bank_transfer">Chuyển khoản</option>
                        <option value="card">Thẻ</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
                    <textarea name="note" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Ghi chú..."></textarea>
                </div>
                <div class="flex space-x-3">
                    <button type="submit" class="flex-1 bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-check mr-2"></i>Xác nhận
                    </button>
                    <button type="button" onclick="closeCollectDebtModal()" class="flex-1 bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fas fa-times mr-2"></i>Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showCollectDebtModal(invoiceId, debtAmount) {
    const modal = document.getElementById('collect-debt-modal');
    const form = document.getElementById('collect-debt-form');
    const amountInput = document.getElementById('collect-amount');
    const remainingDebtSpan = document.getElementById('remaining-debt-amount');
    
    form.action = `/debt/${invoiceId}/collect`;
    amountInput.max = debtAmount;
    amountInput.value = debtAmount;
    remainingDebtSpan.textContent = debtAmount.toLocaleString('vi-VN') + 'đ';
    
    modal.classList.remove('hidden');
}

function closeCollectDebtModal() {
    document.getElementById('collect-debt-modal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('collect-debt-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeCollectDebtModal();
    }
});
</script>
@endpush
