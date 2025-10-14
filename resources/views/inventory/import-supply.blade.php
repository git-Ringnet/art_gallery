@extends('layouts.app')

@section('title', 'Nhập vật tư')
@section('page-title', 'Nhập vật tư')
@section('page-description', 'Nhập vật tư vào kho')

@section('content')
    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
        <div class="flex items-center justify-between mb-6">
            <h4 class="font-medium text-lg">Form nhập vật tư</h4>
            <a href="{{ route('inventory.import.painting.form') }}" class="text-blue-600 hover:text-blue-700 hover:bg-white border border-indigo-600 p-2 bg-blue-600 rounded-lg text-white">
                <i class="fas fa-image mr-2"></i>Chuyển sang nhập tranh
            </a>
        </div>

        <form action="{{ route('inventory.import.supply') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mã vật tư <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('code') border-red-500 @enderror"
                        placeholder="VD: VT001">
                    @error('code')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tên vật tư <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Tên vật tư">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Loại <span class="text-red-500">*</span></label>
                    <select name="type" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Chọn loại...</option>
                        <option value="frame" {{ old('type') == 'frame' ? 'selected' : '' }}>Khung tranh</option>
                        <option value="canvas" {{ old('type') == 'canvas' ? 'selected' : '' }}>Canvas</option>
                        <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>Khác</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Đơn vị tính <span class="text-red-500">*</span></label>
                    <input type="text" name="unit" value="{{ old('unit') }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="VD: m, cm, cái">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Số lượng <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity" value="{{ old('quantity') }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="100" min="0" step="0.01">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
                    <textarea name="notes" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Ghi chú...">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="flex space-x-3 mt-6">
                <button type="submit"
                    class="bg-green-600 text-white py-2 px-6 rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Lưu vật tư
                </button>
                <a href="{{ route('inventory.index') }}"
                    class="bg-gray-600 text-white py-2 px-6 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-times mr-2"></i>Hủy
                </a>
            </div>
        </form>
    </div>
@endsection
