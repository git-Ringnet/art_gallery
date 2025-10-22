@extends('layouts.app')

@section('title', 'Chỉnh sửa phiếu đổi/trả hàng')
@section('page-title', 'Chỉnh sửa phiếu đổi/trả hàng')
@section('page-description', 'Chỉnh sửa thông tin phiếu đổi/trả hàng')

@section('content')
<x-alert />

<form action="{{ route('returns.update', $return->id) }}" method="POST" id="return-form">
    @csrf
    @method('PUT')
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Search Invoice -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-lg p-6 glass-effect mb-6">
                <h3 class="text-lg font-semibold mb-4">Thông tin hóa đơn gốc</h3>
                
                <div class="p-4 bg-gray-50 rounded-lg">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div><span class="text-gray-600">Mã HD:</span> <span class="font-medium">{{ $return->sale->invoice_code }}</span></div>
                        <div><span class="text-gray-600">Ngày:</span> <span>{{ $return->sale->sale_date->format('d/m/Y') }}</span></div>
                        <div><span class="text-gray-600">Khách hàng:</span> <span class="font-medium">{{ $return->customer->name }}</span></div>
                        <div><span class="text-gray-600">Tổng tiền:</span> <span class="font-medium text-green-600">{{ number_format($return->sale->total_vnd, 0, ',', '.') }}đ</span></div>
                    </div>
                </div>
                
                <input type="hidden" name="sale_id" value="{{ $return->sale_id }}">
                <input type="hidden" name="customer_id" value="{{ $return->customer_id }}">
            </div>
            
            <!-- Products List -->
            <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
                <h3 class="text-lg font-semibold mb-4">Sản phẩm</h3>
                
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
                            
                            // Calculate unit price after applying discounts
                            $unitPrice = $item->price_vnd;
                            
                            // Apply item-level discount if exists
                            if ($item->discount_percent > 0) {
                                $unitPrice = $unitPrice * (1 - $item->discount_percent / 100);
                            }
                            
                            // Apply sale-level discount if exists
                            if ($return->sale->discount_percent > 0) {
                                $unitPrice = $unitPrice * (1 - $return->sale->discount_percent / 100);
                            }
                        @endphp
                        
                        @if($available > 0 || $currentQty > 0)
                        <div class="border rounded-lg p-4 mb-3">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1">
                                    <h4 class="font-medium">{{ $itemName }}</h4>
                                    <p class="text-sm text-gray-600">Đã mua: {{ $item->quantity }} | Đã trả: {{ $returnedQty }} | Còn lại: {{ $available }}</p>
                                    <p class="text-sm text-green-600">Đơn giá: {{ number_format($unitPrice, 0, ',', '.') }}đ</p>
                                </div>
                                <div class="text-right">
                                    <label class="text-sm text-gray-600">Số lượng trả:</label>
                                    <input type="number" 
                                           name="items[{{ $item->id }}][quantity]" 
                                           class="w-20 px-2 py-1 border rounded text-center return-qty"
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
        </div>
        
        <!-- Right Column - Return Info -->
        <div>
            <div class="bg-white rounded-xl shadow-lg p-6 glass-effect sticky top-6">
                <h3 class="text-lg font-semibold mb-4">Thông tin đổi/trả</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Loại giao dịch *</label>
                        <select name="type" id="return-type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required onchange="updateReturnType()">
                            <option value="return" {{ $return->type == 'return' ? 'selected' : '' }}>Trả hàng</option>
                            <option value="exchange" {{ $return->type == 'exchange' ? 'selected' : '' }}>Đổi hàng</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ngày đổi/trả *</label>
                        <input type="date" name="return_date" value="{{ $return->return_date->format('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lý do</label>
                        <textarea name="reason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Nhập lý do đổi/trả...">{{ $return->reason }}</textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
                        <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Ghi chú thêm...">{{ $return->notes }}</textarea>
                    </div>
                    
                    <div class="border-t pt-4">
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Số lượng:</span>
                                <span id="summary-qty" class="font-medium">0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tổng tiền:</span>
                                <span id="summary-amount" class="font-semibold text-red-600">0đ</span>
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
// Calculate initial summary
document.addEventListener('DOMContentLoaded', function() {
    updateSummary();
});

function updateSummary() {
    const inputs = document.querySelectorAll('.return-qty');
    let totalQty = 0;
    let totalAmount = 0;
    
    inputs.forEach(input => {
        const qty = parseInt(input.value) || 0;
        const price = parseFloat(input.dataset.price) || 0;
        totalQty += qty;
        totalAmount += qty * price;
    });
    
    document.getElementById('summary-qty').textContent = totalQty;
    document.getElementById('summary-amount').textContent = totalAmount.toLocaleString('vi-VN') + 'đ';
}

function updateReturnType() {
    // Can add more logic here if needed
}
</script>
@endpush
