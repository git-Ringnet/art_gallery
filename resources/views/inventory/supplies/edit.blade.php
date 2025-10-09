@extends('layouts.app')

@section('title', 'Chỉnh sửa vật tư')
@section('page-title', 'Chỉnh sửa vật tư')
@section('page-description', 'Cập nhật thông tin vật tư')

@section('header-actions')
<div class="flex space-x-2">
    <a href="{{ route('inventory.supplies.show', $supply->id) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
        <i class="fas fa-eye mr-2"></i>Xem chi tiết
    </a>
    <a href="{{ route('inventory.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>Quay lại
    </a>
</div>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
    <h3 class="text-lg font-semibold text-gray-900 mb-6">Chỉnh sửa thông tin vật tư</h3>
    
    <form action="{{ route('inventory.supplies.update', $supply->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Mã vật tư <span class="text-red-500">*</span></label>
                <input type="text" name="code" value="{{ old('code', $supply->code) }}" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('code') border-red-500 @enderror" 
                       placeholder="VD: VT001">
                @error('code')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tên vật tư <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $supply->name) }}" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror" 
                       placeholder="Tên vật tư">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Loại <span class="text-red-500">*</span></label>
                <select name="type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('type') border-red-500 @enderror">
                    <option value="">Chọn loại...</option>
                    <option value="frame" {{ old('type', $supply->type) == 'frame' ? 'selected' : '' }}>Khung tranh</option>
                    <option value="canvas" {{ old('type', $supply->type) == 'canvas' ? 'selected' : '' }}>Canvas</option>
                    <option value="other" {{ old('type', $supply->type) == 'other' ? 'selected' : '' }}>Khác</option>
                </select>
                @error('type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Đơn vị tính <span class="text-red-500">*</span></label>
                <input type="text" name="unit" value="{{ old('unit', $supply->unit) }}" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('unit') border-red-500 @enderror" 
                       placeholder="VD: m, cm, cái">
                @error('unit')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Số lượng <span class="text-red-500">*</span></label>
                <input type="number" name="quantity" value="{{ old('quantity', $supply->quantity) }}" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('quantity') border-red-500 @enderror" 
                       placeholder="100" min="0" step="0.01">
                @error('quantity')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái <span class="text-red-500">*</span></label>
                <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('status') border-red-500 @enderror">
                    <option value="in_stock" {{ old('status', $supply->status) == 'in_stock' ? 'selected' : '' }}>Còn hàng</option>
                    <option value="out_of_stock" {{ old('status', $supply->status) == 'out_of_stock' ? 'selected' : '' }}>Hết hàng</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
                <textarea name="notes" rows="3" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('notes') border-red-500 @enderror" 
                          placeholder="Ghi chú...">{{ old('notes', $supply->notes) }}</textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
        
        <div class="flex space-x-3 mt-6">
            <button type="submit" class="bg-green-600 text-white py-2 px-6 rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-save mr-2"></i>Cập nhật vật tư
            </button>
            <a href="{{ route('inventory.supplies.show', $supply->id) }}" class="bg-gray-600 text-white py-2 px-6 rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-times mr-2"></i>Hủy
            </a>
        </div>
    </form>
</div>
@endsection
