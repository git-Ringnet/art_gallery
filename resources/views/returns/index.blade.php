@extends('layouts.app')

@section('title', 'Đổi/Trả hàng')
@section('page-title', 'Đổi/Trả hàng')
@section('page-description', 'Quản lý các giao dịch đổi/trả hàng')

@section('header-actions')
<a href="{{ route('returns.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Tạo phiếu đổi/trả
        </a>
@endsection

@section('content')
<x-alert />

<div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
    <!-- Header -->
    <!-- <div class="flex justify-between items-center mb-6">
        <a href="{{ route('returns.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Tạo phiếu đổi/trả
        </a>
    </div> -->
    
    <!-- Returns List -->
    <!-- Search and Filter -->
    <div class="bg-gray-50 p-4 rounded-lg mb-6">
        <form method="GET" action="{{ route('returns.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tìm kiếm</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}" class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Tìm theo mã phiếu, mã HD, tên khách hàng...">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Loại</label>
                    <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">Tất cả</option>
                        <option value="return" {{ request('type') == 'return' ? 'selected' : '' }}>Trả hàng</option>
                        <option value="exchange" {{ request('type') == 'exchange' ? 'selected' : '' }}>Đổi hàng</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">Tất cả</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Từ ngày</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Đến ngày</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
            </div>
            <div class="flex justify-between items-center mt-4">
                <button type="submit" class="bg-blue-600 text-white py-2 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-filter mr-2"></i>Lọc
                </button>
                <a href="{{ route('returns.index') }}" class="bg-gray-500 text-white py-2 px-6 rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-2"></i>Xóa lọc
                </a>
            </div>
        </form>
    </div>
    
    <!-- Returns Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Mã phiếu</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Loại</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Mã HD gốc</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Ngày</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Khách hàng</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Sản phẩm</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Số lượng</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Tiền hoàn/chênh lệch</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Trạng thái</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($returns as $return)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">
                        {{ $return->return_code }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm">
                        @if($return->type == 'exchange')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                <i class="fas fa-exchange-alt mr-1"></i>Đổi hàng
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                                <i class="fas fa-undo mr-1"></i>Trả hàng
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-blue-600">
                        <a href="{{ route('sales.show', $return->sale_id) }}" class="hover:underline">
                            {{ $return->sale->invoice_code ?? 'N/A' }}
                        </a>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $return->return_date->format('d/m/Y') }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $return->customer->name ?? 'N/A' }}</div>
                            <div class="text-sm text-gray-500">{{ $return->customer->phone ?? '' }}</div>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-900">
                        @php
                            $itemNames = $return->items->map(function($item) {
                                if ($item->item_type === 'painting') {
                                    return $item->painting->name ?? 'N/A';
                                } else {
                                    return $item->supply->name ?? 'N/A';
                                }
                            })->take(2)->implode(', ');
                            $moreCount = $return->items->count() - 2;
                        @endphp
                        {{ $itemNames }}
                        @if($moreCount > 0)
                            <span class="text-gray-500">+{{ $moreCount }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                        {{ $return->items->sum('quantity') }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                        @if($return->type == 'exchange')
                            @if($return->exchange_amount > 0)
                                <span class="font-semibold text-red-600">+{{ number_format($return->exchange_amount, 0, ',', '.') }}đ</span>
                            @elseif($return->exchange_amount < 0)
                                <span class="font-semibold text-green-600">{{ number_format($return->exchange_amount, 0, ',', '.') }}đ</span>
                            @else
                                <span class="text-gray-600">0đ</span>
                            @endif
                        @else
                            <span class="font-semibold text-red-600">{{ number_format($return->total_refund, 0, ',', '.') }}đ</span>
                        @endif
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">
                        @if($return->status == 'completed')
                            <span class="px-3 py-2 text-sm font-bold rounded-lg bg-green-100 text-green-800">Hoàn thành</span>
                        @elseif($return->status == 'approved')
                            <span class="px-3 py-2 text-sm font-bold rounded-lg bg-green-100 text-green-800">Đã duyệt</span>
                        @elseif($return->status == 'pending')
                            <span class="px-3 py-2 text-sm font-bold rounded-lg bg-yellow-100 text-yellow-800">Chờ duyệt</span>
                        @else
                            <span class="px-3 py-2 text-sm font-bold rounded-lg bg-red-100 text-red-800">Đã hủy</span>
                        @endif
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                        <div class="flex items-center justify-center gap-1">
                            <!-- View -->
                            <a href="{{ route('returns.show', $return->id) }}" 
                               class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors" 
                               title="Xem chi tiết">
                                <i class="fas fa-eye text-sm"></i>
                            </a>
                            
                            <!-- Edit -->
                            @if($return->status == 'pending')
                            <a href="{{ route('returns.edit', $return->id) }}" 
                               class="w-8 h-8 flex items-center justify-center rounded-lg bg-yellow-100 text-yellow-600 hover:bg-yellow-200 transition-colors" 
                               title="Chỉnh sửa">
                                <i class="fas fa-edit text-sm"></i>
                            </a>
                            @endif
                            
                            <!-- Approve -->
                            @if($return->status == 'pending')
                            <form action="{{ route('returns.approve', $return->id) }}" method="POST" class="inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" 
                                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-green-100 text-green-600 hover:bg-green-200 transition-colors" 
                                        title="Duyệt phiếu">
                                    <i class="fas fa-check text-sm"></i>
                                </button>
                            </form>
                            @endif
                            
                            <!-- Complete -->
                            @if($return->status == 'approved')
                            <form action="{{ route('returns.complete', $return->id) }}" method="POST" class="inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" 
                                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors" 
                                        title="Hoàn thành">
                                    <i class="fas fa-check-double text-sm"></i>
                                </button>
                            </form>
                            @endif
                            
                            <!-- Print -->
                            <a href="{{ route('returns.show', $return->id) }}" 
                               class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" 
                               title="In phiếu">
                                <i class="fas fa-print text-sm"></i>
                            </a>
                            
                            <!-- Cancel -->
                            @if($return->status != 'cancelled' && $return->status != 'completed')
                            <form action="{{ route('returns.cancel', $return->id) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn hủy phiếu này?')">
                                @csrf
                                @method('PUT')
                                <button type="submit" 
                                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-100 text-red-600 hover:bg-red-200 transition-colors" 
                                        title="Hủy phiếu">
                                    <i class="fas fa-ban text-sm"></i>
                                </button>
                            </form>
                            @endif
                            
                            <!-- Delete -->
                            @if($return->status == 'cancelled')
                            <form action="{{ route('returns.destroy', $return->id) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa phiếu này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" 
                                        title="Xóa phiếu">
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Chưa có dữ liệu đổi/trả hàng</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    @if($returns->hasPages())
    <div class="mt-6">
        {{ $returns->links() }}
    </div>
    @endif
</div>
@endsection
