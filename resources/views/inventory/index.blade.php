@extends('layouts.app')

@section('title', 'Quản lý kho')
@section('page-title', 'Quản lý kho')
@section('page-description', 'Quản lý tồn kho tranh và vật tư')

@section('header-actions')
    <div class="flex gap-2">
        @hasPermission('inventory', 'can_export')
        <div class="relative">
            <button onclick="toggleExportDropdown()" type="button"
                class="bg-green-500 hover:bg-green-600 text-white px-4 py-1.5 text-sm rounded-lg transition-colors flex items-center">
                <i class="fas fa-download mr-1"></i>Xuất file
                <i class="fas fa-chevron-down ml-1 text-xs"></i>
            </button>
            <div id="exportDropdown"
                class="hidden absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl z-50 border border-gray-200">
                <!-- Excel Export -->
                <div class="py-2 border-b border-gray-200">
                    <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Excel</div>
                    <a href="{{ route('inventory.export.excel', array_merge(request()->query(), ['scope' => 'current'])) }}"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 transition-colors">
                        <i class="fas fa-file-excel text-green-600 mr-2"></i>Trang hiện tại
                    </a>
                    <a href="{{ route('inventory.export.excel', array_merge(request()->query(), ['scope' => 'all'])) }}"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 transition-colors">
                        <i class="fas fa-file-excel text-green-600 mr-2"></i>Tất cả kết quả
                    </a>
                </div>
                <!-- PDF Export -->
                <div class="py-2">
                    <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">PDF</div>
                    <a href="{{ route('inventory.export.pdf', array_merge(request()->query(), ['scope' => 'current'])) }}"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-red-50 transition-colors">
                        <i class="fas fa-file-pdf text-red-600 mr-2"></i>Trang hiện tại
                    </a>
                    <a href="{{ route('inventory.export.pdf', array_merge(request()->query(), ['scope' => 'all'])) }}"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-red-50 transition-colors">
                        <i class="fas fa-file-pdf text-red-600 mr-2"></i>Tất cả kết quả
                    </a>
                </div>
            </div>
        </div>
        @endhasPermission

        @hasPermission('inventory', 'can_create')
        <a href="{{ route('inventory.import.painting.form') }}"
            class="bg-blue-600 text-white px-3 py-1.5 text-sm rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-plus mr-1"></i>Nhập kho
        </a>
        @endhasPermission
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
            <form method="GET" action="{{ route('inventory.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Tìm kiếm</label>
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}"
                                class="w-full pl-8 pr-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Tìm theo mã, tên, số hóa đơn...">
                            <i class="fas fa-search absolute left-2 top-2 text-xs text-gray-400"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Loại</label>
                        <select name="type"
                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Tất cả</option>
                            <option value="painting" {{ request('type') == 'painting' ? 'selected' : '' }}>Tranh</option>
                            <option value="supply" {{ request('type') == 'supply' ? 'selected' : '' }}>Vật tư</option>
                        </select>
                    </div>
                    @hasPermission('inventory', 'can_filter_by_date')
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Ngày nhập</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    @endhasPermission
                </div>
                <div class="flex justify-between items-center mt-3">
                    <button type="submit"
                        class="bg-blue-600 text-white py-1.5 px-4 text-sm rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-filter mr-1"></i>Lọc
                    </button>
                    <a href="{{ route('inventory.index') }}"
                        class="bg-gray-500 text-white py-1.5 px-4 text-sm rounded-lg hover:bg-gray-600 transition-colors">
                        <i class="fas fa-times mr-1"></i>Xóa lọc
                    </a>
                </div>
            </form>
        </div>

        <!-- Inventory Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white">
                    <tr>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Mã</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Tên sản
                            phẩm</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Loại</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Số lượng
                        </th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Ngày nhập
                        </th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Số hóa đơn
                        </th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Trạng
                            thái</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Thao tác
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($inventory as $item)
                        <tr class="hover:bg-gray-50">

                            <td class="px-2 py-2 whitespace-nowrap text-xs font-medium text-indigo-600">{{ $item['code'] }}
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">{{ $item['name'] }}</td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">
                                @if ($item['type'] == 'painting')
                                    <span
                                        class="px-1.5 py-0.5 text-xs font-semibold rounded bg-purple-100 text-purple-800">Tranh</span>
                                @else
                                    <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-blue-100 text-blue-800">Vật
                                        tư</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs font-semibold text-gray-900">
                                @if($item['type'] == 'supply' && isset($item['supply_type']) && $item['supply_type'] == 'frame' && isset($item['tree_count']) && $item['tree_count'] > 0)
                                    <div class="flex flex-col">
                                        <span class="text-blue-600">{{ $item['tree_count'] }} cây × {{ $item['quantity'] }}{{ isset($item['unit']) ? $item['unit'] : '' }}/cây</span>
                                        @php
                                            $totalLength = $item['tree_count'] * $item['quantity'];
                                        @endphp
                                        <span class="text-green-600 text-sm">= {{ number_format($totalLength, 2) }}{{ isset($item['unit']) ? $item['unit'] : '' }} tổng</span>
                                    </div>
                                @else
                                    {{ $item['quantity'] }}{{ isset($item['unit']) ? ' ' . $item['unit'] : '' }}
                                @endif
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">{{ $item['import_date'] }}</td>
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
                                @if ($item['type'] == 'painting')
                                    @if ($item['status'] == 'in_stock')
                                        <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-green-100 text-green-800">
                                            Còn hàng
                                        </span>
                                    @else
                                        <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-gray-200 text-gray-800">
                                            Đã bán
                                        </span>
                                    @endif
                                @elseif ($item['type'] == 'supply')
                                    @if ($item['quantity'] > 0)
                                        <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-green-100 text-green-800">
                                            Còn hàng
                                        </span>
                                    @else
                                        <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-gray-200 text-gray-800">
                                            Hết hàng
                                        </span>
                                    @endif
                                @else
                                    <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-gray-200 text-gray-800">
                                        Không xác định
                                    </span>
                                @endif
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs">
                                @if ($item['type'] == 'painting')
                                    @hasPermission('inventory', 'can_view')
                                    <a href="{{ route('inventory.paintings.show', $item['id']) }}"
                                        class="text-indigo-600 hover:text-indigo-900 mr-2" title="Xem chi tiết">
                                        <i class="fas fa-eye px-2 py-1.5 rounded bg-blue-100 text-blue-600 text-xs"></i>
                                    </a>
                                    @endhasPermission

                                    @hasPermission('inventory', 'can_edit')
                                    @if($item['status'] == 'in_stock')
                                        <a href="{{ route('inventory.paintings.edit', ['id' => $item['id'], 'return_url' => request()->fullUrl()]) }}"
                                            class="text-yellow-600 hover:text-yellow-900 mr-2" title="Chỉnh sửa">
                                            <i class="fas fa-edit px-2 py-1.5 rounded bg-yellow-100 text-yellow-600 text-xs"></i>
                                        </a>
                                    @else
                                        <span class="text-gray-400 mr-2" title="Không thể sửa tranh đã bán">
                                            <i
                                                class="fas fa-edit px-2 py-1.5 rounded bg-gray-100 text-gray-400 text-xs cursor-not-allowed"></i>
                                        </span>
                                    @endif
                                    @endhasPermission

                                    @hasPermission('inventory', 'can_delete')
                                    @if($item['status'] == 'in_stock')
                                        <form action="{{ route('inventory.paintings.destroy', $item['id']) }}" method="POST"
                                            class="inline" onsubmit="return confirm('Xóa tranh này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Xóa">
                                                <i class="fas fa-trash px-2 py-1.5 rounded bg-red-100 text-red-400 text-xs"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-400" title="Không thể xóa tranh đã bán">
                                            <i
                                                class="fas fa-trash px-2 py-1.5 rounded bg-gray-100 text-gray-400 text-xs cursor-not-allowed"></i>
                                        </span>
                                    @endif
                                    @endhasPermission
                                @endif

                                @if ($item['type'] == 'supply')
                                    @hasPermission('inventory', 'can_view')
                                    <a href="{{ route('inventory.supplies.show', $item['id']) }}"
                                        class="text-indigo-600 hover:text-indigo-900 mr-2" title="Xem chi tiết">
                                        <i class="fas fa-eye px-2 py-1.5 rounded bg-blue-100 text-blue-600 text-xs"></i>
                                    </a>
                                    @endhasPermission

                                    @hasPermission('inventory', 'can_edit')
                                    <a href="{{ route('inventory.supplies.edit', $item['id']) }}"
                                        class="text-yellow-600 hover:text-yellow-900 mr-2" title="Chỉnh sửa">
                                        <i class="fas fa-edit px-2 py-1.5 rounded bg-yellow-100 text-yellow-600 text-xs"></i>
                                    </a>
                                    @endhasPermission

                                    @hasPermission('inventory', 'can_delete')
                                    @if($item['quantity'] > 0)
                                        <form action="{{ route('inventory.supplies.destroy', $item['id']) }}" method="POST"
                                            class="inline" onsubmit="return confirm('Xóa vật tư này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Xóa">
                                                <i class="fas fa-trash px-2 py-1.5 rounded bg-red-100 text-red-400 text-xs"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-400" title="Không thể xóa vật tư đã sử dụng hết">
                                            <i
                                                class="fas fa-trash px-2 py-1.5 rounded bg-gray-100 text-gray-400 text-xs cursor-not-allowed"></i>
                                        </span>
                                    @endif
                                    @endhasPermission
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-2 py-6 text-center text-gray-500">
                                <i class="fas fa-inbox text-3xl mb-2"></i>
                                <p class="text-sm">Không có dữ liệu</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $inventory->links() }}
        </div>
    </div>
@endsection

<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center p-4"
    onclick="closeImageModal()">
    <div class="relative max-w-4xl max-h-full" onclick="event.stopPropagation()">
        <button onclick="closeImageModal()" class="absolute -top-10 right-0 text-white hover:text-gray-300">
            <i class="fas fa-times text-2xl"></i>
        </button>
        <img id="modalImage" src="" alt="" class="max-w-full max-h-[90vh] object-contain rounded-lg">
        <p id="modalImageTitle" class="text-white text-center mt-4 text-lg"></p>
    </div>
</div>

@push('scripts')
    <script>
        function toggleExportDropdown() {
            const dropdown = document.getElementById('exportDropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function (event) {
            const dropdown = document.getElementById('exportDropdown');
            const button = event.target.closest('[onclick="toggleExportDropdown()"]');

            if (dropdown && !dropdown.contains(event.target) && !button) {
                dropdown.classList.add('hidden');
            }
        });

        function showImageModal(imageSrc, imageTitle) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            const modalTitle = document.getElementById('modalImageTitle');

            modalImage.src = imageSrc;
            modalTitle.textContent = imageTitle;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close modal with ESC key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeImageModal();
            }
        });
    </script>
@endpush