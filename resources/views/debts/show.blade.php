@extends('layouts.app')

@section('title', 'Chi tiết Công nợ')
@section('page-title', 'Chi tiết Công nợ')
@section('page-description', 'Thông tin chi tiết và lịch sử thanh toán')

@section('header-actions')
<a href="{{ route('debt.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
    <i class="fas fa-arrow-left mr-2"></i>Quay lại
</a>
@if($debt->sale->payment_status !== 'paid')
<button onclick="showCollectModal()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors">
    <i class="fas fa-money-bill-wave mr-2"></i>Thu nợ
</button>
@endif
@endsection

@section('content')
<x-alert />

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Debt Info -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-lg p-6 fade-in mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-file-invoice text-blue-500 mr-2"></i>
                Thông tin công nợ
            </h3>
            
            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-500">Mã hóa đơn</label>
                    <p class="font-medium text-blue-600">{{ $debt->sale->invoice_code }}</p>
                </div>
                
                <div>
                    <label class="text-sm text-gray-500">Khách hàng</label>
                    <p class="font-medium text-gray-900">{{ $debt->customer->name }}</p>
                </div>
                
                <div>
                    <label class="text-sm text-gray-500">Số điện thoại</label>
                    <p class="font-medium text-gray-900">
                        <i class="fas fa-phone text-blue-500 mr-2"></i>{{ $debt->customer->phone ?? '-' }}
                    </p>
                </div>
                
                <div>
                    <label class="text-sm text-gray-500">Ngày bán</label>
                    <p class="font-medium text-gray-900">{{ $debt->sale->sale_date->format('d/m/Y') }}</p>
                </div>
                
                <div>
                    <label class="text-sm text-gray-500">Hạn thanh toán</label>
                    <p class="font-medium {{ $debt->isOverdue() ? 'text-red-600' : 'text-gray-900' }}">
                        {{ $debt->due_date ? $debt->due_date->format('d/m/Y') : '-' }}
                        @if($debt->isOverdue())
                            <span class="text-xs">(Quá hạn)</span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- Amount Summary -->
            <div class="mt-6 pt-6 border-t space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Tổng tiền:</span>
                    <span class="font-bold text-gray-900">{{ number_format($debt->total_amount, 0, ',', '.') }}đ</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Đã thanh toán:</span>
                    <span class="font-bold text-green-600">{{ number_format($debt->paid_amount, 0, ',', '.') }}đ</span>
                </div>
                <div class="flex justify-between pt-3 border-t">
                    <span class="text-gray-900 font-medium">Còn nợ:</span>
                    <span class="font-bold text-red-600 text-xl">{{ number_format($debt->debt_amount, 0, ',', '.') }}đ</span>
                </div>
            </div>

            <!-- Status Badge -->
            <div class="mt-6">
                @if($debt->status === 'paid')
                    <div class="bg-green-100 text-green-800 px-4 py-2 rounded-lg text-center font-medium">
                        <i class="fas fa-check-circle mr-2"></i>Đã thanh toán đầy đủ
                    </div>
                @elseif($debt->isOverdue())
                    <div class="bg-red-100 text-red-800 px-4 py-2 rounded-lg text-center font-medium">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Quá hạn thanh toán
                    </div>
                @elseif($debt->status === 'partial')
                    <div class="bg-yellow-100 text-yellow-800 px-4 py-2 rounded-lg text-center font-medium">
                        <i class="fas fa-clock mr-2"></i>Đã trả một phần
                    </div>
                @else
                    <div class="bg-gray-100 text-gray-800 px-4 py-2 rounded-lg text-center font-medium">
                        <i class="fas fa-hourglass-half mr-2"></i>Chưa thanh toán
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-lg p-6 fade-in">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Thao tác nhanh</h3>
            <div class="space-y-2">
                <a href="{{ route('sales.show', $debt->sale_id) }}" class="block w-full bg-blue-100 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-200 transition-colors text-center">
                    <i class="fas fa-file-invoice mr-2"></i>Xem hóa đơn
                </a>
                <a href="{{ route('customers.show', $debt->customer_id) }}" class="block w-full bg-purple-100 text-purple-700 px-4 py-2 rounded-lg hover:bg-purple-200 transition-colors text-center">
                    <i class="fas fa-user mr-2"></i>Xem khách hàng
                </a>
            </div>
        </div>
    </div>

    <!-- Payment History -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-lg p-6 fade-in">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-history text-blue-500 mr-2"></i>
                Lịch sử thanh toán
            </h3>

            @if($debt->sale->payments->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Ngày thanh toán</th>
                            <th class="px-4 py-2 text-right text-sm font-medium text-gray-700">Số tiền</th>
                            <th class="px-4 py-2 text-center text-sm font-medium text-gray-700">Phương thức</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($debt->sale->payments as $payment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">{{ $payment->payment_date->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-right font-medium text-green-600">
                                {{ number_format($payment->amount, 0, ',', '.') }}đ
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($payment->payment_method === 'cash')
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Tiền mặt</span>
                                @elseif($payment->payment_method === 'bank_transfer')
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">Chuyển khoản</span>
                                @else
                                    <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">Thẻ</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $payment->notes ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-receipt text-4xl mb-2"></i>
                <p>Chưa có thanh toán nào</p>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Collect Payment Modal -->
<div id="collectModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Thu nợ</h3>
                <button onclick="closeCollectModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="{{ route('debt.collect', $debt->id) }}">
                @csrf
                <input type="hidden" name="payment_method" value="cash">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Số tiền thu <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="amount" required min="1" max="{{ $debt->sale->debt_amount }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="Nhập số tiền...">
                        <p class="text-xs text-gray-500 mt-1">Tối đa: {{ number_format($debt->sale->debt_amount, 0, ',', '.') }}đ</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nội dung</label>
                        <textarea name="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Nhập nội dung thanh toán..."></textarea>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeCollectModal()" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                        Hủy
                    </button>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                        <i class="fas fa-check mr-2"></i>Xác nhận
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showCollectModal() {
    document.getElementById('collectModal').classList.remove('hidden');
}

function closeCollectModal() {
    document.getElementById('collectModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('collectModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeCollectModal();
    }
});
</script>
@endsection
