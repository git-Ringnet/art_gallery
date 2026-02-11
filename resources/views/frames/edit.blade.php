@extends('layouts.app')

@section('title', 'Sửa khung tranh')
@section('page-title', 'Sửa khung tranh')

@section('content')
    <!-- Confirm Modal -->
    <x-confirm-modal 
        id="confirm-frame-edit-modal"
        title="Xác nhận cập nhật khung"
        message="Bạn có chắc chắn muốn cập nhật khung tranh này?"
        confirmText="Cập nhật"
        cancelText="Quay lại"
        type="warning"
    >
        <div id="confirm-frame-edit-summary" class="text-sm"></div>
    </x-confirm-modal>

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
                <button type="button" onclick="confirmUpdateFrame()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
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

        function confirmUpdateFrame() {
            const name = document.querySelector('input[name="name"]').value.trim();
            if (!name) {
                alert('Vui lòng nhập tên khung!');
                return;
            }
            
            const costPrice = document.querySelector('input[name="cost_price"]').value || '0';
            
            let summaryHtml = `
                <div class="space-y-2">
                    <div class="flex justify-between"><span class="text-gray-600">Tên khung:</span><span class="font-medium">${name}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Giá nhập:</span><span class="font-medium">${parseInt(costPrice).toLocaleString('vi-VN')}đ</span></div>
                </div>
            `;
            
            document.getElementById('confirm-frame-edit-summary').innerHTML = summaryHtml;
            
            showConfirmModal('confirm-frame-edit-modal', {
                title: 'Xác nhận cập nhật khung tranh',
                message: 'Vui lòng kiểm tra thông tin trước khi cập nhật:',
                onConfirm: function() {
                    document.getElementById('frameForm').submit();
                }
            });
        }

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
                        <div class="relative">
                            <input type="text" 
                                   id="supply-search-${index}"
                                   class="supply-search w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" 
                                   placeholder="Tìm kiếm cây gỗ..."
                                   autocomplete="off"
                                   onkeyup="filterSupplies(this.value, ${index})"
                                   onfocus="showSupplySuggestions(${index})">
                            <button type="button" 
                                    id="clear-supply-${index}"
                                    class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden"
                                    onclick="clearSupplySearch(${index})"
                                    title="Xóa tìm kiếm">
                                <i class="fas fa-times-circle"></i>
                            </button>
                            <input type="hidden" name="items[${index}][supply_id]" id="supply-id-${index}" required>
                            <div id="supply-suggestions-${index}" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-y-auto hidden shadow-lg"></div>
                        </div>
                        <div id="supply-info-${index}" class="text-xs text-gray-600 mt-1 hidden"></div>
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
                                min="0" step="0.01" max="" required placeholder="VD: 240">
                            <span class="unit-display text-gray-600">cm</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Tối đa: <span class="max-length-info">-</span></p>
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
            const treeQuantity = item.querySelector('.tree-quantity');
            const lengthPerTree = item.querySelector('.length-per-tree');
            const useWholeTrees = item.querySelector('.use-whole-trees');
            const removeBtn = item.querySelector('.remove-item-btn');
            
            treeQuantity.addEventListener('input', () => updateItemCalculations(item));
            
            lengthPerTree.addEventListener('input', () => {
                // Kiểm tra giá trị nhập không vượt quá max
                const maxValue = parseFloat(lengthPerTree.getAttribute('max'));
                const currentValue = parseFloat(lengthPerTree.value);
                
                if (maxValue && currentValue > maxValue) {
                    lengthPerTree.value = maxValue;
                    alert(`Chiều dài mỗi cây không được vượt quá ${maxValue} cm`);
                }
                
                updateItemCalculations(item);
            });
            
            removeBtn.addEventListener('click', () => {
                if (document.querySelectorAll('#itemsContainer > div').length > 1) {
                    item.remove();
                } else {
                    alert('Phải có ít nhất 1 cây gỗ!');
                }
            });
        }
        
        // Search and filter supplies
        function filterSupplies(query, index) {
            const suggestions = document.getElementById(`supply-suggestions-${index}`);
            const clearBtn = document.getElementById(`clear-supply-${index}`);
            
            if (query && query.length > 0) {
                clearBtn.classList.remove('hidden');
            } else {
                clearBtn.classList.add('hidden');
            }
            
            if (!query || query.length < 1) {
                suggestions.classList.add('hidden');
                return;
            }
            
            const filtered = supplies.filter(s =>
                s.name.toLowerCase().includes(query.toLowerCase()) ||
                s.code?.toLowerCase().includes(query.toLowerCase())
            );
            
            if (filtered.length > 0) {
                suggestions.innerHTML = filtered.map(s => `
                    <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b" onclick="selectSupply(${s.id}, ${index})">
                        <div class="font-medium text-sm">${s.name}</div>
                        <div class="text-xs text-gray-500">Còn: ${parseFloat(s.quantity).toFixed(2)} ${s.unit}/cây (${s.tree_count} cây)</div>
                    </div>
                `).join('');
                suggestions.classList.remove('hidden');
            } else {
                suggestions.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">Không tìm thấy cây gỗ nào</div>';
                suggestions.classList.remove('hidden');
            }
        }

        function clearSupplySearch(index) {
            const searchInput = document.getElementById(`supply-search-${index}`);
            const hiddenInput = document.getElementById(`supply-id-${index}`);
            const suggestions = document.getElementById(`supply-suggestions-${index}`);
            const clearBtn = document.getElementById(`clear-supply-${index}`);
            const infoDiv = document.getElementById(`supply-info-${index}`);
            const lengthPerTree = document.querySelector(`[data-index="${index}"] .length-per-tree`);
            
            searchInput.value = '';
            hiddenInput.value = '';
            suggestions.classList.add('hidden');
            clearBtn.classList.add('hidden');
            infoDiv.classList.add('hidden');
            if (lengthPerTree) lengthPerTree.value = '';
            
            const item = document.querySelector(`[data-index="${index}"]`);
            if (item) updateItemCalculations(item);
            
            searchInput.focus();
        }

        function showSupplySuggestions(index) {
            const input = document.getElementById(`supply-search-${index}`);
            const suggestions = document.getElementById(`supply-suggestions-${index}`);
            
            const filtered = input.value ? supplies.filter(s =>
                s.name.toLowerCase().includes(input.value.toLowerCase()) ||
                s.code?.toLowerCase().includes(input.value.toLowerCase())
            ) : supplies;
            
            if (filtered.length > 0) {
                suggestions.innerHTML = filtered.map(s => `
                    <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b" onclick="selectSupply(${s.id}, ${index})">
                        <div class="font-medium text-sm">${s.name}</div>
                        <div class="text-xs text-gray-500">Còn: ${parseFloat(s.quantity).toFixed(2)} ${s.unit}/cây (${s.tree_count} cây)</div>
                    </div>
                `).join('');
                suggestions.classList.remove('hidden');
            }
        }

        function selectSupply(supplyId, index) {
            const supply = supplies.find(s => s.id === supplyId);
            if (supply) {
                document.getElementById(`supply-id-${index}`).value = supply.id;
                document.getElementById(`supply-search-${index}`).value = supply.name;
                document.getElementById(`supply-suggestions-${index}`).classList.add('hidden');
                
                const clearBtn = document.getElementById(`clear-supply-${index}`);
                if (clearBtn) clearBtn.classList.remove('hidden');
                
                const infoDiv = document.getElementById(`supply-info-${index}`);
                infoDiv.innerHTML = `<i class="fas fa-info-circle mr-1"></i>Kho: ${parseFloat(supply.quantity).toFixed(2)} ${supply.unit}/cây (${supply.tree_count} cây)`;
                infoDiv.classList.remove('hidden');
                
                const item = document.querySelector(`[data-index="${index}"]`);
                const lengthPerTree = item?.querySelector('.length-per-tree');
                if (lengthPerTree) lengthPerTree.value = '';
                
                if (item) updateItemCalculations(item);
            }
        }

        document.addEventListener('click', function(e) {
            document.querySelectorAll('[id^="supply-suggestions-"]').forEach(suggestion => {
                const index = suggestion.id.replace('supply-suggestions-', '');
                if (!e.target.closest(`#supply-search-${index}`) && !e.target.closest(`#supply-suggestions-${index}`)) {
                    suggestion.classList.add('hidden');
                }
            });
        })

        function updateItemCalculations(item) {
            const index = item.dataset.index;
            const supplyId = document.getElementById(`supply-id-${index}`)?.value;
            const treeQuantity = item.querySelector('.tree-quantity');
            const lengthPerTree = item.querySelector('.length-per-tree');
            const totalLengthInput = item.querySelector('.total-length');
            const remainingInfo = item.querySelector('.remaining-info');
            const unitDisplay = item.querySelector('.unit-display');
            const useWholeTrees = item.querySelector('.use-whole-trees');
            const maxLengthInfo = item.querySelector('.max-length-info');

            const supply = supplies.find(s => s.id == supplyId);
            
            if (supply) {
                const availableQuantity = parseFloat(supply.quantity) || 0;
                const availableTreeCount = parseInt(supply.tree_count) || 0;
                const unit = supply.unit || 'cm';
                const qty = parseInt(treeQuantity.value) || 0;
                const length = parseFloat(lengthPerTree.value) || 0;
                const totalLength = qty * length;

                // Set max cho input chiều dài = chiều dài mỗi cây trong kho
                lengthPerTree.setAttribute('max', availableQuantity);
                maxLengthInfo.textContent = `${availableQuantity.toFixed(2)} ${unit}`;

                totalLengthInput.value = `${totalLength.toFixed(2)} ${unit}`;
                unitDisplay.textContent = unit;

                // Tính số cây còn lại và chiều dài còn lại mỗi cây
                const remainingTrees = availableTreeCount - qty;
                const remainingLengthPerTree = availableQuantity - length;
                
                let remainingText = `${availableQuantity.toFixed(2)} ${unit}/cây (${availableTreeCount} cây)`;
                
                if (qty > 0 && length > 0) {
                    remainingText += ` → Còn: <strong>${remainingTrees} cây × ${availableQuantity.toFixed(2)} ${unit}/cây`;
                    if (remainingLengthPerTree > 0) {
                        remainingText += ` + ${qty} cây × ${remainingLengthPerTree.toFixed(2)} ${unit}/cây (phần dư)</strong>`;
                    } else {
                        remainingText += `</strong>`;
                    }
                }
                
                remainingInfo.innerHTML = remainingText;

                // Tự động check "use whole trees" nếu phần dư < 50cm
                if (remainingLengthPerTree > 0 && remainingLengthPerTree < 50) {
                    useWholeTrees.checked = true;
                }
            } else {
                totalLengthInput.value = '0 cm';
                remainingInfo.textContent = 'Chọn cây để xem';
                lengthPerTree.removeAttribute('max');
                maxLengthInfo.textContent = '-';
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
