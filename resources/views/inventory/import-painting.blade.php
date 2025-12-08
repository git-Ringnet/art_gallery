@extends('layouts.app')

@section('title', 'Nhập tranh')
@section('page-title', 'Nhập tranh')
@section('page-description', 'Nhập tranh vào kho')

@section('content')
    <div class="bg-white rounded-xl shadow-lg p-4 glass-effect">
        <div class="flex items-center justify-between mb-4">
            <h4 class="font-medium text-base">Nhập tranh</h4>
            <div class="flex space-x-2">
                <a href="{{ route('inventory.template.painting') }}" class="text-green-600 hover:text-green-700 border border-green-600 px-3 py-1.5 rounded-lg text-sm hover:bg-green-50">
                    <i class="fas fa-download mr-1"></i>Tải file mẫu
                </a>
                <a href="{{ route('inventory.import.supply.form') }}" class="text-blue-600 hover:text-blue-700 hover:bg-white border border-indigo-600 px-3 py-1.5 bg-blue-600 rounded-lg text-white text-sm">
                    <i class="fas fa-box mr-1"></i>Chuyển sang nhập vật tư
                </a>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-4">
            <nav class="-mb-px flex space-x-4">
                <button type="button" onclick="switchTab('manual')" id="tab-manual" class="tab-btn border-b-2 border-blue-500 py-2 px-1 text-sm font-medium text-blue-600">
                    Nhập thủ công
                </button>
                <button type="button" onclick="switchTab('excel')" id="tab-excel" class="tab-btn border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Import từ Excel
                </button>
            </nav>
        </div>

        <!-- Manual Form -->
        <div id="form-manual" class="tab-content">

        <form action="{{ route('inventory.import.painting') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Mã tranh <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" required
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('code') border-red-500 @enderror"
                        placeholder="Nhập mã tranh...">
                    @error('code')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tên tranh <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Nhập tên tranh hoặc tác tranh...">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Họa sĩ (Artist) <span class="text-red-500">*</span></label>
                    <input type="text" name="artist" value="{{ old('artist') }}" required
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Nhập tên họa sĩ...">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Chất liệu tranh <span class="text-red-500">*</span></label>
                    <div id="material-select-create" class="relative">
                        <div class="flex items-center">
                            <input type="text" name="material" id="material-input-create" value="{{ old('material') }}" autocomplete="off" required
                                placeholder="Chọn hoặc nhập chất liệu..."
                                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <button type="button" id="material-toggle-create"
                                class="px-2 py-1.5 border border-l-0 border-gray-300 rounded-r-lg bg-gray-50 hover:bg-gray-100">
                                <i class="fas fa-chevron-down text-gray-500 text-xs"></i>
                            </button>
                        </div>
                        <div id="material-options-create"
                            class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto hidden">
                            <ul class="py-1 text-xs text-gray-700">
                                <li data-value="Sơn dầu" class="px-2 py-1.5 hover:bg-blue-50 cursor-pointer">Sơn dầu</li>
                                <li data-value="Canvas" class="px-2 py-1.5 hover:bg-blue-50 cursor-pointer">Canvas</li>
                                <li data-value="Thủy mặc" class="px-2 py-1.5 hover:bg-blue-50 cursor-pointer">Thủy mặc</li>
                                <li data-value="Acrylic" class="px-2 py-1.5 hover:bg-blue-50 cursor-pointer">Acrylic</li>
                                <li data-value="Màu nước" class="px-2 py-1.5 hover:bg-blue-50 cursor-pointer">Màu nước</li>
                                <li data-value="Bột màu" class="px-2 py-1.5 hover:bg-blue-50 cursor-pointer">Bột màu</li>
                                <li data-value="Khác" class="px-2 py-1.5 hover:bg-blue-50 cursor-pointer">Khác</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Rộng (cm)</label>
                        <input type="number" name="width" value="{{ old('width') }}"
                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Rộng (cm)">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Cao (cm)</label>
                        <input type="number" name="height" value="{{ old('height') }}"
                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Cao (cm)">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Năm sản xuất (Paint year)</label>
                    <input type="text" name="year" value="{{ old('year') }}"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Ví dụ: 2019">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Giá USD</label>
                    <input type="number" name="price_usd" value="{{ old('price_usd') }}" step="0.01"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Ví dụ: 4500">
                    <p class="text-xs text-gray-500 mt-1">Nhập giá USD hoặc VND (ít nhất 1 loại)</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Giá VND</label>
                    <input type="number" name="price_vnd" value="{{ old('price_vnd') }}" step="1"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Ví dụ: 100000000">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Ngày nhập kho <span class="text-red-500">*</span></label>
                    <input type="date" name="import_date" value="{{ old('import_date') }}" required
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Ảnh tranh (5Mb)</label>
                    <input id="painting-image-input" type="file" name="image" accept="image/*"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                    <div id="painting-image-preview-wrap" class="mt-2 hidden">
                        <img id="painting-image-preview" src="#" alt="Xem trước ảnh"
                            class="max-w-xs max-h-48 object-contain rounded border bg-gray-100 cursor-pointer"
                            onclick="showFullImage(this.src, 'Xem trước ảnh tranh')">
                    </div>
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
                    <i class="fas fa-save mr-1"></i>Lưu nhập tranh
                </button>
                <a href="{{ route('inventory.index') }}"
                    class="bg-gray-600 text-white py-1.5 px-4 text-sm rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-times mr-1"></i>Hủy
                </a>
            </div>
        </form>
        </div>

        <!-- Excel Import Form -->
        <div id="form-excel" class="tab-content hidden">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <h5 class="font-medium text-sm text-blue-800 mb-2"><i class="fas fa-info-circle mr-1"></i>Hướng dẫn import</h5>
                <ol class="text-xs text-blue-700 space-y-1 list-decimal list-inside">
                    <li>Tải file mẫu Excel bằng nút "Tải file mẫu" ở trên</li>
                    <li>Điền thông tin tranh vào file Excel theo mẫu</li>
                    <li><strong>Chuẩn bị ảnh:</strong> Đặt tên ảnh theo mã tranh (VD: T001.jpg, T002.png, T003.jpg)</li>
                    <li>Các cột có dấu (*) là bắt buộc</li>
                    <li>Upload file Excel + chọn nhiều ảnh cùng lúc (Ctrl + Click)</li>
                </ol>
                <div class="mt-3 p-2 bg-green-50 border border-green-200 rounded">
                    <p class="text-xs text-green-800"><i class="fas fa-lightbulb mr-1"></i><strong>Mẹo:</strong> Đặt tên ảnh theo mã tranh (T001.jpg, T002.png...) rồi chọn tất cả ảnh cùng lúc khi upload!</p>
                </div>
            </div>

            <form action="{{ route('inventory.import.painting.excel') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-file-excel text-green-600 mr-1"></i>
                        Bước 1: Chọn file Excel <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="file" id="excel-file" accept=".xlsx,.xls" required
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                    <p class="text-xs text-gray-500 mt-1">Chấp nhận file .xlsx hoặc .xls, tối đa 10MB</p>
                </div>

                <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-images text-blue-600 mr-1"></i>
                        Bước 2: Chọn ảnh tranh (tùy chọn)
                    </label>
                    <input type="file" name="images[]" id="image-files" accept="image/jpeg,image/png,image/jpg,image/gif" multiple
                        class="w-full px-3 py-2 text-sm border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                    <div class="mt-2 text-xs text-blue-700">
                        <p class="font-semibold mb-1"><i class="fas fa-lightbulb mr-1"></i>Hướng dẫn:</p>
                        <ul class="list-disc list-inside space-y-1 ml-2">
                            <li>Đặt tên ảnh theo mã tranh: <code class="bg-blue-100 px-1 rounded">T001.jpg</code>, <code class="bg-blue-100 px-1 rounded">T002.png</code></li>
                            <li>Chọn nhiều ảnh: Giữ <kbd class="bg-white px-1 border rounded">Ctrl</kbd> + Click từng ảnh</li>
                            <li>Hoặc: Click ảnh đầu → Giữ <kbd class="bg-white px-1 border rounded">Shift</kbd> → Click ảnh cuối</li>
                        </ul>
                    </div>
                    <div id="image-preview" class="mt-2 text-xs text-gray-600"></div>
                </div>

                <div class="flex space-x-2">
                    <button type="submit"
                        class="bg-blue-600 text-white py-2 px-4 text-sm rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-upload mr-1"></i>Import từ Excel
                    </button>
                    <a href="{{ route('inventory.index') }}"
                        class="bg-gray-600 text-white py-2 px-4 text-sm rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fas fa-times mr-1"></i>Hủy
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-80 z-50 hidden flex items-center justify-center p-4" onclick="closeImageModal()">
    <div class="relative" onclick="event.stopPropagation()">
        <button onclick="closeImageModal()" class="absolute -top-10 right-0 text-white hover:text-gray-300">
            <i class="fas fa-times text-2xl"></i>
        </button>
        <img id="modalImage" src="" alt="" class="max-w-[90vw] max-h-[90vh] rounded-lg shadow-2xl">
        <p id="modalImageTitle" class="text-white text-center mt-4 text-lg"></p>
    </div>
