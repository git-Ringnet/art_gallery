@extends('layouts.app')

@section('title', 'Chi tiết tranh')
@section('page-title', 'Chi tiết tranh')
@section('page-description', 'Thông tin chi tiết về bức tranh')

@section('header-actions')
<div class="flex space-x-2">
    <a href="{{ route('inventory.paintings.edit', $painting->id) }}" class="bg-green-600 text-white px-3 py-1.5 text-sm rounded-lg hover:bg-green-700 transition-colors">
        <i class="fas fa-edit mr-1"></i>Chỉnh sửa
    </a>
    <form action="{{ route('inventory.paintings.destroy', $painting->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tranh này?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="w-full bg-red-600 text-white py-1.5 px-3 text-sm rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center">
            <i class="fas fa-trash mr-1"></i>Xóa tranh
        </button>
    </form>
    <a href="{{ route('inventory.index') }}" class="bg-gray-600 text-white px-3 py-1.5 text-sm rounded-lg hover:bg-gray-700 transition-colors">
        <i class="fas fa-arrow-left mr-1"></i>Quay lại
    </a>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Main Information -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-lg p-4 glass-effect">
            <h3 class="text-base font-semibold text-gray-900 mb-3">Thông tin cơ bản</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Mã tranh</label>
                    <p class="text-sm font-semibold text-indigo-600">{{ $painting->code }}</p>
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tên tranh</label>
                    <p class="text-sm font-semibold text-gray-900">{{ $painting->name }}</p>
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Họa sĩ</label>
                    <p class="text-sm text-gray-900">{{ $painting->artist }}</p>
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Chất liệu</label>
                    <p class="text-sm text-gray-900">
                        @switch($painting->material)
                            @case('son_dau')
                                Sơn dầu
                                @break
                            @case('canvas')
                                Canvas
                                @break
                            @case('thuy_mac')
                                Thủy mặc
                                @break
                            @case('acrylic')
                                Acrylic
                                @break
                            @default
                                {{ $painting->material }}
                        @endswitch
                    </p>
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Kích thước</label>
                    <p class="text-sm text-gray-900">
                        @if($painting->width && $painting->height)
                            {{ $painting->width }}cm x {{ $painting->height }}cm
                        @else
                            Chưa có thông tin
                        @endif
                    </p>
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Năm sản xuất</label>
                    <p class="text-sm text-gray-900">{{ $painting->paint_year ?? 'Chưa có thông tin' }}</p>
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Giá (USD)</label>
                    <p class="text-sm font-semibold text-green-600">${{ number_format($painting->price_usd, 2) }}</p>
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Số lượng</label>
                    <p class="text-sm font-semibold text-gray-900">{{ $painting->quantity }}</p>
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Ngày nhập kho</label>
                    <p class="text-sm text-gray-900">{{ $painting->import_date ? $painting->import_date->format('d/m/Y') : 'Chưa có thông tin' }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                    <p>
                        @if($painting->status == 'in_stock')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Còn hàng</span>
                        @elseif($painting->status == 'sold')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Đã bán</span>
                        @elseif($painting->status == 'reserved')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Đã đặt</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Không xác định</span>
                        @endif
                    </p>
                </div>
            </div>
            
            @if($painting->notes)
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-gray-700">{{ $painting->notes }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="lg:col-span-1">
        <!-- Image -->
        <div class="bg-white rounded-xl shadow-lg p-6 glass-effect mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Ảnh tranh</h3>
            @if($painting->image)
                <img id="painting-image-show" src="{{ asset('storage/' . $painting->image) }}" alt="{{ $painting->name }}" class="w-full h-64 object-cover rounded-lg cursor-zoom-in">
            @else
                <div class="w-full h-64 bg-gray-200 rounded-lg flex items-center justify-center">
                    <div class="text-center text-gray-500">
                        <i class="fas fa-image text-4xl mb-2"></i>
                        <p>Chưa có ảnh</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
// Simple zoom modal
(function(){
    const img = document.getElementById('painting-image-show');
    if(!img) return;
    let overlay;
    img.addEventListener('click', () => {
        overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50';
        const full = document.createElement('img');
        full.src = img.src;
        full.alt = img.alt;
        full.className = 'max-w-[90vw] max-h-[90vh] rounded-lg shadow-2xl';
        overlay.appendChild(full);
        overlay.addEventListener('click', () => overlay.remove());
        document.body.appendChild(overlay);
    });
})();
</script>
@endpush
