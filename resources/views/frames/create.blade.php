@extends('layouts.app')

@section('title', 'Tạo khung tranh')
@section('page-title', 'Tạo khung tranh')
@section('page-description', 'Tạo khung tranh mới từ cây gỗ')

@section('content')
    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
        <div class="flex items-center justify-between mb-6">
            <h4 class="font-medium text-lg">Form tạo khung tranh</h4>
            <a href="{{ route('frames.index') }}" class="text-blue-600 hover:text-blue-700 border border-blue-600 px-4 py-2 rounded-lg">
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

        <form action="{{ route('frames.store') }}" method="POST" id="frameForm">
            @csrf
            
            <!-- Thông tin khung -->
            <div class="mb-6">
                <h5 class="font-medium text-gray-700 mb-4">Thông tin khung</h5>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tên khung <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="VD: Khung tranh 40x60">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Giá nhập khung (VND) <span class="text-red-500">*</span></label>
                        <input type="number" name="cost_price" value="{{ old('cost_price') }}" 
                            step="0.01" min="0" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="VD: 150000">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Giá nhập khung (USD)</label>
                        <input type="number" name="cost_price_usd" value="{{ old('cost_price_usd') }}" 
                            step="0.01" min="0"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="VD: 10">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Chiều dài khung (cm) <span class="text-red-500">*</span></label>
                        <input type="number" name="frame_length" id="frame_length" value="{{ old('frame_length') }}" 
                            step="0.01" min="0" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="VD: 60">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Chiều rộng khung (cm) <span class="text-red-500">*</span></label>
                        <input type="number" name="frame_width" id="frame_width" value="{{ old('frame_width') }}" 
                            step="0.01" min="0" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="VD: 40">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Khấu trừ góc xéo (cm) <span class="text-red-500">*</span>
                            <i class="fas fa-info-circle text-gray-400 ml-1" title="Tổng chiều dài 4 góc xéo cần khấu trừ"></i>
                        </label>
                        <input type="number" name="corner_deduction" id="corner_deduction" value="{{ old('corner_deduction', 0) }}" 
                            step="0.01" min="0" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="VD: 17">
                        <p class="text-xs text-gray-500 mt-1">Tổng chiều dài 4 góc xéo (phần thừa khi cắt góc 45°)</p>
                    </div>

                    <div class="md:col-span-2">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="grid grid-cols-3 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Chu vi:</span>
                                    <span class="font-semibold text-blue-700 ml-2" id="perimeter_display">0 cm</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Khấu trừ góc:</span>
                                    <span class="font-semibold text-orange-700 ml-2" id="corner_display">0 cm</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Tổng cây cần:</span>
                                    <span class="font-semibold text-green-700 ml-2" id="total_wood_display">0 cm</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
                        <textarea name="notes" rows="2"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Ghi chú thêm về khung tranh...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Danh sách cây gỗ sử dụng -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h5 class="font-medium text-gray-700">Loại cây gỗ sử dụng <span class="text-red-500">*</span></h5>
                    <button type="button" id="addItemBtn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                        <i class="fas fa-plus mr-2"></i>Thêm loại cây
                    </button>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Lưu ý:</strong> Hệ thống sẽ tự động tính số cây cần dùng dựa trên chu vi khung và khấu trừ góc xéo bạn nhập ở trên
                    </p>
                </div>

                <div id="itemsContainer" class="space-y-4">
                    <!-- Items sẽ được thêm vào đây bằng JavaScript -->
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <a href="{{ route('frames.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Hủy
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Tạo khung
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        const supplies = @json($supplies);
        let itemIndex = 0;

        function calculateFrameMetrics() {
            const frameLength = parseFloat(document.getElementById('frame_length').value) || 0;
            const frameWidth = parseFloat(document.getElementById('frame_width').value) || 0;
            const cornerDeduction = parseFloat(document.getElementById('corner_deduction').value) || 0;
            
            // Tính chu vi
            const perimeter = 2 * (frameLength + frameWidth);
            
            // Tổng cây cần
            const totalWoodNeeded = perimeter + cornerDeduction;
            
            // Cập nhật hiển thị
            document.getElementById('perimeter_display').textContent = perimeter.toFixed(2) + ' cm';
            document.getElementById('corner_display').textContent = cornerDeduction.toFixed(2) + ' cm';
            document.getElementById('total_wood_display').textContent = totalWoodNeeded.toFixed(2) + ' cm';
            
            return { perimeter, cornerDeduction, totalWoodNeeded };
        }

        function addItem(data = {}) {
            const index = itemIndex++;
            const item = document.createElement('div');
            item.className = 'border border-gray-300 rounded-lg p-4 bg-gray-50';
            item.dataset.index = index;
            
            item.innerHTML = `
                <div class="flex items-center justify-between mb-3">
                    <h6 class="font-medium text-gray-700">Loại cây #${index + 1}</h6>
                    <button type="button" class="text-red-600 hover:text-red-800 remove-item-btn">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </div>
                
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Chọn loại cây gỗ <span class="text-red-500">*</span></label>
                        <select name="items[${index}][supply_id]" class="supply-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            <option value="">-- Chọn loại cây --</option>
                            ${supplies.map(s => `
                                <option value="${s.id}" 
                                    data-quantity="${s.quantity}" 
                                    data-tree-count="${s.tree_count}"
                                    data-unit="${s.unit}"
                                    ${data.supply_id == s.id ? 'selected' : ''}>
                                    ${s.name} - Còn: ${parseFloat(s.quantity).toFixed(2)} ${s.unit}/cây (${s.tree_count} cây)
                                </option>
                            `).join('')}
                        </select>
                    </div>
                    
                    <div>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-gray-600">Số cây cần dùng:</span>
                                    <span class="font-semibold text-blue-700 ml-2 tree-needed-display">0 cây</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Chiều dài cắt mỗi cây:</span>
                                    <span class="font-semibold text-green-700 ml-2 length-per-tree-display">0 cm</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-xs text-gray-500 supply-info">
                            <i class="fas fa-info-circle mr-1"></i>
                            <span class="remaining-info">Chọn loại cây để xem thông tin kho</span>
                        </p>
                    </div>
                </div>
            `;
            
            document.getElementById('itemsContainer').appendChild(item);
            attachItemEvents(item);
            updateItemCalculations(item);
        }

        function attachItemEvents(item) {
            const supplySelect = item.querySelector('.supply-select');
            const removeBtn = item.querySelector('.remove-item-btn');

            supplySelect.addEventListener('change', () => {
                updateItemCalculations(item);
            });
            
            removeBtn.addEventListener('click', () => {
                if (document.querySelectorAll('#itemsContainer > div').length > 1) {
                    item.remove();
                    // Cập nhật lại tất cả items sau khi xóa
                    document.querySelectorAll('#itemsContainer > div').forEach(item => {
                        updateItemCalculations(item);
                    });
                } else {
                    alert('Phải có ít nhất 1 loại cây gỗ!');
                }
            });
        }

        function updateItemCalculations(item) {
            const supplySelect = item.querySelector('.supply-select');
            const remainingInfo = item.querySelector('.remaining-info');
            const treeNeededDisplay = item.querySelector('.tree-needed-display');
            const lengthPerTreeDisplay = item.querySelector('.length-per-tree-display');

            const selectedOption = supplySelect.options[supplySelect.selectedIndex];
            
            if (selectedOption.value) {
                const availableQuantity = parseFloat(selectedOption.dataset.quantity) || 0;
                const availableTreeCount = parseInt(selectedOption.dataset.treeCount) || 0;
                const unit = selectedOption.dataset.unit || 'cm';
                
                // Lấy thông tin khung
                const metrics = calculateFrameMetrics();
                const itemCount = document.querySelectorAll('#itemsContainer > div').length;
                
                // Tính chiều dài cần cho loại cây này (chia đều tổng cây cần)
                const woodNeededForThisSupply = metrics.totalWoodNeeded / itemCount;
                
                // Tính số cây cần
                const treeQuantity = availableQuantity > 0 ? Math.ceil(woodNeededForThisSupply / availableQuantity) : 0;
                treeNeededDisplay.textContent = treeQuantity + ' cây';
                
                // Chiều dài cắt mỗi cây
                const lengthPerTree = treeQuantity > 0 ? woodNeededForThisSupply / treeQuantity : 0;
                lengthPerTreeDisplay.textContent = lengthPerTree.toFixed(2) + ' cm';
                
                // Thông tin kho
                const remainingTrees = availableTreeCount - treeQuantity;
                const remainingLength = availableQuantity - lengthPerTree;
                
                let infoText = `Kho: ${availableQuantity.toFixed(2)} ${unit}/cây (${availableTreeCount} cây)`;
                
                if (treeQuantity > 0) {
                    if (treeQuantity > availableTreeCount) {
                        infoText += ` → <strong class="text-red-600">KHÔNG ĐỦ! Thiếu ${treeQuantity - availableTreeCount} cây</strong>`;
                    } else {
                        infoText += ` → Còn: <strong>${remainingTrees} cây × ${availableQuantity.toFixed(2)} ${unit}/cây`;
                        if (remainingLength > 0) {
                            infoText += ` + ${treeQuantity} cây × ${remainingLength.toFixed(2)} ${unit}/cây (phần dư)</strong>`;
                        } else {
                            infoText += `</strong>`;
                        }
                    }
                }
                
                remainingInfo.innerHTML = infoText;
            } else {
                treeNeededDisplay.textContent = '0 cây';
                lengthPerTreeDisplay.textContent = '0 cm';
                remainingInfo.textContent = 'Chọn loại cây để xem thông tin kho';
            }
        }

        document.getElementById('addItemBtn').addEventListener('click', () => addItem());

        // Lắng nghe thay đổi kích thước khung và khấu trừ góc
        document.getElementById('frame_length').addEventListener('input', () => {
            calculateFrameMetrics();
            document.querySelectorAll('#itemsContainer > div').forEach(item => {
                updateItemCalculations(item);
            });
        });

        document.getElementById('frame_width').addEventListener('input', () => {
            calculateFrameMetrics();
            document.querySelectorAll('#itemsContainer > div').forEach(item => {
                updateItemCalculations(item);
            });
        });

        document.getElementById('corner_deduction').addEventListener('input', () => {
            calculateFrameMetrics();
            document.querySelectorAll('#itemsContainer > div').forEach(item => {
                updateItemCalculations(item);
            });
        });

        // Thêm item đầu tiên
        document.addEventListener('DOMContentLoaded', () => {
            addItem();
            calculateFrameMetrics();
        });
    </script>
    @endpush
@endsection
