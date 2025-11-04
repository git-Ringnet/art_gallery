@extends('layouts.app')

@section('title', 'Nhập vật tư')
@section('page-title', 'Nhập vật tư')
@section('page-description', 'Nhập vật tư vào kho')

@section('content')
    <div class="bg-white rounded-xl shadow-lg p-4 glass-effect">
        <div class="flex items-center justify-between mb-4">
            <h4 class="font-medium text-base">Form nhập vật tư</h4>
            <a href="{{ route('inventory.import.painting.form') }}" class="text-blue-600 hover:text-blue-700 hover:bg-white border border-indigo-600 px-3 py-1.5 bg-blue-600 rounded-lg text-white text-sm">
                <i class="fas fa-image mr-1"></i>Chuyển sang nhập tranh
            </a>
        </div>

        <form action="{{ route('inventory.import.supply') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Mã vật tư <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" required
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('code') border-red-500 @enderror"
                        placeholder="VD: VT001">
                    @error('code')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tên vật tư <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Tên vật tư">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Loại <span class="text-red-500">*</span></label>
                    <select name="type" required
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Chọn loại...</option>
                        <option value="frame" {{ old('type') == 'frame' ? 'selected' : '' }}>Khung tranh</option>
                        <option value="canvas" {{ old('type') == 'canvas' ? 'selected' : '' }}>Canvas</option>
                        <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>Khác</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Đơn vị tính <span class="text-red-500">*</span></label>
                    <input type="text" name="unit" value="{{ old('unit') }}" required
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="VD: m, cm, cái">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Số lượng (chiều dài) <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity" value="{{ old('quantity') }}" required
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="100" min="0" step="0.01">
                    <p class="text-xs text-gray-500 mt-1">Tổng chiều dài của vật tư (VD: 500 cm)</p>
                </div>
                <div id="tree_count_field" style="display: none;">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Số lượng cây</label>
                    <input type="number" name="tree_count" value="{{ old('tree_count', 0) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="0" min="0" step="1">
                    <p class="text-xs text-gray-500 mt-1">Số cây gỗ (chỉ áp dụng cho loại khung tranh)</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Ghi chú</label>
                    <textarea name="notes" rows="2"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Ghi chú...">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="flex space-x-2 mt-4">
                <button type="submit"
                    class="bg-green-600 text-white py-1.5 px-4 text-sm rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-save mr-1"></i>Lưu vật tư
                </button>
                <a href="{{ route('inventory.index') }}"
                    class="bg-gray-600 text-white py-1.5 px-4 text-sm rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-times mr-1"></i>Hủy
                </a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.querySelector('select[name="type"]');
        const treeCountField = document.getElementById('tree_count_field');

        function toggleTreeCountField() {
            if (typeSelect.value === 'frame') {
                treeCountField.style.display = 'block';
            } else {
                treeCountField.style.display = 'none';
            }
        }

        typeSelect.addEventListener('change', toggleTreeCountField);
        toggleTreeCountField(); // Initial check
    });
</script>
@endpush
