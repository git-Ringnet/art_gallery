@extends('layouts.app')

@section('title', 'Nhập tranh')
@section('page-title', 'Nhập tranh')
@section('page-description', 'Nhập tranh vào kho')

@section('content')
    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
        <div class="flex items-center justify-between mb-6">
            <h4 class="font-medium text-lg">Form nhập tranh</h4>
            <a href="{{ route('inventory.import.supply.form') }}" class="text-blue-600 hover:text-blue-700 hover:bg-white border border-indigo-600 p-2 bg-blue-600 rounded-lg text-white">
                <i class="fas fa-box mr-2"></i>Chuyển sang nhập vật tư
            </a>
        </div>

        <form action="{{ route('inventory.import.painting') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mã tranh <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('code') border-red-500 @enderror"
                        placeholder="Nhập mã tranh...">
                    @error('code')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tên tranh <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Nhập tên tranh hoặc tác tranh...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Họa sĩ (Artist) <span class="text-red-500">*</span></label>
                    <input type="text" name="artist" value="{{ old('artist') }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Nhập tên họa sĩ...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chất liệu tranh <span class="text-red-500">*</span></label>
                    <div id="material-select-create" class="relative">
                        <div class="flex items-center">
                            <input type="text" name="material" id="material-input-create" value="{{ old('material') }}" autocomplete="off" required
                                placeholder="Chọn hoặc nhập chất liệu..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <button type="button" id="material-toggle-create"
                                class="px-3 py-2 border border-l-0 border-gray-300 rounded-r-lg bg-gray-50 hover:bg-gray-100">
                                <i class="fas fa-chevron-down text-gray-500"></i>
                            </button>
                        </div>
                        <div id="material-options-create"
                            class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto hidden">
                            <ul class="py-1 text-sm text-gray-700">
                                <li data-value="Sơn dầu" class="px-3 py-2 hover:bg-blue-50 cursor-pointer">Sơn dầu</li>
                                <li data-value="Canvas" class="px-3 py-2 hover:bg-blue-50 cursor-pointer">Canvas</li>
                                <li data-value="Thủy mặc" class="px-3 py-2 hover:bg-blue-50 cursor-pointer">Thủy mặc</li>
                                <li data-value="Acrylic" class="px-3 py-2 hover:bg-blue-50 cursor-pointer">Acrylic</li>
                                <li data-value="Màu nước" class="px-3 py-2 hover:bg-blue-50 cursor-pointer">Màu nước</li>
                                <li data-value="Bột màu" class="px-3 py-2 hover:bg-blue-50 cursor-pointer">Bột màu</li>
                                <li data-value="Khác" class="px-3 py-2 hover:bg-blue-50 cursor-pointer">Khác</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rộng (W)</label>
                        <input type="number" name="width" value="{{ old('width') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Rộng (W)">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cao (H)</label>
                        <input type="number" name="height" value="{{ old('height') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Cao (H)">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Năm sản xuất (Paint year)</label>
                    <input type="text" name="year" value="{{ old('year') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Ví dụ: 2019">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Giá (USD) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" value="{{ old('price') }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Ví dụ: 4500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ngày nhập kho <span class="text-red-500">*</span></label>
                    <input type="date" name="import_date" value="{{ old('import_date') }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ảnh tranh</label>
                    <input id="painting-image-input" type="file" name="image" accept="image/*"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                    <div id="painting-image-preview-wrap" class="mt-3 hidden">
                        <img id="painting-image-preview" src="#" alt="Xem trước ảnh"
                            class="w-40 h-40 object-cover rounded-lg border">
                    </div>
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
                    <i class="fas fa-save mr-2"></i>Lưu nhập tranh
                </button>
                <a href="{{ route('inventory.index') }}"
                    class="bg-gray-600 text-white py-2 px-6 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-times mr-2"></i>Hủy
                </a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        // Material select
        (() => {
            const wrapper = document.getElementById('material-select-create');
            if (!wrapper) return;
            const input = document.getElementById('material-input-create');
            const toggle = document.getElementById('material-toggle-create');
            const dropdown = document.getElementById('material-options-create');

            function openDropdown() {
                dropdown.classList.remove('hidden');
            }

            function closeDropdown() {
                dropdown.classList.add('hidden');
            }

            function isOpen() {
                return !dropdown.classList.contains('hidden');
            }

            toggle.addEventListener('click', () => {
                isOpen() ? closeDropdown() : openDropdown();
                input.focus();
            });

            input.addEventListener('focus', openDropdown);
            input.addEventListener('input', () => {
                const term = input.value.toLowerCase();
                dropdown.querySelectorAll('li').forEach(li => {
                    const show = li.dataset.value.toLowerCase().includes(term);
                    li.classList.toggle('hidden', !show);
                });
            });

            dropdown.querySelectorAll('li').forEach(li => {
                li.addEventListener('click', () => {
                    input.value = li.dataset.value;
                    closeDropdown();
                });
            });

            document.addEventListener('click', (e) => {
                if (!wrapper.contains(e.target)) closeDropdown();
            });
        })();

        // Live preview for image
        (() => {
            const input = document.getElementById('painting-image-input');
            if (!input) return;
            const wrap = document.getElementById('painting-image-preview-wrap');
            const img = document.getElementById('painting-image-preview');
            input.addEventListener('change', (e) => {
                const file = e.target.files && e.target.files[0];
                if (!file) { wrap.classList.add('hidden'); return; }
                const url = URL.createObjectURL(file);
                img.src = url;
                wrap.classList.remove('hidden');
            });
        })();
    </script>
@endpush
