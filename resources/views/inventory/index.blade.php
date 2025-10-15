@extends('layouts.app')

@section('title', 'Quản lý kho')
@section('page-title', 'Quản lý kho')
@section('page-description', 'Quản lý tồn kho tranh và vật tư')

@section('header-actions')
    <div class="flex gap-2">
        <div class="relative">
            <button onclick="toggleExportDropdown()" type="button" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg transition-colors flex items-center">
                <i class="fas fa-download mr-2"></i>Xuất file
                <i class="fas fa-chevron-down ml-2 text-xs"></i>
            </button>
            <div id="exportDropdown" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl z-50 border border-gray-200">
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

        <a href="{{ route('inventory.import.painting.form') }}"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Nhập kho
        </a>
    </div>
@endsection

@section('content')
    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
        <!-- Search and Filter -->
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <form method="GET" action="{{ route('inventory.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tìm kiếm</label>
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}"
                                class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Tìm theo mã, tên sản phẩm...">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Loại</label>
                        <select name="type"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Tất cả</option>
                            <option value="painting" {{ request('type') == 'painting' ? 'selected' : '' }}>Tranh</option>
                            <option value="supply" {{ request('type') == 'supply' ? 'selected' : '' }}>Vật tư</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ngày nhập</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                <div class="flex justify-between items-center mt-4">
                    <button type="submit"
                        class="bg-blue-600 text-white py-2 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-filter mr-2"></i>Lọc
                    </button>
                    <a href="{{ route('inventory.index') }}"
                        class="bg-gray-500 text-white py-2 px-6 rounded-lg hover:bg-gray-600 transition-colors">
                        <i class="fas fa-times mr-2"></i>Xóa lọc
                    </a>
                </div>
            </form>
        </div>

        <!-- Inventory Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium uppercase tracking-wider">Mã</th>
                        <th class="px-4 py-3 text-left font-medium uppercase tracking-wider">Tên sản
                            phẩm</th>
                        <th class="px-4 py-3 text-left font-medium uppercase tracking-wider">Loại</th>
                        <th class="px-4 py-3 text-left font-medium uppercase tracking-wider">Số lượng
                        </th>
                        <th class="px-4 py-3 text-left font-medium uppercase tracking-wider">Ngày nhập
                        </th>
                        <th class="px-4 py-3 text-left font-medium uppercase tracking-wider">Trạng
                            thái</th>
                        <th class="px-4 py-3 text-left font-medium uppercase tracking-wider">Thao tác
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($inventory as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">{{ $item['code'] }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item['name'] }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if ($item['type'] == 'painting')
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-lg bg-purple-100 text-purple-800">Tranh</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-lg bg-blue-100 text-blue-800">Vật
                                        tư</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                {{ $item['quantity'] }}{{ isset($item['unit']) ? ' ' . $item['unit'] : '' }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item['import_date'] }}</td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if ($item['type'] == 'painting')
                                    @if ($item['status'] == 'in_stock')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-lg bg-green-100 text-green-800">
                                            Còn hàng
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-lg bg-gray-200 text-gray-800">
                                            Đã bán
                                        </span>
                                    @endif
                                @elseif ($item['type'] == 'supply')
                                    @if ($item['quantity'] > 0)
                                        <span class="px-2 py-1 text-xs font-semibold rounded-lg bg-green-100 text-green-800">
                                            Còn hàng
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-lg bg-gray-200 text-gray-800">
                                            Hết hàng
                                        </span>
                                    @endif
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-lg bg-gray-200 text-gray-800">
                                        Không xác định
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                @if ($item['type'] == 'painting')
                                    <a href="{{ route('inventory.paintings.show', $item['id']) }}"
                                        class="text-indigo-600 hover:text-indigo-900 mr-3" title="Xem chi tiết">
                                        <i class="fas fa-eye px-3 py-2 rounded-lg bg-blue-100 text-blue-600"></i>
                                    </a>
                                    <a href="{{ route('inventory.paintings.edit', $item['id']) }}"
                                        class="text-yellow-600 hover:text-yellow-900 mr-3" title="Chỉnh sửa">
                                        <i class="fas fa-edit px-3 py-2 rounded-lg bg-yellow-100 text-yellow-600"></i>
                                    </a>
                                    <form action="{{ route('inventory.paintings.destroy', $item['id']) }}" method="POST"
                                        class="inline" onsubmit="return confirm('Xóa tranh này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Xóa">
                                            <i class="fas fa-trash px-3 py-2 rounded-lg bg-red-100 text-red-400"></i>
                                        </button>
                                    </form>
                                @endif
                                @if ($item['type'] == 'supply')
                                    <a href="{{ route('inventory.supplies.show', $item['id']) }}"
                                        class="text-indigo-600 hover:text-indigo-900 mr-3" title="Xem chi tiết">
                                        <i class="fas fa-eye px-3 py-2 rounded-lg bg-blue-100 text-blue-600"></i>
                                    </a>
                                    <a href="{{ route('inventory.supplies.edit', $item['id']) }}"
                                        class="text-yellow-600 hover:text-yellow-900 mr-3" title="Chỉnh sửa">
                                        <i class="fas fa-edit px-3 py-2 rounded-lg bg-yellow-100 text-yellow-600"></i>
                                    </a>
                                @endif
                                @if ($item['type'] == 'supply')
                                    <form action="{{ route('inventory.supplies.destroy', $item['id']) }}" method="POST"
                                        class="inline" onsubmit="return confirm('Xóa vật tư này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Xóa">
                                            <i class="fas fa-trash px-3 py-2 rounded-lg bg-red-100 text-red-400"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>Không có dữ liệu</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $inventory->links() }}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
function toggleExportDropdown() {
    const dropdown = document.getElementById('exportDropdown');
    dropdown.classList.toggle('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('exportDropdown');
    const button = event.target.closest('[onclick="toggleExportDropdown()"]');
    
    if (dropdown && !dropdown.contains(event.target) && !button) {
        dropdown.classList.add('hidden');
    }
});
    </script>
@endpush