</div>

@push('scripts')
    <script>
        // Image modal functions
        function showFullImage(src, title) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            const modalTitle = document.getElementById('modalImageTitle');
            
            modalImage.src = src;
            modalTitle.textContent = title || '';
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        
        // Close modal with ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeImageModal();
            }
        });

        // Tab switching
        function switchTab(tab) {
            const tabs = ['manual', 'excel'];
            tabs.forEach(t => {
                const btn = document.getElementById(`tab-${t}`);
                const content = document.getElementById(`form-${t}`);
                if (t === tab) {
                    btn.classList.add('border-blue-500', 'text-blue-600');
                    btn.classList.remove('border-transparent', 'text-gray-500');
                    content.classList.remove('hidden');
                } else {
                    btn.classList.remove('border-blue-500', 'text-blue-600');
                    btn.classList.add('border-transparent', 'text-gray-500');
                    content.classList.add('hidden');
                }
            });
        }

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

        // Image files preview
        (() => {
            const imageInput = document.getElementById('image-files');
            const preview = document.getElementById('image-preview');
            if (!imageInput || !preview) return;

            imageInput.addEventListener('change', (e) => {
                const files = e.target.files;
                if (files.length === 0) {
                    preview.innerHTML = '';
                    return;
                }

                const fileNames = Array.from(files).map(f => f.name).join(', ');
                preview.innerHTML = `<i class="fas fa-check-circle text-green-600 mr-1"></i>Đã chọn <strong>${files.length}</strong> ảnh: ${fileNames}`;
            });
        })();
    </script>
@endpush
