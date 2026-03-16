@extends('layouts.app')

@section('title', 'Kho hàng gia công')
@section('page-title', 'Kho hàng gia công')
@section('page-description', 'Quản lý các sản phẩm gia công (Hàng order)')

@section('header-actions')
    <div class="flex gap-2">
        {{-- Create button removed as per user request --}}
    </div>
@endsection

@section('content')
    <div class="bg-white rounded-xl shadow-lg p-4 glass-effect">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <!-- Search and Filter -->
        <div class="bg-gray-50 p-3 rounded-lg mb-4">
            <form method="GET" action="{{ route('inventory.processed-items.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Tìm kiếm</label>
                        <div class="relative">
                            <input type="text" name="search" value="{{ $search ?? '' }}"
                                class="w-full pl-8 pr-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Tìm theo mã, tên, số hóa đơn...">
                            <i class="fas fa-search absolute left-2 top-2 text-xs text-gray-400"></i>
                        </div>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit"
                            class="bg-blue-600 text-white py-1.5 px-4 text-sm rounded-lg hover:bg-blue-700 transition-colors flex-1 text-center">
                            <i class="fas fa-filter mr-1"></i>Lọc
                        </button>
                        <a href="{{ route('inventory.processed-items.index') }}"
                            class="bg-gray-500 text-white py-1.5 px-3 text-sm rounded-lg hover:bg-gray-600 transition-colors text-center">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Bulk Actions Bar -->
        <div id="bulkActionsBar" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4 flex items-center justify-between">
            <div class="flex items-center">
                <span class="text-sm text-blue-800">
                    <i class="fas fa-check-square mr-1"></i>
                    Đã chọn <span id="selectedCount" class="font-bold">0</span> mục
                </span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="clearSelection()" class="text-gray-600 hover:text-gray-800 text-sm px-3 py-1.5 rounded border border-gray-300 hover:bg-gray-100">
                    <i class="fas fa-times mr-1"></i>Bỏ chọn
                </button>
                <button type="button" onclick="confirmBulkDelete()" class="bg-red-600 hover:bg-red-700 text-white text-sm px-3 py-1.5 rounded">
                    <i class="fas fa-trash mr-1"></i>Xóa đã chọn
                </button>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white">
                    <tr>
                        <th class="px-2 py-2 text-center text-xs font-medium uppercase tracking-wider w-10">
                            <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" title="Chọn tất cả">
                        </th>
                        <th class="px-2 py-2 text-center text-xs font-medium uppercase tracking-wider w-12">STT</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Mã</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Tên sản phẩm</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Loại</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Số lượng</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Ngày nhập</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Ngày xuất</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Số hóa đơn</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Trạng thái</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($items as $index => $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 py-2 whitespace-nowrap text-center">
                                <input type="checkbox" class="item-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" 
                                    data-id="{{ $item['id'] }}" data-type="{{ $item['type'] }}">
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-center text-gray-500">
                                {{ $items->firstItem() + $index }}
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs font-medium text-indigo-600">{{ $item['code'] }}</td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">{{ $item['name'] }}</td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">
                                <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-orange-100 text-orange-800">Gia công</span>
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs font-semibold text-gray-900">
                                {{ is_numeric($item['quantity']) ? rtrim(rtrim(number_format($item['quantity'], 2), '0'), '.') : $item['quantity'] }} {{ $item['unit'] }}
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">{{ $item['import_date'] }}</td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">
                                @if(isset($item['export_date']) && $item['export_date'])
                                    {{ $item['export_date'] }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-xs text-gray-900">
                                @if(isset($item['sales']) && $item['sales']->isNotEmpty())
                                    @foreach($item['sales'] as $sale)
                                        <a href="{{ route('sales.show', $sale->id) }}"
                                            class="text-blue-600 hover:text-blue-800 hover:underline font-medium"
                                            title="Xem chi tiết hóa đơn">
                                            {{ $sale->invoice_code }}
                                        </a>
                                        @if(!$loop->last), @endif
                                    @endforeach
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                @if ($item['quantity'] > 0)
                                    <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-green-100 text-green-800">Còn hàng</span>
                                @elseif ($item['quantity'] < 0)
                                    <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-red-100 text-red-800">Âm kho</span>
                                @else
                                    <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-gray-200 text-gray-800">Hết hàng</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs">
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('inventory.processed-items.show', $item['id']) }}" class="text-blue-600 hover:text-blue-900" title="Xem chi tiết">
                                        <i class="fas fa-eye px-2 py-1.5 rounded bg-blue-100 text-blue-400 text-xs"></i>
                                    </a>

                                    @if($item['sales']->isEmpty())
                                        {{-- Edit button removed as per user request --}}

                                        <form action="{{ route('inventory.processed-items.destroy', $item['id']) }}" method="POST"
                                            class="inline" onsubmit="return confirm('Xóa sản phẩm này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Xóa">
                                                <i class="fas fa-trash px-2 py-1.5 rounded bg-red-100 text-red-400 text-xs"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-2 py-6 text-center text-gray-500">
                                <i class="fas fa-inbox text-3xl mb-2"></i>
                                <p class="text-sm">Không có dữ liệu</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Bulk Delete Form (Hidden) -->
        <form id="bulkDeleteForm" action="{{ route('inventory.processed-items.bulk-delete') }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
            <input type="hidden" name="items" id="bulkDeleteItems">
        </form>

        <div class="mt-3">
            {{ $items->links() }}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const bulkActionsBar = document.getElementById('bulkActionsBar');
        const selectedCountEl = document.getElementById('selectedCount');
        const selectAll = document.getElementById('selectAll');
        const bulkDeleteForm = document.getElementById('bulkDeleteForm');
        const bulkDeleteItemsInput = document.getElementById('bulkDeleteItems');

        function updateBulkActionsBar() {
            const checkedItems = document.querySelectorAll('.item-checkbox:checked');
            const count = checkedItems.length;
            
            if (count > 0) {
                bulkActionsBar.classList.remove('hidden');
                selectedCountEl.textContent = count;
            } else {
                bulkActionsBar.classList.add('hidden');
            }
            
            // Sync select all checkbox
            const totalItems = document.querySelectorAll('.item-checkbox').length;
            if (selectAll) {
                selectAll.checked = count === totalItems && totalItems > 0;
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.item-checkbox');
                checkboxes.forEach(cb => {
                    cb.checked = selectAll.checked;
                });
                updateBulkActionsBar();
            });
        }

        document.querySelectorAll('.item-checkbox').forEach(cb => {
            cb.addEventListener('change', updateBulkActionsBar);
        });

        function clearSelection() {
            document.querySelectorAll('.item-checkbox').forEach(cb => {
                cb.checked = false;
            });
            if (selectAll) selectAll.checked = false;
            updateBulkActionsBar();
        }

        function confirmBulkDelete() {
            const checkedItems = document.querySelectorAll('.item-checkbox:checked');
            if (checkedItems.length === 0) return;

            if (confirm(`Bạn có chắc chắn muốn xóa ${checkedItems.length} mục đã chọn?`)) {
                const items = Array.from(checkedItems).map(cb => ({
                    id: cb.getAttribute('data-id'),
                    type: cb.getAttribute('data-type')
                }));
                
                bulkDeleteItemsInput.value = JSON.stringify(items);
                bulkDeleteForm.submit();
            }
        }
    </script>
@endpush
