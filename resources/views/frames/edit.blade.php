@extends('layouts.app')

@section('title', 'Sửa khung tranh')
@section('page-title', 'Sửa khung tranh')

@section('content')
    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
        <div class="flex items-center justify-between mb-6">
            <h4 class="font-medium text-lg">Chỉnh sửa khung tranh</h4>
            <a href="{{ route('frames.show', $frame) }}" class="text-blue-600 hover:text-blue-700 border border-blue-600 px-4 py-2 rounded-lg">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại
            </a>
        </div>

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('frames.update', $frame) }}" method="POST" id="frameForm">
            @csrf
            @method('PUT')
            
            <!-- Thông tin khung -->
            <div class="mb-6">
                <h5 class="font-medium text-gray-700 mb-4">Thông tin khung</h5>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tên khung <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $frame->name) }}" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Giá nhập khung <span class="text-red-500">*</span></label>
                        <input type="number" name="cost_price" value="{{ old('cost_price', $frame->cost_price) }}" 
                            step="0.01" min="0" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
                        <textarea name="notes" rows="2"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('notes', $frame->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Danh sách cây gỗ sử dụng -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h5 class="font-medium text-gray-700">Cây gỗ sử dụng <span class="text-red-500">*</span></h5>
                    <button type="button" id="addItemBtn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                        <i class="fas fa-plus mr-2"></i>Thêm cây
                    </button>
                </div>

                <div id="itemsContainer" class="space-y-4">
                    <!-- Items sẽ được thêm vào đây bằng JavaScript -->
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <a href="{{ route('frames.show', $frame) }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Hủy
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Cập nhật
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        const supplies = @json($supplies);
        const existingItems = @json($frame->items);
        let itemIndex = 0;

        function addItem(data = {}) {
            const index = itemIndex++;
            const item = document.createElement('div');
            item.className = 'border border-gray-300 rounded-lg p-4 bg-gray-50';
            item.dataset.index = index;
            
            item.innerHTML = `
                <div class="flex items-center justify-between mb-3">
                    <h6 class="font-medium text-gray-700">Cây gỗ #${index + 1}</h6>
                    <button type="button" class="text-red-600 hover:text-red-800 remove-item-btn">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Chọn cây gỗ <span class="text-red-500">*</span></label>
                        <select name="items[${index}][supply_id]" class="supply-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            <option value="">-- Chọn cây gỗ --</option>
                            ${supplies.map(s => `
                                <option value="${s.id}" 
                                    data-quantity="${s.quantity}" 
                                    data-tree-count="${s.tree_count}"
                                    data-unit="${s.unit}"
                                    ${data.supply_id == s.id ? 'selected' : ''}>
                                    ${s.name} - Còn: ${parseFloat(s.quantity).toFixed(2)} ${s.unit} (${s.tree_count} cây)
                                </option>
                            `).join('')}
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            Còn lại: <span class="remaining-info font-medium">Chọn cây để xem</span>
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Số lượng cây <span class="text-red-500">*</span></label>
                        <input type="number" name="items[${index}][tree_quantity]" value="${data.tree_quantity || 1}" 
                            class="tree-quantity w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            min="1" step="1" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Chiều dài mỗi cây <span class="text-red-500">*</span></label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="items[${index}][length_per_tree]" value="${data.length_per_tree || ''}" 
                                class="length-per-tree flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                min="0" step="0.01" required placeholder="VD: 240">
                            <span class="unit-display text-gray-600">cm</span>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tổng chiều dài</label>
                        <input type="text" class="total-length w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" 
                            readonly value="0 cm">
                    </div>
                    
                    <div class="md:col-span-3">
                        <div class="flex items-center">
                            <input type="checkbox" name="items[${index}][use_whole_trees]" value="1" 
                                class="use-whole-trees w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                ${data.use_whole_trees ? 'checked' : ''}>
                            <label class="ml-2 text-sm text-gray-700">
                                Sử dụng nguyên cây (phần còn lại quá ngắn không xài được)
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Nếu chọn, sẽ trừ số cây từ kho</p>
                    </div>
                </div>
            `;
            
            document.getElementById('itemsContainer').appendChild(item);
            attachItemEvents(item);
            updateItemCalculations(item);
        }

        function attachItemEvents(item) {
            const supplySelect = item.querySelector('.supply-select');
            const treeQuantity = item.querySelector('.tree-quantity');
            const lengthPerTree = item.querySelector('.length-per-tree');
            const useWholeTrees = item.querySelector('.use-whole-trees');
            const removeBtn = item.querySelector('.remove-item-btn');

            supplySelect.addEventListener('change', () => updateItemCalculations(item));
            treeQuantity.addEventListener('input', () => updateItemCalculations(item));
            lengthPerTree.addEventListener('input', () => updateItemCalculations(item));
            
            removeBtn.addEventListener('click', () => {
                if (document.querySelectorAll('#itemsContainer > div').length > 1) {
                    item.remove();
                } else {
                    alert('Phải có ít nhất 1 cây gỗ!');
                }
            });
        }

        function updateItemCalculations(item) {
            const supplySelect = item.querySelector('.supply-select');
            const treeQuantity = item.querySelector('.tree-quantity');
            const lengthPerTree = item.querySelector('.length-per-tree');
            const totalLengthInput = item.querySelector('.total-length');
            const remainingInfo = item.querySelector('.remaining-info');
            const unitDisplay = item.querySelector('.unit-display');
            const useWholeTrees = item.querySelector('.use-whole-trees');

            const selectedOption = supplySelect.options[supplySelect.selectedIndex];
            
            if (selectedOption.value) {
                const availableQuantity = parseFloat(selectedOption.dataset.quantity) || 0;
                const availableTreeCount = parseInt(selectedOption.dataset.treeCount) || 0;
                const unit = selectedOption.dataset.unit || 'cm';
                const qty = parseInt(treeQuantity.value) || 0;
                const length = parseFloat(lengthPerTree.value) || 0;
                const totalLength = qty * length;

                totalLengthInput.value = `${totalLength.toFixed(2)} ${unit}`;
                unitDisplay.textContent = unit;

                const remaining = availableQuantity - totalLength;
                remainingInfo.innerHTML = `${availableQuantity.toFixed(2)} ${unit} (${availableTreeCount} cây) → Còn: <strong>${remaining.toFixed(2)} ${unit}</strong>`;

                if (remaining > 0 && remaining < 50) {
                    useWholeTrees.checked = true;
                }
            } else {
                totalLengthInput.value = '0 cm';
                remainingInfo.textContent = 'Chọn cây để xem';
            }
        }

        document.getElementById('addItemBtn').addEventListener('click', () => addItem());

        // Load existing items
        document.addEventListener('DOMContentLoaded', () => {
            if (existingItems.length > 0) {
                existingItems.forEach(item => {
                    addItem({
                        supply_id: item.supply_id,
                        tree_quantity: item.tree_quantity,
                        length_per_tree: item.length_per_tree,
                        use_whole_trees: item.use_whole_trees
                    });
                });
            } else {
                addItem();
            }
        });
    </script>
    @endpush
@endsection
