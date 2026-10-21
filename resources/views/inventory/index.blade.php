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
                        class="export-excel-link block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 transition-colors">
                        <i class="fas fa-file-excel text-green-600 mr-2"></i>Trang hiện tại
                    </a>
                    <a href="{{ route('inventory.export.excel', array_merge(request()->query(), ['scope' => 'all'])) }}"
                        class="export-excel-link block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 transition-colors">
                        <i class="fas fa-file-excel text-green-600 mr-2"></i>Tất cả kết quả
                    </a>
                </div>
                <!-- PDF Export -->
                <div class="py-2">
                    <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">PDF</div>
                    <a href="{{ route('inventory.export.pdf', array_merge(request()->query(), ['scope' => 'current'])) }}"
                        class="export-pdf-link block px-4 py-2 text-sm text-gray-700 hover:bg-red-50 transition-colors">
                        <i class="fas fa-file-pdf text-red-600 mr-2"></i>Trang hiện tại
                    </a>
                    <a href="{{ route('inventory.export.pdf', array_merge(request()->query(), ['scope' => 'all'])) }}"
                        class="export-pdf-link block px-4 py-2 text-sm text-gray-700 hover:bg-red-50 transition-colors">
                        <i class="fas fa-file-pdf text-red-600 mr-2"></i>Tất cả kết quả
                    </a>
                </div>
            </div>
        </div>
        @endhasPermission

        @notArchive
        @hasPermission('inventory', 'can_create')
        <a href="{{ route('inventory.import.painting.form') }}"
            class="bg-blue-600 text-white px-3 py-1.5 text-sm rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-plus mr-1"></i>Nhập kho
        </a>
        @endhasPermission
        @endnotArchive
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

        @if(session('warning'))
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                {{ session('warning') }}
            </div>
        @endif

        @if(session('import_errors'))
            <div class="bg-orange-100 border border-orange-400 text-orange-700 px-4 py-3 rounded mb-4">
                <p class="font-semibold mb-2">Chi tiết lỗi import:</p>
                <ul class="list-disc list-inside text-sm space-y-1">
                    @foreach(session('import_errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
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

        <!-- Bulk Actions Bar -->
        @hasPermission('inventory', 'can_delete')
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
        @endhasPermission

        <!-- Table Toolbar: Column Selection Button (Sliders) -->
        <div class="flex justify-between items-center mb-3">
            <div class="text-xs text-gray-500 font-medium">
                Hiển thị <span class="font-bold text-gray-700">{{ $inventory->count() }}</span> / {{ $inventory->total() }} kết quả
            </div>
            <div class="relative inline-block text-left">
                <button id="columnToggleBtn" type="button" onclick="toggleColumnMenu(event)"
                    class="p-2 border border-gray-300 rounded-xl bg-white hover:bg-gray-50 text-gray-600 hover:text-blue-600 shadow-sm transition-all flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-blue-500"
                    title="Cột hiển thị & thứ tự">
                    <i class="fas fa-sliders-h text-base"></i>
                </button>

                <!-- Column Toggle Dropdown (Matches Design Mockup) -->
                <div id="columnToggleMenu" 
                     class="hidden absolute right-0 mt-2 w-72 sm:w-80 bg-white rounded-xl shadow-2xl border border-gray-200 z-50 p-3.5 transition-all">
                    <div class="flex items-center justify-between pb-2.5 mb-2.5 border-b border-gray-200">
                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">CỘT HIỂN THỊ & THỨ TỰ</span>
                        <button type="button" onclick="resetDefaultColumns()" class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                            Mặc định
                        </button>
                    </div>
                    <div class="grid grid-cols-2 gap-x-2 gap-y-1.5 max-h-80 overflow-y-auto pr-1">
                        <label class="flex items-center px-1.5 py-1 hover:bg-gray-50 rounded cursor-pointer select-none">
                            <input type="checkbox" data-target-col="stt" class="col-toggle-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2 h-4 w-4">
                            <span class="text-xs text-gray-700 font-medium">STT</span>
                        </label>
                        <label class="flex items-center px-1.5 py-1 hover:bg-gray-50 rounded cursor-pointer select-none">
                            <input type="checkbox" data-target-col="code" class="col-toggle-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2 h-4 w-4">
                            <span class="text-xs text-gray-700 font-medium">Mã</span>
                        </label>
                        <label class="flex items-center px-1.5 py-1 hover:bg-gray-50 rounded cursor-pointer select-none">
                            <input type="checkbox" data-target-col="name" class="col-toggle-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2 h-4 w-4">
                            <span class="text-xs text-gray-700 font-medium">Tên sản phẩm</span>
                        </label>
                        <label class="flex items-center px-1.5 py-1 hover:bg-gray-50 rounded cursor-pointer select-none">
                            <input type="checkbox" data-target-col="type" class="col-toggle-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2 h-4 w-4">
                            <span class="text-xs text-gray-700 font-medium">Loại</span>
                        </label>
                        <label class="flex items-center px-1.5 py-1 hover:bg-gray-50 rounded cursor-pointer select-none">
                            <input type="checkbox" data-target-col="artist" class="col-toggle-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2 h-4 w-4">
                            <span class="text-xs text-gray-700 font-medium">Họa sĩ</span>
                        </label>
                        <label class="flex items-center px-1.5 py-1 hover:bg-gray-50 rounded cursor-pointer select-none">
                            <input type="checkbox" data-target-col="material" class="col-toggle-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2 h-4 w-4">
                            <span class="text-xs text-gray-700 font-medium">Chất liệu</span>
                        </label>
                        <label class="flex items-center px-1.5 py-1 hover:bg-gray-50 rounded cursor-pointer select-none">
                            <input type="checkbox" data-target-col="dimensions" class="col-toggle-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2 h-4 w-4">
                            <span class="text-xs text-gray-700 font-medium">Kích thước</span>
                        </label>
                        <label class="flex items-center px-1.5 py-1 hover:bg-gray-50 rounded cursor-pointer select-none">
                            <input type="checkbox" data-target-col="paint_year" class="col-toggle-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2 h-4 w-4">
                            <span class="text-xs text-gray-700 font-medium">Năm SX</span>
                        </label>
                        <label class="flex items-center px-1.5 py-1 hover:bg-gray-50 rounded cursor-pointer select-none">
                            <input type="checkbox" data-target-col="price_usd" class="col-toggle-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2 h-4 w-4">
                            <span class="text-xs text-gray-700 font-medium">Giá USD</span>
                        </label>
                        <label class="flex items-center px-1.5 py-1 hover:bg-gray-50 rounded cursor-pointer select-none">
                            <input type="checkbox" data-target-col="price_vnd" class="col-toggle-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2 h-4 w-4">
                            <span class="text-xs text-gray-700 font-medium">Giá VND</span>
                        </label>
                        <label class="flex items-center px-1.5 py-1 hover:bg-gray-50 rounded cursor-pointer select-none">
                            <input type="checkbox" data-target-col="quantity" class="col-toggle-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2 h-4 w-4">
                            <span class="text-xs text-gray-700 font-medium">Số lượng</span>
                        </label>
                        <label class="flex items-center px-1.5 py-1 hover:bg-gray-50 rounded cursor-pointer select-none">
                            <input type="checkbox" data-target-col="import_date" class="col-toggle-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2 h-4 w-4">
                            <span class="text-xs text-gray-700 font-medium">Ngày nhập</span>
                        </label>
                        <label class="flex items-center px-1.5 py-1 hover:bg-gray-50 rounded cursor-pointer select-none">
                            <input type="checkbox" data-target-col="export_date" class="col-toggle-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2 h-4 w-4">
                            <span class="text-xs text-gray-700 font-medium">Ngày xuất</span>
                        </label>
                        <label class="flex items-center px-1.5 py-1 hover:bg-gray-50 rounded cursor-pointer select-none">
                            <input type="checkbox" data-target-col="invoice_code" class="col-toggle-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2 h-4 w-4">
                            <span class="text-xs text-gray-700 font-medium">Số hóa đơn</span>
                        </label>
                        <label class="flex items-center px-1.5 py-1 hover:bg-gray-50 rounded cursor-pointer select-none">
                            <input type="checkbox" data-target-col="status" class="col-toggle-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2 h-4 w-4">
                            <span class="text-xs text-gray-700 font-medium">Trạng thái</span>
                        </label>
                        <label class="flex items-center px-1.5 py-1 hover:bg-gray-50 rounded cursor-pointer select-none">
                            <input type="checkbox" data-target-col="action" class="col-toggle-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2 h-4 w-4">
                            <span class="text-xs text-gray-700 font-medium">Thao tác</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white">
                    <tr>
                        @hasPermission('inventory', 'can_delete')
                        <th class="px-2 py-2 text-center text-xs font-medium uppercase tracking-wider w-10">
                            <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" title="Chọn tất cả">
                        </th>
                        @endhasPermission
                        <th data-col="stt" class="px-2 py-2 text-center text-xs font-medium uppercase tracking-wider w-12">STT</th>
                        <th data-col="code" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Mã</th>
                        <th data-col="name" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Tên sản phẩm</th>
                        <th data-col="type" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Loại</th>
                        <th data-col="artist" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Họa sĩ</th>
                        <th data-col="material" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Chất liệu</th>
                        <th data-col="dimensions" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Kích thước</th>
                        <th data-col="paint_year" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Năm SX</th>
                        <th data-col="price_usd" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Giá USD</th>
                        <th data-col="price_vnd" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Giá VND</th>
                        <th data-col="quantity" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Số lượng</th>
                        <th data-col="import_date" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Ngày nhập</th>
                        <th data-col="export_date" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Ngày xuất</th>
                        <th data-col="invoice_code" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Số hóa đơn</th>
                        <th data-col="status" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Trạng thái</th>
                        <th data-col="action" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($inventory as $index => $item)
                        @php
                            $canDelete = ($item['type'] == 'painting' && $item['status'] == 'in_stock') || 
                                        ($item['type'] == 'supply' && $item['quantity'] > 0);
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            @hasPermission('inventory', 'can_delete')
                            <td class="px-2 py-2 whitespace-nowrap text-center">
                                @if($canDelete)
                                    <input type="checkbox" class="item-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" 
                                        data-id="{{ $item['id'] }}" data-type="{{ $item['type'] }}" data-name="{{ $item['code'] }}">
                                @else
                                    <input type="checkbox" disabled class="rounded border-gray-200 text-gray-300 cursor-not-allowed" title="Không thể xóa">
                                @endif
                            </td>
                            @endhasPermission

                            {{-- STT --}}
                            <td data-col="stt" class="px-2 py-2 whitespace-nowrap text-xs text-center text-gray-500">
                                {{ $inventory->firstItem() + $index }}
                            </td>

                            {{-- Mã --}}
                            <td data-col="code" class="px-2 py-2 whitespace-nowrap text-xs font-semibold text-indigo-600">
                                {{ $item['code'] }}
                            </td>

                            {{-- Tên sản phẩm --}}
                            <td data-col="name" class="px-2 py-2 whitespace-nowrap text-xs font-medium text-gray-900">
                                {{ $item['name'] }}
                            </td>

                            {{-- Loại --}}
                            <td data-col="type" class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">
                                @if ($item['type'] == 'painting')
                                    <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-purple-100 text-purple-800">Tranh</span>
                                @else
                                    <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-blue-100 text-blue-800">Vật tư</span>
                                @endif
                            </td>

                            {{-- Họa sĩ --}}
                            <td data-col="artist" class="px-2 py-2 whitespace-nowrap text-xs text-gray-800">
                                {{ $item['artist'] ?? '-' }}
                            </td>

                            {{-- Chất liệu --}}
                            <td data-col="material" class="px-2 py-2 whitespace-nowrap text-xs text-gray-800">
                                @if ($item['type'] == 'painting' && !empty($item['material']))
                                    @switch($item['material'])
                                        @case('son_dau') Sơn dầu @break
                                        @case('canvas') Canvas @break
                                        @case('thuy_mac') Thủy mặc @break
                                        @case('acrylic') Acrylic @break
                                        @default {{ $item['material'] }}
                                    @endswitch
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            {{-- Kích thước --}}
                            <td data-col="dimensions" class="px-2 py-2 whitespace-nowrap text-xs text-gray-800">
                                @if (!empty($item['dimensions']))
                                    {{ $item['dimensions'] }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            {{-- Năm sản xuất --}}
                            <td data-col="paint_year" class="px-2 py-2 whitespace-nowrap text-xs text-gray-800">
                                {{ $item['paint_year'] ?? '-' }}
                            </td>

                            {{-- Giá USD --}}
                            <td data-col="price_usd" class="px-2 py-2 whitespace-nowrap text-xs font-semibold text-blue-600">
                                @if (!empty($item['price_usd']))
                                    ${{ number_format($item['price_usd'], 2) }}
                                @else
                                    <span class="text-gray-400 font-normal">-</span>
                                @endif
                            </td>

                            {{-- Giá VND --}}
                            <td data-col="price_vnd" class="px-2 py-2 whitespace-nowrap text-xs font-semibold text-green-600">
                                @if (!empty($item['price_vnd']))
                                    {{ number_format($item['price_vnd']) }}đ
                                @else
                                    <span class="text-gray-400 font-normal">-</span>
                                @endif
                            </td>

                            {{-- Số lượng --}}
                            <td data-col="quantity" class="px-2 py-2 whitespace-nowrap text-xs font-semibold text-gray-900">
                                @if($item['type'] == 'supply' && isset($item['supply_type']) && $item['supply_type'] == 'frame' && isset($item['tree_count']) && $item['tree_count'] > 0)
                                    <div class="flex flex-col">
                                        <span class="text-blue-600">{{ $item['tree_count'] }} cây × {{ rtrim(rtrim(number_format($item['quantity'], 2), '0'), '.') }}{{ isset($item['unit']) ? $item['unit'] : '' }}/cây</span>
                                        @php
                                            $totalLength = $item['tree_count'] * $item['quantity'];
                                        @endphp
                                        <span class="text-green-600 text-sm">= {{ rtrim(rtrim(number_format($totalLength, 2), '0'), '.') }}{{ isset($item['unit']) ? $item['unit'] : '' }} tổng</span>
                                    </div>
                                @else
                                    {{ is_numeric($item['quantity']) ? rtrim(rtrim(number_format($item['quantity'], 2), '0'), '.') : $item['quantity'] }}{{ isset($item['unit']) ? ' ' . $item['unit'] : '' }}
                                @endif
                            </td>

                            {{-- Ngày nhập --}}
                            <td data-col="import_date" class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">
                                {{ $item['import_date'] ?? '-' }}
                            </td>

                            {{-- Ngày xuất --}}
                            <td data-col="export_date" class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">
                                @if(isset($item['export_date']) && $item['export_date'])
                                    {{ $item['export_date'] }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            {{-- Số hóa đơn --}}
                            <td data-col="invoice_code" class="px-2 py-2 text-xs text-gray-900">
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

                            {{-- Trạng thái --}}
                            <td data-col="status" class="px-2 py-2 whitespace-nowrap">
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

                            {{-- Thao tác --}}
                            <td data-col="action" class="px-2 py-2 whitespace-nowrap text-xs">
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
                                            <i class="fas fa-edit px-2 py-1.5 rounded bg-gray-100 text-gray-400 text-xs cursor-not-allowed"></i>
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
                                            <i class="fas fa-trash px-2 py-1.5 rounded bg-gray-100 text-gray-400 text-xs cursor-not-allowed"></i>
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
                                            <i class="fas fa-trash px-2 py-1.5 rounded bg-gray-100 text-gray-400 text-xs cursor-not-allowed"></i>
                                        </span>
                                    @endif
                                    @endhasPermission
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="100" class="px-2 py-6 text-center text-gray-500">
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

    <!-- Image Preview Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center p-4" onclick="closeImageModal()">
        <div class="relative max-w-4xl max-h-full" onclick="event.stopPropagation()">
            <button onclick="closeImageModal()" class="absolute -top-10 right-0 text-white hover:text-gray-300">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <img id="modalImage" src="" alt="" class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl">
            <p id="modalImageTitle" class="text-white text-center mt-3 text-base font-medium"></p>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function showImageModal(imageSrc, imageTitle) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            const modalTitle = document.getElementById('modalImageTitle');
            if (modalImage) modalImage.src = imageSrc || '';
            if (modalTitle) modalTitle.textContent = imageTitle || '';
            if (modal) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            if (modal) modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeImageModal();
            }
        });

        function toggleExportDropdown() {
            const dropdown = document.getElementById('exportDropdown');
            dropdown.classList.toggle('hidden');
        }

        // ==========================================
        // Column Visibility & Ordering Management
        // ==========================================
        const COLUMN_STORAGE_KEY = 'art_gallery_inventory_columns_v5';
        const ALL_COLUMNS = [
            'stt', 'code', 'name', 'type', 'artist', 'material',
            'dimensions', 'paint_year', 'price_usd', 'price_vnd',
            'quantity', 'import_date', 'export_date', 'invoice_code', 'status', 'action'
        ];
        // Mặc định hiển thị tất cả các cột
        const DEFAULT_COLUMNS = [...ALL_COLUMNS];

        function getStoredColumns() {
            try {
                const stored = localStorage.getItem(COLUMN_STORAGE_KEY);
                if (stored) {
                    const parsed = JSON.parse(stored);
                    if (Array.isArray(parsed) && parsed.length > 0) {
                        return parsed;
                    }
                }
            } catch (e) {
                console.error(e);
            }
            return DEFAULT_COLUMNS;
        }

        function applyColumns(columns) {
            const colSet = new Set(columns);

            // Cập nhật trạng thái các checkbox
            document.querySelectorAll('.col-toggle-checkbox').forEach(cb => {
                cb.checked = colSet.has(cb.dataset.targetCol);
            });

            // Cập nhật hiển thị các cột trong bảng (th & td)
            ALL_COLUMNS.forEach(colKey => {
                const isVisible = colSet.has(colKey);
                const elements = document.querySelectorAll(`[data-col="${colKey}"]`);
                elements.forEach(el => {
                    if (isVisible) {
                        el.classList.remove('hidden');
                    } else {
                        el.classList.add('hidden');
                    }
                });
            });

            // Đồng bộ danh sách cột sang đường link Xuất file Excel & PDF
            // Xuất Excel và PDF luôn kèm cột 'image' (hình ảnh) theo yêu cầu
            const exportCols = Array.from(colSet);
            if (!exportCols.includes('image')) {
                const sttIdx = exportCols.indexOf('stt');
                if (sttIdx !== -1) {
                    exportCols.splice(sttIdx + 1, 0, 'image');
                } else {
                    exportCols.unshift('image');
                }
            }
            const exportColsParam = exportCols.join(',');

            const exportExcelLinks = document.querySelectorAll('.export-excel-link');
            exportExcelLinks.forEach(link => {
                try {
                    const url = new URL(link.href, window.location.origin);
                    url.searchParams.set('columns', exportColsParam);
                    link.href = url.pathname + url.search;
                } catch (e) {}
            });

            const exportPdfLinks = document.querySelectorAll('.export-pdf-link');
            exportPdfLinks.forEach(link => {
                try {
                    const url = new URL(link.href, window.location.origin);
                    url.searchParams.set('columns', exportColsParam);
                    link.href = url.pathname + url.search;
                } catch (e) {}
            });

            // Lưu cấu hình vào localStorage
            localStorage.setItem(COLUMN_STORAGE_KEY, JSON.stringify(Array.from(colSet)));
        }

        function resetDefaultColumns() {
            localStorage.removeItem(COLUMN_STORAGE_KEY);
            applyColumns(DEFAULT_COLUMNS);
        }

        function toggleColumnMenu(e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('columnToggleMenu');
            if (menu) menu.classList.toggle('hidden');
        }

        // Lắng nghe sự kiện click checkbox cột
        document.querySelectorAll('.col-toggle-checkbox').forEach(cb => {
            cb.addEventListener('change', () => {
                const activeCols = [];
                document.querySelectorAll('.col-toggle-checkbox').forEach(item => {
                    if (item.checked) activeCols.push(item.dataset.targetCol);
                });
                applyColumns(activeCols);
            });
        });

        // Đóng dropdown khi click ra ngoài
        document.addEventListener('click', function (event) {
            // Export dropdown
            const exportDropdown = document.getElementById('exportDropdown');
            const exportBtn = event.target.closest('[onclick="toggleExportDropdown()"]');
            if (exportDropdown && !exportDropdown.contains(event.target) && !exportBtn) {
                exportDropdown.classList.add('hidden');
            }

            // Column menu dropdown
            const columnMenu = document.getElementById('columnToggleMenu');
            const columnBtn = event.target.closest('#columnToggleBtn');
            if (columnMenu && !columnMenu.contains(event.target) && !columnBtn) {
                columnMenu.classList.add('hidden');
            }
        });

        // Khởi chạy áp dụng cột ngay khi tải trang
        document.addEventListener('DOMContentLoaded', () => {
            applyColumns(getStoredColumns());
        });
        applyColumns(getStoredColumns());

        // Bulk selection functionality
        const selectAllCheckbox = document.getElementById('selectAll');
        const itemCheckboxes = document.querySelectorAll('.item-checkbox');
        const bulkActionsBar = document.getElementById('bulkActionsBar');
        const selectedCountSpan = document.getElementById('selectedCount');

        function updateBulkActionsBar() {
            const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
            const count = checkedBoxes.length;
            
            if (selectedCountSpan) {
                selectedCountSpan.textContent = count;
            }
            
            if (bulkActionsBar) {
                if (count > 0) {
                    bulkActionsBar.classList.remove('hidden');
                } else {
                    bulkActionsBar.classList.add('hidden');
                }
            }
            
            // Update select all checkbox state
            if (selectAllCheckbox && itemCheckboxes.length > 0) {
                const enabledCheckboxes = document.querySelectorAll('.item-checkbox:not(:disabled)');
                const checkedEnabled = document.querySelectorAll('.item-checkbox:not(:disabled):checked');
                selectAllCheckbox.checked = enabledCheckboxes.length > 0 && checkedEnabled.length === enabledCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedEnabled.length > 0 && checkedEnabled.length < enabledCheckboxes.length;
            }
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const enabledCheckboxes = document.querySelectorAll('.item-checkbox:not(:disabled)');
                enabledCheckboxes.forEach(cb => cb.checked = this.checked);
                updateBulkActionsBar();
            });
        }

        itemCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkActionsBar);
        });

        function clearSelection() {
            itemCheckboxes.forEach(cb => cb.checked = false);
            if (selectAllCheckbox) selectAllCheckbox.checked = false;
            updateBulkActionsBar();
        }

        function confirmBulkDelete() {
            const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('Vui lòng chọn ít nhất một mục để xóa');
                return;
            }

            const items = [];
            const names = [];
            checkedBoxes.forEach(cb => {
                items.push({
                    id: cb.dataset.id,
                    type: cb.dataset.type
                });
                names.push(cb.dataset.name);
            });

            const confirmMsg = `Bạn có chắc chắn muốn xóa ${items.length} mục sau?\n\n${names.join(', ')}\n\nHành động này không thể hoàn tác!`;
            
            if (confirm(confirmMsg)) {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("inventory.bulk-delete") }}';
                
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);

                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';
                form.appendChild(methodField);

                const itemsField = document.createElement('input');
                itemsField.type = 'hidden';
                itemsField.name = 'items';
                itemsField.value = JSON.stringify(items);
                form.appendChild(itemsField);

                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
@endpush