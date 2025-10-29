@extends('layouts.app')

@section('title', 'Sửa tranh ' . $painting->code)
@section('page-title', 'Sửa tranh')
@section('page-description', $painting->name)

@section('content')
    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <p class="font-semibold mb-2"><i class="fas fa-exclamation-circle mr-2"></i>Có lỗi xảy ra:</p>
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('inventory.paintings.update', $painting->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mã tranh</label>
                    <input type="text" value="{{ $painting->code }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" disabled>
                    <input type="hidden" name="code" value="{{ $painting->code }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tên tranh</label>
                    <input type="text" name="name" value="{{ old('name', $painting->name) }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Họa sĩ</label>
                    <input type="text" name="artist" value="{{ old('artist', $painting->artist) }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chất liệu</label>
                    <div id="material-select-edit" class="relative">
                        <div class="flex items-center">
                            <input type="text" name="material" id="material-input-edit"
                                value="{{ old('material', $painting->material) }}" autocomplete="off" required
                                placeholder="Chọn hoặc nhập chất liệu..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <button type="button" id="material-toggle-edit"
                                class="px-3 py-2 border border-l-0 border-gray-300 rounded-r-lg bg-gray-50 hover:bg-gray-100">
                                <i class="fas fa-chevron-down text-gray-500"></i>
                            </button>
                        </div>
                        <div id="material-options-edit"
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rộng (cm)</label>
                        <input type="number" step="1" name="width" value="{{ old('width', $painting->width) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cao (cm)</label>
                        <input type="number" step="1" name="height" value="{{ old('height', $painting->height) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Năm sản xuất</label>
                    <input type="text" name="paint_year" value="{{ old('paint_year', $painting->paint_year) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Giá (USD)</label>
                    <input type="number" step="0.01" name="price_usd" value="{{ old('price_usd', $painting->price_usd) }}"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ngày nhập</label>
                    <input type="date" name="import_date"
                        value="{{ old('import_date', optional($painting->import_date)->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ngày xuất</label>
                    <input type="date" name="export_date"
                        value="{{ old('export_date', optional($painting->export_date)->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
                    <textarea name="notes" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('notes', $painting->notes) }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ảnh tranh</label>
                    <div class="flex items-center space-x-4">
                        @if($painting->image)
                            <div class="relative group">
                                <img id="painting-current-image" src="{{ Storage::url($painting->image) }}"
                                    alt="{{ $painting->name }}" class="w-24 h-24 object-cover rounded-lg border">
                                <button type="button" id="btn-remove-image"
                                    class="hidden group-hover:flex absolute -top-2 -right-2 w-7 h-7 items-center justify-center rounded-full bg-red-600 text-white shadow">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        @endif
                        <input id="painting-image-input-edit" type="file" name="image" accept="image/*"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                        <input type="hidden" id="remove_image" name="remove_image" value="0">
                    </div>
                    <div id="painting-image-preview-wrap-edit" class="mt-3 hidden">
                        <img id="painting-image-preview-edit" src="#" alt="Xem trước ảnh"
                            class="w-32 h-32 object-cover rounded-lg border">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Định dạng: JPG, PNG, WEBP. Tối đa 2MB.</p>
                </div>
            </div>

            <div class="flex space-x-3 mt-6">
                <button type="submit"
                    class="bg-blue-600 text-white py-2 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Lưu thay đổi
                </button>
                <a href="{{ route('inventory.index') }}"
                    class="bg-gray-600 text-white py-2 px-6 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Quay lại
                </a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        // Material select (edit)
        (() => {
            const wrapper = document.getElementById('material-select-edit');
            if (!wrapper) return;
            const input = document.getElementById('material-input-edit');
            const toggle = document.getElementById('material-toggle-edit');
            const dropdown = document.getElementById('material-options-edit');

            function openDropdown() { dropdown.classList.remove('hidden'); }
            function closeDropdown() { dropdown.classList.add('hidden'); }
            function isOpen() { return !dropdown.classList.contains('hidden'); }

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

        // Live preview + remove image on edit
        (() => {
            const input = document.getElementById('painting-image-input-edit');
            if (!input) return;
            const wrap = document.getElementById('painting-image-preview-wrap-edit');
            const img = document.getElementById('painting-image-preview-edit');
            const removeBtn = document.getElementById('btn-remove-image');
            const removeField = document.getElementById('remove_image');

            input.addEventListener('change', (e) => {
                const file = e.target.files && e.target.files[0];
                if (!file) { wrap.classList.add('hidden'); return; }
                const url = URL.createObjectURL(file);
                img.src = url;
                wrap.classList.remove('hidden');
                // if user uploads a new file, don't mark remove current
                if (removeField) removeField.value = '0';
            });

            if (removeBtn) {
                removeBtn.addEventListener('click', () => {
                    const current = document.getElementById('painting-current-image');
                    if (current) current.remove();
                    removeBtn.remove();
                    if (removeField) removeField.value = '1';
                });
            }
        })();
    </script>
@endpush