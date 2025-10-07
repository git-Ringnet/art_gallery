@extends('layouts.app')

@section('title', 'Quản lý kho')
@section('page-title', 'Quản lý kho')
@section('page-description', 'Quản lý tồn kho tranh và vật tư')

@section('header-actions')
<a href="{{ route('inventory.import') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
    <i class="fas fa-plus mr-2"></i>Nhập kho
</a>
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
                        <input type="text" name="search" value="{{ request('search') }}" class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Tìm theo mã, tên sản phẩm...">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Loại</label>
                    <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Tất cả</option>
                        <option value="painting" {{ request('type') == 'painting' ? 'selected' : '' }}>Tranh</option>
                        <option value="supply" {{ request('type') == 'supply' ? 'selected' : '' }}>Vật tư</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Từ ngày</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>
            <div class="flex justify-between items-center mt-4">
                <button type="submit" class="bg-blue-600 text-white py-2 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-filter mr-2"></i>Lọc
                </button>
                <a href="{{ route('inventory.index') }}" class="bg-gray-500 text-white py-2 px-6 rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-2"></i>Xóa lọc
                </a>
            </div>
        </form>
    </div>
    
    <!-- Inventory Table -->
    <div class="overflow-x-auto">
        <table class="w-full table-auto">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên sản phẩm</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số lượng</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày nhập</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($inventory as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">{{ $item['code'] }}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item['name'] }}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                        @if($item['type'] == 'painting')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">Tranh</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Vật tư</span>
                        @endif
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                        {{ $item['quantity'] }}{{ isset($item['unit']) ? ' ' . $item['unit'] : '' }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item['import_date'] }}</td>
                    <td class="px-4 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Còn hàng</span>
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
</div>
@endsection
