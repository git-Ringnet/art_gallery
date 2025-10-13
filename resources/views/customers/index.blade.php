@extends('layouts.app')

@section('title', 'Quản lý Khách hàng')
@section('page-title', 'Quản lý Khách hàng')
@section('page-description', 'Danh sách khách hàng và thông tin liên hệ')

@section('header-actions')
<a href="{{ route('customers.create') }}" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors flex items-center space-x-2">
    <i class="fas fa-plus"></i>
    <span>Thêm khách hàng</span>
</a>
@endsection

@section('content')
<x-alert />

<div class="bg-white rounded-xl shadow-lg p-6 fade-in">
    <!-- Search & Filter -->
    <form method="GET" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-search mr-2"></i>Tìm kiếm
                </label>
                <input type="text" name="search" value="{{ request('search') }}" 
                    placeholder="Tên, SĐT, Email..." 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition-colors">
                    <i class="fas fa-search mr-2"></i>Tìm kiếm
                </button>
                <a href="{{ route('customers.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                    <i class="fas fa-redo mr-2"></i>Làm mới
                </a>
            </div>
        </div>
    </form>

    <!-- Customers Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white">
                <tr>
                    <th class="px-4 py-3 text-left">Tên khách hàng</th>
                    <th class="px-4 py-3 text-left">Số điện thoại</th>
                    <th class="px-4 py-3 text-left">Email</th>
                    <th class="px-4 py-3 text-left">Địa chỉ</th>
                    <th class="px-4 py-3 text-right">Tổng mua</th>
                    <th class="px-4 py-3 text-right">Công nợ</th>
                    <th class="px-4 py-3 text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($customers as $customer)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ $customer->name }}</div>
                    </td>
                    <td class="px-4 py-3">
                       {{ $customer->phone }}
                    </td>
                    <td class="px-4 py-3">
                        @if($customer->email)
                            {{ $customer->email }}
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        {{ Str::limit($customer->address, 30) ?: '-' }}
                    </td>
                    <td class="px-4 py-3 text-right font-medium text-green-600">
                        {{ number_format($customer->total_purchased, 0, ',', '.') }}đ
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($customer->total_debt > 0)
                            <span class="text-red-600 font-medium">{{ number_format($customer->total_debt, 0, ',', '.') }}đ</span>
                        @else
                            <span class="text-gray-400">0đ</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center space-x-2">
                            <!-- Xem chi tiết -->
                            <a href="{{ route('customers.show', $customer->id) }}" 
                                class="w-8 h-8 flex items-center justify-center bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-colors" 
                                title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            
                            <!-- Sửa -->
                            <a href="{{ route('customers.edit', $customer->id) }}" 
                                class="w-8 h-8 flex items-center justify-center bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-colors" 
                                title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            
                            <!-- Icon trạng thái công nợ hoặc nút xóa -->
                            @if($customer->total_debt > 0)
                                <!-- Có công nợ - hiển thị icon cảnh báo -->
                                <span class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-600 rounded-lg" 
                                    title="Có công nợ: {{ number_format($customer->total_debt, 0, ',', '.') }}đ">
                                    <i class="fas fa-exclamation-circle"></i>
                                </span>
                            @elseif($customer->total_purchased > 0)
                                <!-- Đã có giao dịch và thanh toán đầy đủ - hiển thị icon check -->
                                <span class="w-8 h-8 flex items-center justify-center bg-green-100 text-green-600 rounded-lg" 
                                    title="Đã thanh toán đầy đủ - Tổng mua: {{ number_format($customer->total_purchased, 0, ',', '.') }}đ">
                                    <i class="fas fa-check-circle"></i>
                                </span>
                            @else
                                <!-- Chưa có giao dịch - cho phép xóa -->
                                <button type="button"
                                    onclick="showDeleteModal('{{ route('customers.destroy', $customer->id) }}', 'Bạn có chắc chắn muốn xóa khách hàng {{ $customer->name }}?')"
                                    class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors" 
                                    title="Xóa khách hàng">
                                    <i class="fas fa-trash"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-users text-4xl mb-2"></i>
                        <p>Chưa có khách hàng nào</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $customers->links() }}
    </div>
</div>

<!-- Delete Modal Component -->
<x-delete-modal />
@endsection
