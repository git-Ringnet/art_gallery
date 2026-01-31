@extends('layouts.app')

@section('title', 'Cập nhật ảnh tranh ' . $painting->code)
@section('page-title', 'Cập nhật ảnh tranh')
@section('page-description', $painting->name . ' (' . $painting->code . ')')

@section('content')
    <x-confirm-modal id="confirmUpdateImageModal" title="Xác nhận cập nhật ảnh" />
    <div class="bg-white rounded-xl shadow-lg p-4 glass-effect max-w-2xl mx-auto">
        @if(session('error'))
            <div class="mb-3 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">
                <i class="fas fa-exclamation-circle mr-1"></i>{{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="mb-3 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg text-sm">
                <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-3 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">
                <p class="font-semibold mb-1"><i class="fas fa-exclamation-circle mr-1"></i>Có lỗi xảy ra:</p>
                <ul class="list-disc list-inside text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg text-sm">
            <i class="fas fa-info-circle mr-1"></i>
            Trang này cho phép cập nhật <strong>hình ảnh</strong> và <strong>ghi chú</strong> cho tranh, kể cả khi tranh đã
            bán.
        </div>

        <form id="painting-image-form" action="{{ route('inventory.paintings.update-image', $painting->id) }}" method="POST"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Mã tranh</label>
                        <div class="text-sm font-semibold text-gray-800">{{ $painting->code }}</div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Tên tranh</label>
                        <div class="text-sm font-semibold text-gray-800">{{ $painting->name }}</div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Họa sĩ</label>
                        <div class="text-sm text-gray-800">{{ $painting->artist }}</div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Trạng thái</label>
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $painting->status === 'in_stock' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $painting->status === 'in_stock' ? 'Trong kho' : 'Đã bán' }}
                        </span>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ảnh tranh (Tối đa 5MB)</label>
                    <div class="flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-lg p-6 hover:bg-gray-50 transition-colors"
                        id="drop-zone">

                        <div class="flex items-center space-x-4 w-full justify-center">
                            @if($painting->image)
                                <div class="relative group">
                                    <div class="text-xs text-center mb-1 text-gray-500">Ảnh hiện tại</div>
                                    <img id="painting-current-image" src="{{ Storage::url($painting->image) }}"
                                        alt="{{ $painting->name }}"
                                        class="max-w-32 max-h-32 object-contain rounded border bg-white shadow-sm cursor-pointer"
                                        onclick="showFullImage(this.src, '{{ $painting->name }}')">
                                </div>
                                <div class="text-gray-300"><i class="fas fa-arrow-right"></i></div>
                            @endif

                            <div class="relative group text-center">
                                <div id="painting-image-preview-wrap-edit" class="hidden mb-2">
                                    <div class="text-xs text-center mb-1 text-blue-500 font-semibold">Ảnh mới</div>
                                    <img id="painting-image-preview-edit" src="#" alt="Xem trước ảnh"
                                        class="max-w-32 max-h-32 object-contain rounded border bg-white shadow-sm cursor-pointer"
                                        onclick="showFullImage(this.src, 'Xem trước ảnh mới')">
                                </div>

                                <label for="painting-image-input-edit" class="cursor-pointer">
                                    <div class="flex flex-col items-center">
                                        <i
                                            class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2 group-hover:text-blue-500"></i>
                                        <span class="text-sm text-gray-600 group-hover:text-blue-600">Chọn ảnh mới</span>
                                        <span class="text-xs text-gray-400 mt-1">(JPG, PNG, WEBP)</span>
                                    </div>
                                    <input id="painting-image-input-edit" type="file" name="image" accept="image/*"
                                        class="hidden">
                                </label>
                            </div>
                        </div>
                        <input type="hidden" id="remove_image" name="remove_image" value="0">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                    <textarea name="notes" rows="3"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Nhập ghi chú bổ sung...">{{ old('notes', $painting->notes) }}</textarea>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-100">
                <a href="{{ route('inventory.index') }}"
                    class="bg-gray-100 text-gray-700 py-2 px-4 text-sm rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-times mr-1"></i>Hủy
                </a>
                <button type="button" onclick="confirmUpdateImage()"
                    class="bg-blue-600 text-white py-2 px-4 text-sm rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                    <i class="fas fa-save mr-1"></i>Lưu thay đổi
                </button>
            </div>
        </form>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex items-center justify-center p-4"
        onclick="closeImageModal()">
        <div class="relative max-w-4xl max-h-full" onclick="event.stopPropagation()">
            <button onclick="closeImageModal()"
                class="absolute -top-10 right-0 text-white hover:text-gray-300 focus:outline-none">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <img id="modalImage" src="" alt="" class="max-w-full max-h-[85vh] rounded shadow-2xl object-contain">
            <p id="modalImageTitle" class="text-white text-center mt-2 text-lg font-medium"></p>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function confirmUpdateImage() {
            var form = document.getElementById('painting-image-form');
            var fileInput = document.getElementById('painting-image-input-edit');
            var notes = form.querySelector('textarea[name="notes"]').value;
            var originalNotes = "{{ str_replace(array("\r", "\n"), '', addslashes($painting->notes ?? '')) }}";

            // Allow submit if image is selected OR notes changed
            if (!fileInput.files.length && notes === originalNotes) {
                // Nothing changed alert or just submit (server handles it fine but avoiding request is better)
                // Let's just confirm anyway
            }

            var summary = '<div class="text-left">';
            if (fileInput.files.length) {
                summary += '<p class="text-green-600 mb-1"><i class="fas fa-image mr-1"></i>Có thay đổi ảnh mới</p>';
            } else {
                summary += '<p class="text-gray-500 mb-1"><i class="fas fa-image mr-1"></i>Không thay đổi ảnh</p>';
            }
            if (notes !== originalNotes) {
                summary += '<p class="text-blue-600"><i class="fas fa-sticky-note mr-1"></i>Có thay đổi ghi chú</p>';
            }
            summary += '</div>';

            showConfirmModal('confirmUpdateImageModal', {
                message: summary,
                onConfirm: function () {
                    form.submit();
                }
            });
        }

        function showFullImage(src, title) {
            var modal = document.getElementById('imageModal');
            var modalImage = document.getElementById('modalImage');
            var modalTitle = document.getElementById('modalImageTitle');

            modalImage.src = src;
            modalTitle.textContent = title || '';
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            var modal = document.getElementById('imageModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeImageModal();
            }
        });

        (function () {
            var input = document.getElementById('painting-image-input-edit');
            if (!input) return;
            var wrap = document.getElementById('painting-image-preview-wrap-edit');
            var img = document.getElementById('painting-image-preview-edit');
            var removeField = document.getElementById('remove_image');

            input.addEventListener('change', function (e) {
                var file = e.target.files && e.target.files[0];
                if (!file) {
                    wrap.classList.add('hidden');
                    return;
                }

                var reader = new FileReader();
                reader.onload = function (e) {
                    img.src = e.target.result;
                    wrap.classList.remove('hidden');
                }
                reader.readAsDataURL(file);

                if (removeField) removeField.value = '0';
            });

            // Drag and drop support
            var dropZone = document.getElementById('drop-zone');

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });

            function highlight(e) {
                dropZone.classList.add('bg-blue-50', 'border-blue-400');
            }

            function unhighlight(e) {
                dropZone.classList.remove('bg-blue-50', 'border-blue-400');
            }

            dropZone.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                var dt = e.dataTransfer;
                var files = dt.files;

                if (files.length) {
                    input.files = files;
                    // Trigger change event manually
                    var event = new Event('change');
                    input.dispatchEvent(event);
                }
            }
        })();
    </script>
@endpush