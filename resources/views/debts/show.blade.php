@extends('layouts.app')

@section('title', 'Chi tiết Công nợ')
@section('page-title', 'Chi tiết Công nợ')
@section('page-description', 'Thông tin chi tiết và lịch sử thanh toán')

@section('header-actions')
<div class="flex flex-wrap gap-2">
    <a href="{{ route('debt.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1.5 rounded-lg transition-colors text-sm whitespace-nowrap">
        <i class="fas fa-arrow-left mr-1"></i>Quay lại
    </a>
    @if($debt->sale->payment_status !== 'paid')
    <button onclick="showCollectModal()" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-lg transition-colors text-sm whitespace-nowrap">
        Thanh toán
    </button>
    @endif
</div>
@endsection

@section('content')
<x-alert />

@push('scripts')
<script src="{{ asset('js/number-format.js') }}"></script>
@endpush

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Debt Info -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-lg p-4 fade-in mb-4">
            <h3 class="text-base font-bold text-gray-800 mb-3 flex items-center">
                <i class="fas fa-file-invoice text-blue-500 mr-2"></i>
                Thông tin công nợ
            </h3>
            
            <div class="space-y-3">
                <div>
                    <label class="text-xs text-gray-500 font-medium">Mã hóa đơn</label>
                    <p class="font-bold text-blue-600 text-base">{{ $debt->sale->invoice_code }}</p>
                </div>
                
                <div>
                    <label class="text-xs text-gray-500 font-medium">Tên khách hàng</label>
                    <p class="font-medium text-gray-900 text-sm">{{ $debt->customer->name }}</p>
                </div>
                
                <div>
                    <label class="text-xs text-gray-500 font-medium">Số điện thoại</label>
                    <p class="font-medium text-gray-900 text-sm">
                        <i class="fas fa-phone text-blue-500 mr-1"></i>{{ $debt->customer->phone ?? '-' }}
                    </p>
                </div>
                
                <div>
                    <label class="text-xs text-gray-500 font-medium">Ngày mua hàng</label>
                    <p class="font-medium text-gray-900 text-sm">{{ $debt->sale->sale_date->format('d/m/Y') }}</p>
                </div>
                
                <div>
                    <label class="text-xs text-gray-500 font-medium">Hạn trả tiền</label>
                    <p class="font-medium {{ $debt->isOverdue() ? 'text-red-600' : 'text-gray-900' }} text-sm">
                        {{ $debt->due_date ? $debt->due_date->format('d/m/Y') : '-' }}
                        @if($debt->isOverdue())
                            <span class="text-xs ml-1">(Quá hạn)</span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- Amount Summary -->
            <div class="mt-4 pt-4 border-t-2 space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 font-medium text-sm">Tổng tiền HĐ:</span>
                    <span class="font-bold text-gray-900 text-base">{{ number_format($debt->total_amount, 0, ',', '.') }}đ</span>
                </div>
                <div class="flex justify-between items-center bg-green-50 p-2 rounded-lg">
                    <span class="text-green-700 font-medium text-sm">Đã trả:</span>
                    <span class="font-bold text-green-700 text-base">{{ number_format($debt->paid_amount, 0, ',', '.') }}đ</span>
                </div>
                <div class="flex justify-between items-center pt-2 border-t-2 bg-red-50 p-3 rounded-lg border-2 border-red-200">
                    <span class="text-red-700 font-bold text-base">Còn nợ:</span>
                    <span class="font-bold text-red-600 text-xl">{{ number_format($debt->debt_amount, 0, ',', '.') }}đ</span>
                </div>
            </div>

            <!-- Status Badge -->
            <div class="mt-6">
                @if($debt->status === 'cancelled')
                    <div class="bg-gray-100 text-gray-800 px-4 py-2 rounded-lg text-center font-medium">
                        <i class="fas fa-ban mr-2"></i>Đã hủy (Trả hàng)
                    </div>
                @elseif($debt->status === 'paid')
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
        <div class="bg-white rounded-xl shadow-lg p-4 fade-in">
            <h3 class="text-base font-bold text-gray-800 mb-3">Thao tác nhanh</h3>
            <div class="space-y-2">
                <a href="{{ route('sales.show', $debt->sale_id) }}" class="block w-full bg-blue-100 text-blue-700 px-3 py-2 rounded-lg hover:bg-blue-200 transition-colors text-center text-sm">
                    <i class="fas fa-file-invoice mr-1"></i>Xem hóa đơn
                </a>
                <a href="{{ route('customers.show', $debt->customer_id) }}" class="block w-full bg-purple-100 text-purple-700 px-3 py-2 rounded-lg hover:bg-purple-200 transition-colors text-center text-sm">
                    <i class="fas fa-user mr-1"></i>Xem khách hàng
                </a>
            </div>
        </div>
    </div>

    <!-- Payment History -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-lg p-4 fade-in">
            <h3 class="text-base font-bold text-gray-800 mb-3 flex items-center">
                <i class="fas fa-receipt text-blue-500 mr-2"></i>
                Các lần khách đã trả tiền
            </h3>
            <p class="text-xs text-gray-500 mb-3 italic">Danh sách các lần khách hàng đã thanh toán cho hóa đơn này</p>

            @if($debt->sale->payments->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-700">Ngày trả</th>
                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-700">Số tiền</th>
                            <th class="px-2 py-2 text-center text-xs font-medium text-gray-700">Hình thức</th>
                            <th class="px-2 py-2 text-center text-xs font-medium text-gray-700">Loại GD</th>
                            <th class="px-2 py-2 text-center text-xs font-medium text-gray-700">Người thu</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-700">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($debt->sale->payments as $payment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 py-2 text-xs whitespace-nowrap">
                                @php
                                    $paymentDateTime = $payment->payment_date->timezone('Asia/Ho_Chi_Minh');
                                    $timeStr = $paymentDateTime->format('H:i:s');
                                    // Chỉ hiển thị giờ nếu không phải 00:00:00 hoặc 07:00:00 (data cũ từ UTC)
                                    $hasTime = $timeStr !== '00:00:00' && $timeStr !== '07:00:00';
                                @endphp
                                <div>{{ $paymentDateTime->format('d/m/Y') }}</div>
                                @if($hasTime)
                                    <div class="text-xs text-gray-500">{{ $paymentDateTime->format('H:i') }}</div>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-right font-medium {{ $payment->amount < 0 ? 'text-red-600' : 'text-green-600' }} text-xs whitespace-nowrap">
                                @if($payment->amount < 0)
                                    <i class="fas fa-undo"></i>
                                @endif
                                {{ number_format(abs($payment->amount), 0, ',', '.') }}đ
                                @if($payment->amount < 0)
                                    <span class="text-xs">(Hoàn)</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-center">
                                @if($payment->payment_method === 'cash')
                                    <span class="px-1.5 py-0.5 bg-green-100 text-green-800 text-xs rounded-full whitespace-nowrap">
                                        Tiền Mặt
                                    </span>
                                @elseif($payment->payment_method === 'bank_transfer')
                                    <span class="px-1.5 py-0.5 bg-blue-100 text-blue-800 text-xs rounded-full whitespace-nowrap">
                                        C.Khoản
                                    </span>
                                @else
                                    <span class="px-1.5 py-0.5 bg-purple-100 text-purple-800 text-xs rounded-full whitespace-nowrap">
                                        Thẻ
                                    </span>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-center">
                                @php
                                    $transactionType = $payment->transaction_type ?? 'sale_payment';
                                @endphp
                                @if($transactionType === 'sale_payment')
                                    <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full font-semibold whitespace-nowrap">
                                        Bán Hàng
                                    </span>
                                @elseif($transactionType === 'return')
                                    <span class="px-1.5 py-0.5 bg-orange-100 text-orange-700 text-xs rounded-full font-semibold whitespace-nowrap">
                                        Trả Hàng
                                    </span>
                                @elseif($transactionType === 'exchange')
                                    <span class="px-1.5 py-0.5 bg-purple-100 text-purple-700 text-xs rounded-full font-semibold whitespace-nowrap">
                                        Đổi Hàng
                                    </span>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-center text-xs">
                                @if($payment->createdBy)
                                    <div class="flex items-center justify-center">
                                        <i class="fas fa-user-circle text-blue-500 mr-1"></i>
                                        <span class="truncate max-w-[100px]" title="{{ $payment->createdBy->name }}">{{ $payment->createdBy->name }}</span>
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-xs text-gray-600 truncate max-w-[150px]" title="{{ $payment->notes ?? '-' }}">{{ $payment->notes ?? '-' }}</td>
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
<div id="collectModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-75 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
    <div class="relative mx-auto p-4 border border-gray-300 w-full max-w-lg shadow-2xl rounded-xl bg-white">
        <div class="mt-1">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-900 flex items-center">
                    
                    Thanh toán
                </h3>
                <button onclick="closeCollectModal()" class="text-gray-400 hover:text-gray-600 text-2xl w-8 h-8">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="{{ route('debt.collect', $debt->id) }}" id="payment-form" onsubmit="return validatePayment(event)">
                @csrf
                
                <div class="space-y-4">
                    <!-- Số tiền còn nợ hiển thị rõ -->
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                        <p class="text-sm text-gray-700 mb-1 font-medium">Số tiền còn thanh toán:</p>
                        <p class="text-2xl font-bold text-red-600">{{ number_format($debt->sale->debt_amount, 0, ',', '.') }}đ</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-900 mb-2">
                            Số tiền thu <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="amount" id="payment-amount" required
                            class="w-full px-3 py-2 text-base font-semibold border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Nhập số tiền..."
                            oninput="formatVND(this)" 
                            onblur="formatVND(this)">
                        <p class="text-xs text-gray-600 mt-2 flex items-center">
                            <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                            Tối đa: <span class="font-bold ml-1">{{ number_format($debt->sale->debt_amount, 0, ',', '.') }}đ</span>
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-900 mb-2">
                            Phương thức thanh toán <span class="text-red-500">*</span>
                        </label>
                        <select name="payment_method" required class="w-full px-3 py-2 text-sm font-medium border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="cash">Tiền mặt</option>
                            <option value="bank_transfer">Chuyển khoản</option>
                            <option value="card">Thẻ</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-900 mb-2">Ghi chú</label>
                        <textarea name="notes" rows="2" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Nhập ghi chú thanh toán..."></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeCollectModal()" class="bg-gray-500 text-white px-4 py-2 text-sm font-bold rounded-lg hover:bg-gray-600 transition-coloors">
                        <i class="fas fa-times mr-1"></i>Hủy
                    </button>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 text-sm font-bold rounded-lg hover:bg-green-700 transition-colors shadow-lg">
                        <i class="fas fa-check mr-1"></i>Xác nhận thu tiền
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const maxDebt = {{ $debt->sale->debt_amount }};

function showCollectModal() {
    document.getElementById('collectModal').classList.remove('hidden');
    // Reset form
    document.getElementById('payment-form').reset();
}

function closeCollectModal() {
    document.getElementById('collectModal').classList.add('hidden');
}

function validatePayment(event) {
    const input = document.getElementById('payment-amount');
    const amountStr = input.value;
    
    // Unformat number (remove dots)
    const amount = unformatNumber(amountStr);
    
    // Validate
    if (!amount || amount <= 0) {
        alert('Vui lòng nhập số tiền hợp lệ');
        return false;
    }
    
    if (amount > maxDebt) {
        alert(`Số tiền nhập vượt quá số nợ!\nSố nợ: ${maxDebt.toLocaleString('vi-VN')}đ\nSố tiền nhập: ${amount.toLocaleString('vi-VN')}đ`);
        return false;
    }
    
    // Set unformatted value before submit
    input.value = amount;
    
    return true;
}

// Close modal when clicking outside
document.getElementById('collectModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeCollectModal();
    }
});

// Real-time validation
document.getElementById('payment-amount')?.addEventListener('input', function() {
    const amount = unformatNumber(this.value);
    const infoText = this.parentElement.querySelector('p');
    
    if (amount > maxDebt) {
        this.classList.add('border-red-500', 'bg-red-50');
        this.classList.remove('border-gray-300');
        if (infoText) {
            infoText.innerHTML = `<i class="fas fa-exclamation-triangle mr-2 text-red-500"></i>
                <span class="text-red-600 font-bold">Vượt quá số nợ! Tối đa: ${maxDebt.toLocaleString('vi-VN')}đ</span>`;
        }
    } else {
        this.classList.remove('border-red-500', 'bg-red-50');
        this.classList.add('border-gray-300');
        if (infoText) {
            infoText.innerHTML = `<i class="fas fa-info-circle mr-2 text-blue-500"></i>
                Tối đa: <span class="font-bold ml-2">${maxDebt.toLocaleString('vi-VN')}đ</span>`;
        }
    }
});
</script>
@endsection
