@extends('layouts.app')

@section('title', 'Tạo khung tranh')
@section('page-title', 'Tạo khung tranh')
@section('page-description', 'Tạo khung tranh mới từ cây gỗ')

@section('content')
    <!-- Confirm Modal -->
    <x-confirm-modal 
        id="confirm-frame-modal"
        title="Xác nhận tạo khung"
        message="Bạn có chắc chắn muốn tạo khung tranh này?"
        confirmText="Xác nhận"
        cancelText="Quay lại"
        type="info"
    >
        <div id="confirm-frame-summary" class="text-sm"></div>
    </x-confirm-modal>

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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Số lượng tạo <span class="text-xs text-gray-500">(Mặc định: 1)</span></label>
                        <input type="number" name="quantity" value="{{ old('quantity', 1) }}" 
                            min="1" step="1"
                            class="w-full px-3 py-2 border border-blue-300 bg-blue-50 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="VD: 1">
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
                <button type="button" onclick="confirmCreateFrame()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Tạo khung
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        const supplies = @json($supplies);
        let itemIndex = 0;

        function confirmCreateFrame() {
            const name = document.querySelector('input[name="name"]').value.trim();
            if (!name) {
                alert('Vui lòng nhập tên khung!');
                return;
            }
            
            const frameLength = document.getElementById('frame_length').value || '0';
            const frameWidth = document.getElementById('frame_width').value || '0';
            const costPrice = document.querySelector('input[name="cost_price"]').value || '0';
            
            let summaryHtml = `
                <div class="space-y-2">
                    <div class="flex justify-between"><span class="text-gray-600">Tên khung:</span><span class="font-medium">${name}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Kích thước:</span><span class="font-medium">${frameLength} x ${frameWidth} cm</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Giá nhập:</span><span class="font-medium">${parseInt(costPrice).toLocaleString('vi-VN')}đ</span></div>
                </div>
            `;
            
            document.getElementById('confirm-frame-summary').innerHTML = summaryHtml;
            
            showConfirmModal('confirm-frame-modal', {
                title: 'Xác nhận tạo khung tranh',
                message: 'Vui lòng kiểm tra thông tin trước khi lưu:',
                onConfirm: function() {
                    document.getElementById('frameForm').submit();
                }
            });
        }

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
                        <div class="relative">
                            <input type="text" 
                                   id="supply-search-${index}"
                                   class="supply-search w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" 
                                   placeholder="Tìm kiếm loại cây..."
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
            const removeBtn = item.querySelector('.remove-item-btn');
            
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

        // Search and filter supplies
        function filterSupplies(query, index) {
            const suggestions = document.getElementById(`supply-suggestions-${index}`);
            const clearBtn = document.getElementById(`clear-supply-${index}`);
            
            // Show/hide clear button
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
                suggestions.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">Không tìm thấy loại cây nào</div>';
                suggestions.classList.remove('hidden');
            }
        }

        function clearSupplySearch(index) {
            const searchInput = document.getElementById(`supply-search-${index}`);
            const hiddenInput = document.getElementById(`supply-id-${index}`);
            const suggestions = document.getElementById(`supply-suggestions-${index}`);
            const clearBtn = document.getElementById(`clear-supply-${index}`);
            const infoDiv = document.getElementById(`supply-info-${index}`);
            
            // Clear all fields
            searchInput.value = '';
            hiddenInput.value = '';
            suggestions.classList.add('hidden');
            clearBtn.classList.add('hidden');
            infoDiv.classList.add('hidden');
            
            // Clear calculations
            const item = document.querySelector(`[data-index="${index}"]`);
            if (item) {
                const treeNeededDisplay = item.querySelector('.tree-needed-display');
                const lengthPerTreeDisplay = item.querySelector('.length-per-tree-display');
                const remainingInfo = item.querySelector('.remaining-info');
                
                if (treeNeededDisplay) treeNeededDisplay.textContent = '0 cây';
                if (lengthPerTreeDisplay) lengthPerTreeDisplay.textContent = '0 cm';
                if (remainingInfo) remainingInfo.textContent = 'Chọn loại cây để xem thông tin kho';
            }
            
            // Focus back to input
            searchInput.focus();
        }

        function showSupplySuggestions(index) {
            const input = document.getElementById(`supply-search-${index}`);
            const suggestions = document.getElementById(`supply-suggestions-${index}`);
            
            // Show all supplies if no query
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
                
                // Show clear button
                const clearBtn = document.getElementById(`clear-supply-${index}`);
                if (clearBtn) clearBtn.classList.remove('hidden');
                
                // Show supply info
                const infoDiv = document.getElementById(`supply-info-${index}`);
                infoDiv.innerHTML = `<i class="fas fa-info-circle mr-1"></i>Kho: ${parseFloat(supply.quantity).toFixed(2)} ${supply.unit}/cây (${supply.tree_count} cây)`;
                infoDiv.classList.remove('hidden');
                
                // Update calculations
                const item = document.querySelector(`[data-index="${index}"]`);
                if (item) {
                    updateItemCalculations(item);
                }
            }
        }

        // Close suggestions when clicking outside
        document.addEventListener('click', function(e) {
            document.querySelectorAll('[id^="supply-suggestions-"]').forEach(suggestion => {
                const index = suggestion.id.replace('supply-suggestions-', '');
                if (!e.target.closest(`#supply-search-${index}`) && !e.target.closest(`#supply-suggestions-${index}`)) {
                    suggestion.classList.add('hidden');
                }
            });
        });

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
                
                // Lấy số lượng batch
                const batchQuantity = parseInt(document.querySelector('input[name="quantity"]').value) || 1;

                // Tính chiều dài cần cho loại cây này (chia đều tổng cây cần)
                const woodNeededForThisSupplyPerFrame = metrics.totalWoodNeeded / itemCount;
                // Tổng chiều dài cần cho cả batch
                const totalWoodNeededForBatch = woodNeededForThisSupplyPerFrame * batchQuantity;
                
                // Tính số cây cần (Logic: Tổng chiều dài / Chiều dài mỗi cây) -> Không đúng hoàn toàn thực tế cắt,
                // Thực tế: Cần tính số cây cho 1 khung, sau đó nhân với batchQuantity (vì mỗi khung cắt riêng)
                
                // Cách tính chính xác theo backend loop:
                const treePerFrame = availableQuantity > 0 ? Math.ceil(woodNeededForThisSupplyPerFrame / availableQuantity) : 0;
                const totalTreeNeeded = treePerFrame * batchQuantity;

                treeNeededDisplay.textContent = `${totalTreeNeeded} cây (${treePerFrame} cây/khung)`;
                
                // Chiều dài cắt mỗi cây
                const lengthPerTree = treePerFrame > 0 ? woodNeededForThisSupplyPerFrame / treePerFrame : 0;
                lengthPerTreeDisplay.textContent = lengthPerTree.toFixed(2) + ' cm';
                
                // Thông tin kho
                const remainingTrees = availableTreeCount - totalTreeNeeded;
                
                let infoText = `Kho: ${availableQuantity.toFixed(2)} ${unit}/cây (${availableTreeCount} cây)`;
                
                if (totalTreeNeeded > 0) {
                    if (totalTreeNeeded > availableTreeCount) {
                        infoText += ` → <strong class="text-red-600">KHÔNG ĐỦ! Thiếu ${totalTreeNeeded - availableTreeCount} cây (Cần: ${totalTreeNeeded})</strong>`;
                    } else {
                        infoText += ` → Còn: <strong>${remainingTrees} cây</strong> (Sau khi trừ ${totalTreeNeeded} cây cho ${batchQuantity} khung)`;
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

        // Lắng nghe thay đổi số lượng
        document.querySelector('input[name="quantity"]').addEventListener('input', () => {
             document.querySelectorAll('#itemsContainer > div').forEach(item => {
                updateItemCalculations(item);
            });
        });

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
