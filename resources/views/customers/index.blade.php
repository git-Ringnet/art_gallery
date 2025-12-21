@extends('layouts.app')

@section('title', 'Quản lý Khách hàng')
@section('page-title', 'Quản lý Khách hàng')
@section('page-description', 'Danh sách khách hàng và thông tin liên hệ')

@section('header-actions')
@notArchive
@hasPermission('customers', 'can_create')
<a href="{{ route('customers.create') }}" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 text-sm rounded-lg transition-colors flex items-center space-x-1">
    <i class="fas fa-plus"></i>
    <span>Thêm khách hàng</span>
</a>
@endhasPermission
@endnotArchive
@endsection

@section('content')
<x-alert />

<div class="bg-white rounded-xl shadow-lg p-4 fade-in">
    <!-- Search & Filter -->
    <form method="GET" class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    <i class="fas fa-search mr-1"></i>Tìm kiếm
                </label>
                <input type="text" name="search" value="{{ request('search') }}" 
                    placeholder="Tên, SĐT, Email..." 
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-1.5 text-sm rounded-lg transition-colors">
                    <i class="fas fa-search mr-1"></i>Tìm kiếm
                </button>
                <a href="{{ route('customers.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-1.5 text-sm rounded-lg transition-colors">
                    <i class="fas fa-redo mr-1"></i>Làm mới
                </a>
            </div>
        </div>
    </form>

    <!-- Customers Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white">
                <tr>
                    <th class="px-2 py-2 text-center text-xs">STT</th>
                    <th class="px-2 py-2 text-left text-xs">Tên khách hàng</th>
                    <th class="px-2 py-2 text-left text-xs">Số điện thoại</th>
                    <th class="px-2 py-2 text-left text-xs">Email</th>
                    <th class="px-2 py-2 text-left text-xs">Địa chỉ</th>
                    <th class="px-2 py-2 text-right text-xs">Tổng mua</th>
                    <th class="px-2 py-2 text-right text-xs">Công nợ</th>
                    <th class="px-2 py-2 text-center text-xs">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($customers as $index => $customer)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-2 py-2 text-center text-xs text-gray-600">
                        {{ ($customers->currentPage() - 1) * $customers->perPage() + $index + 1 }}
                    </td>
                    <td class="px-2 py-2">
                        <div class="font-medium text-xs text-gray-900">{{ $customer->name }}</div>
                    </td>
                    <td class="px-2 py-2 text-xs">
                       {{ $customer->phone }}
                    </td>
                    <td class="px-2 py-2 text-xs">
                        @if($customer->email)
                            {{ $customer->email }}
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-2 py-2 text-xs text-gray-600 truncate max-w-[150px]">
                        {{ Str::limit($customer->address, 30) ?: '-' }}
                    </td>
                    <td class="px-2 py-2 text-right text-xs font-medium">
                        @if($customer->total_purchased_usd > 0)
                            <div class="text-green-600">${{ number_format($customer->total_purchased_usd, 2) }}</div>
                        @endif
                        @if($customer->total_purchased_vnd > 0)
                            <div class="text-green-600">{{ number_format($customer->total_purchased_vnd, 0, ',', '.') }}đ</div>
                        @endif
                        @if($customer->total_purchased_usd == 0 && $customer->total_purchased_vnd == 0)
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-2 py-2 text-right text-xs font-bold">
                        @if($customer->total_debt_usd > 0 || $customer->total_debt_vnd > 0)
                            @if($customer->total_debt_usd > 0)
                                <div class="text-red-600">${{ number_format($customer->total_debt_usd, 2) }}</div>
                            @endif
                            @if($customer->total_debt_vnd > 0)
                                <div class="text-red-600">{{ number_format($customer->total_debt_vnd, 0, ',', '.') }}đ</div>
                            @endif
                        @else
                            <span class="text-gray-400 font-normal">0đ</span>
                        @endif
                    </td>
                    <td class="px-2 py-2">
                        <div class="flex items-center justify-center space-x-1">
                            <!-- Xem chi tiết -->
                            @hasPermission('customers', 'can_view')
                            <a href="{{ route('customers.show', $customer->id) }}" 
                                class="w-7 h-7 flex items-center justify-center bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-colors" 
                                title="Xem chi tiết">
                                <i class="fas fa-eye text-xs"></i>
                            </a>
                            @endhasPermission
                            
                            <!-- Sửa -->
                            @hasPermission('customers', 'can_edit')
                            <a href="{{ route('customers.edit', $customer->id) }}" 
                                class="w-7 h-7 flex items-center justify-center bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 transition-colors" 
                                title="Sửa">
                                <i class="fas fa-edit text-xs"></i>
                            </a>
                            @endhasPermission
                            
                            <!-- Icon trạng thái công nợ hoặc nút xóa -->
                            @if($customer->total_debt > 0)
                                <!-- Có công nợ - hiển thị icon cảnh báo -->
                                <span class="w-7 h-7 flex items-center justify-center bg-red-100 text-red-600 rounded" 
                                    title="Có công nợ: {{ number_format($customer->total_debt, 0, ',', '.') }}đ">
                                    <i class="fas fa-exclamation-circle text-xs"></i>
                                </span>
                            @elseif($customer->total_purchased > 0)
                                <!-- Đã có giao dịch và thanh toán đầy đủ - hiển thị icon check -->
                                <span class="w-7 h-7 flex items-center justify-center bg-green-100 text-green-600 rounded" 
                                    title="Đã thanh toán đầy đủ - Tổng mua: {{ number_format($customer->total_purchased, 0, ',', '.') }}đ">
                                    <i class="fas fa-check-circle text-xs"></i>
                                </span>
                            @else
                                <!-- Chưa có giao dịch - cho phép xóa -->
                                @hasPermission('customers', 'can_delete')
                                <button type="button"
                                    onclick="showDeleteModal('{{ route('customers.destroy', $customer->id) }}', 'Bạn có chắc chắn muốn xóa khách hàng {{ $customer->name }}?')"
                                    class="w-7 h-7 flex items-center justify-center bg-red-100 text-red-600 rounded hover:bg-red-200 transition-colors" 
                                    title="Xóa khách hàng">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                                @endhasPermission
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-2 py-6 text-center text-gray-500">
                        <i class="fas fa-users text-3xl mb-2"></i>
                        <p class="text-sm">Chưa có khách hàng nào</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $customers->links() }}
    </div>
</div>

<!-- Delete Modal Component -->
<x-delete-modal />
@endsection
