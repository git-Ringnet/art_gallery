@extends('layouts.app')

@section('title', 'Chi tiết hóa đơn')
@section('page-title', 'Chi tiết hóa đơn ' . $sale->invoice_code)
@section('page-description', 'Xem chi tiết hóa đơn bán hàng')

@section('header-actions')
<div class="flex gap-2">
    <a href="{{ route('sales.print', $sale->id) }}" target="_blank" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
        <i class="fas fa-print mr-2"></i>In
    </a>
    <a href="{{ route('sales.edit', $sale->id) }}" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700">
        <i class="fas fa-edit mr-2"></i>Sửa
    </a>
    <a href="{{ route('sales.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
        <i class="fas fa-arrow-left mr-2"></i>Quay lại
    </a>
</div>
@endsection

@section('content')
<x-alert />

<div class="grid grid-cols-3 gap-6">
    <!-- Left Column -->
    <div class="col-span-2 space-y-6">
        <!-- Customer Info -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="font-semibold text-lg mb-4 flex items-center">
                <i class="fas fa-user text-blue-600 mr-2"></i>
                Thông tin khách hàng
            </h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Tên khách hàng</p>
                    <p class="font-medium">{{ $sale->customer->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Số điện thoại</p>
                    <p class="font-medium">{{ $sale->customer->phone }}</p>
                </div>
                @if($sale->customer->email)
                <div>
                    <p class="text-sm text-gray-600">Email</p>
                    <p class="font-medium">{{ $sale->customer->email }}</p>
                </div>
                @endif
                @if($sale->customer->address)
                <div>
                    <p class="text-sm text-gray-600">Địa chỉ</p>
                    <p class="font-medium">{{ $sale->customer->address }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Sale Items -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="font-semibold text-lg mb-4 flex items-center">
                <i class="fas fa-shopping-cart text-green-600 mr-2"></i>
                Sản phẩm
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">SL</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Đơn giá</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($sale->saleItems as $index => $item)
                        <tr>
                            <td class="px-4 py-3 text-sm">{{ $index + 1 }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $item->description }}</div>
                                @if($item->painting)
                                    <div class="text-xs text-gray-500">Tranh: {{ $item->painting->code }}</div>
                                @endif
                                @if($item->supply)
                                    <div class="text-xs text-gray-500">Vật tư: {{ $item->supply->name }} ({{ $item->supply_length }}m)</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-sm">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                @if($item->currency == 'USD')
                                    <div>${{ number_format($item->price_usd, 2) }}</div>
                                    <div class="text-xs text-gray-500">{{ number_format($item->price_vnd) }}đ</div>
                                @else
                                    <div>{{ number_format($item->price_vnd) }}đ</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-semibold">
                                <div>${{ number_format($item->total_usd, 2) }}</div>
                                <div class="text-xs text-gray-500">{{ number_format($item->total_vnd) }}đ</div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payments -->
        @if($sale->payments->count() > 0)
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="font-semibold text-lg mb-4 flex items-center">
                <i class="fas fa-money-bill text-green-600 mr-2"></i>
                Lịch sử thanh toán
            </h3>
            <div class="space-y-3">
                @foreach($sale->payments as $payment)
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <div>
                        <p class="font-medium">{{ number_format($payment->amount) }}đ</p>
                        <p class="text-sm text-gray-600">
                            {{ $payment->payment_date->format('d/m/Y') }} - 
                            @if($payment->payment_method == 'cash') Tiền mặt
                            @elseif($payment->payment_method == 'bank_transfer') Chuyển khoản
                            @elseif($payment->payment_method == 'card') Thẻ
                            @else Khác
                            @endif
                        </p>
                    </div>
                    @if($payment->notes)
                    <p class="text-sm text-gray-500">{{ $payment->notes }}</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Right Column -->
    <div class="space-y-6">
        <!-- Sale Info -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="font-semibold text-lg mb-4">Thông tin hóa đơn</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-600">Mã hóa đơn</p>
                    <p class="font-bold text-blue-600">{{ $sale->invoice_code }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Ngày bán</p>
                    <p class="font-medium">{{ $sale->sale_date->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Showroom</p>
                    <p class="font-medium">{{ $sale->showroom->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Nhân viên</p>
                    <p class="font-medium">{{ $sale->user ? $sale->user->name : 'Chưa xác định' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Tỷ giá</p>
                    <p class="font-medium">1 USD = {{ number_format($sale->exchange_rate) }} VND</p>
                </div>
            </div>
        </div>

        <!-- Totals -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="font-semibold text-lg mb-4">Tổng kết</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Tạm tính:</span>
                    <div class="text-right">
                        <div class="font-medium">${{ number_format($sale->subtotal_usd, 2) }}</div>
                        <div class="text-sm text-gray-500">{{ number_format($sale->subtotal_vnd) }}đ</div>
                    </div>
                </div>
                @if($sale->discount_percent > 0)
                <div class="flex justify-between text-red-600">
                    <span>Giảm giá ({{ $sale->discount_percent }}%):</span>
                    <div class="text-right">
                        <div class="font-medium">-${{ number_format($sale->discount_usd, 2) }}</div>
                        <div class="text-sm">-{{ number_format($sale->discount_vnd) }}đ</div>
                    </div>
                </div>
                @endif
                <div class="border-t pt-3 flex justify-between">
                    <span class="font-bold text-lg">Tổng cộng:</span>
                    <div class="text-right">
                        <div class="font-bold text-lg text-green-600">${{ number_format($sale->total_usd, 2) }}</div>
                        <div class="text-sm text-gray-500">{{ number_format($sale->total_vnd) }}đ</div>
                    </div>
                </div>
                <div class="flex justify-between text-blue-600">
                    <span>Đã thanh toán:</span>
                    <span class="font-bold">{{ number_format($sale->paid_amount) }}đ</span>
                </div>
                @if($sale->debt_amount > 0)
                <div class="flex justify-between text-red-600 bg-red-50 p-3 rounded">
                    <span class="font-bold">Còn nợ:</span>
                    <span class="font-bold text-lg">{{ number_format($sale->debt_amount) }}đ</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Status -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="font-semibold text-lg mb-4">Trạng thái</h3>
            <div class="text-center">
                @if($sale->payment_status == 'paid')
                    <span class="inline-block px-4 py-2 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>Đã thanh toán
                    </span>
                @elseif($sale->payment_status == 'partial')
                    <span class="inline-block px-4 py-2 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">
                        <i class="fas fa-clock mr-1"></i>Thanh toán một phần
                    </span>
                @else
                    <span class="inline-block px-4 py-2 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                        <i class="fas fa-times-circle mr-1"></i>Chưa thanh toán
                    </span>
                @endif
            </div>
        </div>

        @if($sale->notes)
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="font-semibold text-lg mb-4">Ghi chú</h3>
            <p class="text-gray-700">{{ $sale->notes }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
