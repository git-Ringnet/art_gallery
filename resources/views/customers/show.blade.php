@extends('layouts.app')

@section('title', 'Chi tiết Khách hàng')
@section('page-title', 'Thông tin Khách hàng')
@section('page-description', 'Chi tiết và lịch sử giao dịch')

@section('header-actions')
<a href="{{ route('customers.edit', $customer->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 text-sm rounded-lg transition-colors">
    <i class="fas fa-edit mr-1"></i>Sửa
</a>
 <a href="{{ route('customers.index') }}"
            class="bg-gray-500 text-white px-3 py-1.5 text-sm rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-1"></i>Quay lại
        </a>
@endsection

@section('content')
<x-alert />

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Customer Info -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-lg p-4 fade-in">
            <h3 class="text-base font-bold text-gray-800 mb-3 flex items-center">
                <i class="fas fa-user-circle text-blue-500 mr-1"></i>
                Thông tin khách hàng
            </h3>
            
            <div class="space-y-2">
                <div>
                    <label class="text-xs text-gray-500">Tên khách hàng</label>
                    <p class="font-medium text-sm text-gray-900">{{ $customer->name }}</p>
                </div>
                
                <div>
                    <label class="text-xs text-gray-500">Số điện thoại</label>
                    <p class="font-medium text-sm text-gray-900">
                        <i class="fas fa-phone text-blue-500 mr-1"></i>{{ $customer->phone }}
                    </p>
                </div>
                
                @if($customer->email)
                <div>
                    <label class="text-xs text-gray-500">Email</label>
                    <p class="font-medium text-sm text-gray-900">
                        <i class="fas fa-envelope text-gray-500 mr-1"></i>{{ $customer->email }}
                    </p>
                </div>
                @endif
                
                @if($customer->address)
                <div>
                    <label class="text-xs text-gray-500">Địa chỉ</label>
                    <p class="font-medium text-sm text-gray-900">{{ $customer->address }}</p>
                </div>
                @endif
                
                @if($customer->notes)
                <div>
                    <label class="text-xs text-gray-500">Ghi chú</label>
                    <p class="text-sm text-gray-700">{{ $customer->notes }}</p>
                </div>
                @endif
            </div>

            <!-- Stats -->
            @php
                // Tính tổng từ sales không bị hủy (lọc cancelled)
                $activeSales = $customer->sales->where('payment_status', '!=', 'cancelled');
                $totalPurchased = $activeSales->sum('total_vnd');
                $totalDebt = $activeSales->sum('debt_amount');
            @endphp
            <div class="mt-4 pt-4 border-t grid grid-cols-2 gap-3">
                <div class="text-center">
                    <p class="text-xs text-gray-500">Tổng mua</p>
                    <p class="text-base font-bold text-green-600">{{ number_format($totalPurchased, 0, ',', '.') }}đ</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-500">Công nợ</p>
                    <p class="text-base font-bold {{ $totalDebt > 0 ? 'text-red-600' : 'text-gray-400' }}">
                        {{ number_format($totalDebt, 0, ',', '.') }}đ
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-lg p-4 fade-in">
            <h3 class="text-base font-bold text-gray-800 mb-3 flex items-center">
                <i class="fas fa-history text-blue-500 mr-1"></i>
                Lịch sử giao dịch
            </h3>

            @if($customer->sales->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-700">Mã HĐ</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-700">Ngày bán</th>
                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-700">Tổng tiền</th>
                            <th class="px-2 py-2 text-center text-xs font-medium text-gray-700">Trạng thái</th>
                            <th class="px-2 py-2 text-center text-xs font-medium text-gray-700">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($customer->sales as $sale)
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 py-2 font-medium text-xs text-blue-600">{{ $sale->invoice_code }}</td>
                            <td class="px-2 py-2 text-xs">{{ $sale->sale_date->format('d/m/Y') }}</td>
                            <td class="px-2 py-2 text-right text-xs font-medium">{{ number_format($sale->total_vnd, 0, ',', '.') }}đ</td>
                            <td class="px-2 py-2 text-center">
                                @if($sale->payment_status === 'cancelled')
                                    <span class="px-1.5 py-0.5 bg-gray-100 text-gray-800 text-xs rounded">Đã hủy</span>
                                @elseif($sale->payment_status === 'paid')
                                    <span class="px-1.5 py-0.5 bg-green-100 text-green-800 text-xs rounded">Đã thanh toán</span>
                                @elseif($sale->payment_status === 'partial')
                                    <span class="px-1.5 py-0.5 bg-yellow-100 text-yellow-800 text-xs rounded">Thanh toán 1 phần</span>
                                @else
                                    <span class="px-1.5 py-0.5 bg-red-100 text-red-800 text-xs rounded">Chưa Thanh toán</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-center">
                                <a href="{{ route('sales.show', $sale->id) }}" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-6 text-gray-500">
                <i class="fas fa-receipt text-3xl mb-2"></i>
                <p class="text-sm">Chưa có giao dịch nào</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
