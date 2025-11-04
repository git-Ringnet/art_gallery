@extends('layouts.app')

@section('title', 'Chỉnh sửa vật tư')
@section('page-title', 'Chỉnh sửa vật tư')
@section('page-description', 'Cập nhật thông tin vật tư')

@section('header-actions')
<div class="flex space-x-2">
    <a href="{{ route('inventory.supplies.show', $supply->id) }}" class="bg-blue-600 text-white px-3 py-1.5 text-sm rounded-lg hover:bg-blue-700 transition-colors">
        <i class="fas fa-eye mr-1"></i>Xem chi tiết
    </a>
    <a href="{{ route('inventory.index') }}" class="bg-gray-600 text-white px-3 py-1.5 text-sm rounded-lg hover:bg-gray-700 transition-colors">
        <i class="fas fa-arrow-left mr-1"></i>Quay lại
    </a>
</div>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-lg p-4 glass-effect">
    <h3 class="text-base font-semibold text-gray-900 mb-4">Chỉnh sửa thông tin vật tư</h3>
    
    <form action="{{ route('inventory.supplies.update', $supply->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Mã vật tư <span class="text-red-500">*</span></label>
                <input type="text" name="code" value="{{ old('code', $supply->code) }}" required 
                       class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('code') border-red-500 @enderror" 
                       placeholder="VD: VT001">
                @error('code')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Tên vật tư <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $supply->name) }}" required 
                       class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror" 
                       placeholder="Tên vật tư">
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Hidden field: Loại mặc định là frame (cây gỗ) -->
            <input type="hidden" name="type" value="frame">
            
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Đơn vị tính <span class="text-red-500">*</span></label>
                <input type="text" name="unit" value="{{ old('unit', $supply->unit) }}" required 
                       class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('unit') border-red-500 @enderror" 
                       placeholder="VD: m, cm, cái">
                @error('unit')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Số lượng (chiều dài) <span class="text-red-500">*</span></label>
                <input type="number" name="quantity" value="{{ old('quantity', $supply->quantity) }}" required 
                       class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('quantity') border-red-500 @enderror" 
                       placeholder="100" min="0" step="0.01">
                @error('quantity')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div id="tree_count_field">
                <label class="block text-sm font-medium text-gray-700 mb-2">Số lượng cây</label>
                <input type="number" name="tree_count" value="{{ old('tree_count', $supply->tree_count) }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       placeholder="0" min="0" step="1">
                <p class="text-xs text-gray-500 mt-1">Số cây gỗ (chỉ áp dụng cho loại khung tranh)</p>
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Trạng thái <span class="text-red-500">*</span></label>
                <select name="status" required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('status') border-red-500 @enderror">
                    <option value="in_stock" {{ old('status', $supply->status) == 'in_stock' ? 'selected' : '' }}>Còn hàng</option>
                    <option value="out_of_stock" {{ old('status', $supply->status) == 'out_of_stock' ? 'selected' : '' }}>Hết hàng</option>
                </select>
                @error('status')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-700 mb-1">Ghi chú</label>
                <textarea name="notes" rows="2" 
                          class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('notes') border-red-500 @enderror" 
                          placeholder="Ghi chú...">{{ old('notes', $supply->notes) }}</textarea>
                @error('notes')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
        
        <div class="flex space-x-2 mt-4">
            <button type="submit" class="bg-green-600 text-white py-1.5 px-4 text-sm rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-save mr-1"></i>Cập nhật vật tư
            </button>
            <a href="{{ route('inventory.supplies.show', $supply->id) }}" class="bg-gray-600 text-white py-1.5 px-4 text-sm rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-times mr-1"></i>Hủy
            </a>
        </div>
    </form>
</div>
@endsection


