@extends('layouts.app')

@section('title', 'Chi tiết vật tư')
@section('page-title', 'Chi tiết vật tư')
@section('page-description', 'Thông tin chi tiết về vật tư')

@section('header-actions')
<div class="flex space-x-2">
    <a href="{{ route('inventory.supplies.edit', $supply->id) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
        <i class="fas fa-edit mr-2"></i>Chỉnh sửa
    </a>
    <a href="{{ route('inventory.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>Quay lại
    </a>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Information -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Thông tin cơ bản</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mã vật tư</label>
                    <p class="text-lg font-semibold text-indigo-600">{{ $supply->code }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tên vật tư</label>
                    <p class="text-lg font-semibold text-gray-900">{{ $supply->name }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Loại</label>
                    <p class="text-gray-900">
                        @switch($supply->type)
                            @case('frame')
                                Khung tranh
                                @break
                            @case('canvas')
                                Canvas
                                @break
                            @case('other')
                                Khác
                                @break
                            @default
                                {{ $supply->type }}
                        @endswitch
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Đơn vị tính</label>
                    <p class="text-gray-900">{{ $supply->unit }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng</label>
                    <p class="text-lg font-semibold text-gray-900">{{ $supply->quantity }} {{ $supply->unit }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ngày nhập kho</label>
                    <p class="text-gray-900">{{ $supply->import_date ? $supply->import_date->format('d/m/Y') : 'Chưa có thông tin' }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                    <p>
                        @if($supply->status == 'in_stock')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Còn hàng</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Hết hàng</span>
                        @endif
                    </p>
                </div>
            </div>
            
            @if($supply->notes)
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-gray-700">{{ $supply->notes }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="lg:col-span-1">
        <!-- Actions -->
        <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Thao tác</h3>
            <div class="space-y-3">
                <a href="{{ route('inventory.supplies.edit', $supply->id) }}" class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center">
                    <i class="fas fa-edit mr-2"></i>Chỉnh sửa
                </a>
                
                <form action="{{ route('inventory.supplies.destroy', $supply->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa vật tư này?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-trash mr-2"></i>Xóa vật tư
                    </button>
                </form>
                
                <a href="{{ route('inventory.index') }}" class="w-full bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition-colors flex items-center justify-center">
                    <i class="fas fa-arrow-left mr-2"></i>Quay lại danh sách
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
