@extends('layouts.app')

@section('title', 'Sửa Khách hàng')
@section('page-title', 'Sửa thông tin Khách hàng')
@section('page-description', 'Cập nhật thông tin khách hàng')

@section('content')
<x-alert />

<!-- Confirm Modal for Customer Edit -->
<x-confirm-modal 
    id="confirm-customer-edit-modal"
    title="Xác nhận cập nhật khách hàng"
    message="Bạn có chắc chắn muốn cập nhật thông tin khách hàng này?"
    confirmText="Cập nhật"
    cancelText="Quay lại"
    type="warning"
>
    <div id="confirm-customer-edit-summary" class="text-sm">
    </div>
</x-confirm-modal>

<div class="bg-white rounded-xl shadow-lg p-4 glass-effect">
    <form method="POST" action="{{ route('customers.update', $customer->id) }}" id="customer-edit-form">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <h4 class="font-semibold mb-3 text-base">Thông tin khách hàng</h4>
            
            <div class="grid grid-cols-2 gap-3 mb-3">
                <!-- Name -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                        Tên khách hàng <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="customer_name" value="{{ old('name', $customer->name) }}" required
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                        Số điện thoại
                    </label>
                    <input type="text" name="phone" id="customer_phone" value="{{ old('phone', $customer->phone) }}"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('phone') border-red-500 @enderror">
                    @error('phone')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-3">
                <!-- Email -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                        Email
                    </label>
                    <input type="email" name="email" id="customer_email" value="{{ old('email', $customer->email) }}"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Address -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                        Địa chỉ
                    </label>
                    <input type="text" name="address" id="customer_address" value="{{ old('address', $customer->address) }}"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('address') border-red-500 @enderror">
                    @error('address')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-3">
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Ghi chú
                </label>
                <textarea name="notes" rows="2"
                    class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('notes') border-red-500 @enderror">{{ old('notes', $customer->notes) }}</textarea>
                @error('notes')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-2">
            <button type="button" onclick="confirmUpdateCustomer()" class="flex-1 bg-blue-600 text-white py-1.5 text-sm rounded-lg hover:bg-blue-700 transition-colors font-medium">
                <i class="fas fa-save mr-1"></i>Cập nhật
            </button>
            <a href="{{ route('customers.index') }}" class="flex-1 bg-gray-600 text-white py-1.5 text-sm rounded-lg hover:bg-gray-700 text-center transition-colors font-medium">
                <i class="fas fa-times mr-1"></i>Hủy
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
function confirmUpdateCustomer() {
    const name = document.getElementById('customer_name').value.trim();
    const phone = document.getElementById('customer_phone').value.trim();
    const email = document.getElementById('customer_email').value.trim();
    const address = document.getElementById('customer_address').value.trim();
    
    // Validate required fields
    if (!name) {
        alert('Vui lòng nhập tên khách hàng!');
        document.getElementById('customer_name').focus();
        return false;
    }
    
    // Build summary
    let summaryHtml = `
        <div class="space-y-2">
            <div class="flex justify-between">
                <span class="text-gray-600">Tên:</span>
                <span class="font-medium">${name}</span>
            </div>`;
    
    if (phone) {
        summaryHtml += `
            <div class="flex justify-between">
                <span class="text-gray-600">SĐT:</span>
                <span class="font-medium">${phone}</span>
            </div>`;
    }
    
    if (email) {
        summaryHtml += `
            <div class="flex justify-between">
                <span class="text-gray-600">Email:</span>
                <span class="font-medium">${email}</span>
            </div>`;
    }
    
    if (address) {
        summaryHtml += `
            <div class="flex justify-between">
                <span class="text-gray-600">Địa chỉ:</span>
                <span class="font-medium">${address}</span>
            </div>`;
    }
    
    summaryHtml += `</div>`;
    
    document.getElementById('confirm-customer-edit-summary').innerHTML = summaryHtml;
    
    // Show confirmation modal
    showConfirmModal('confirm-customer-edit-modal', {
        title: 'Xác nhận cập nhật khách hàng',
        message: 'Vui lòng kiểm tra thông tin trước khi cập nhật:',
        onConfirm: function() {
            document.getElementById('customer-edit-form').submit();
        }
    });
}
</script>
@endpush
@endsection
