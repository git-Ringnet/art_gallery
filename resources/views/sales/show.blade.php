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
            <h3 class="font-semibold text-lg mb-4 flex items-center justify-between">
                <span class="flex items-center">
                    <i class="fas fa-shopping-cart text-green-600 mr-2"></i>
                    Sản phẩm
                    @if($sale->returns->where('type', 'exchange')->where('status', 'completed')->count() > 0)
                        <span class="ml-2 text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">
                            Hiện tại (Đã đổi hàng)
                        </span>
                    @endif
                </span>
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">SL</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Đơn giá</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Giảm giá</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @php $displayIndex = 0; @endphp
                        @foreach($sale->saleItems as $item)
                            @if($item->quantity > 0)
                                @php $displayIndex++; @endphp
                                <tr>
                                    <td class="px-4 py-3 text-sm">{{ $displayIndex }}</td>
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
                                    <td class="px-4 py-3 text-right text-sm">
                                        @if($item->discount_percent > 0)
                                            <span class="text-red-600">{{ number_format($item->discount_percent, 0) }}%</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm font-semibold">
                                        <div>${{ number_format($item->total_usd, 2) }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format($item->total_vnd) }}đ</div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Returns/Exchanges -->
        @if($sale->returns->where('status', 'completed')->count() > 0)
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="font-semibold text-lg mb-4 flex items-center">
                <i class="fas fa-exchange-alt text-orange-600 mr-2"></i>
                Lịch sử đổi/trả hàng
            </h3>
            <div class="space-y-3">
                @foreach($sale->returns->where('status', 'completed') as $return)
                <div class="p-3 bg-gray-50 rounded border-l-4 @if($return->type == 'exchange') border-blue-500 @else border-red-500 @endif">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex-1">
                            <p class="font-medium">
                                @if($return->type == 'exchange')
                                    <span class="text-blue-600"><i class="fas fa-exchange-alt mr-1"></i>Đổi hàng</span>
                                @else
                                    <span class="text-red-600"><i class="fas fa-undo mr-1"></i>Trả hàng</span>
                                @endif
                            </p>
                            <p class="text-sm text-gray-600">{{ $return->return_date->format('d/m/Y') }}</p>
                            
                            @if($return->type == 'exchange')
                                <!-- Show exchange details: Old → New -->
                                <div class="mt-2 text-sm">
                                    @foreach($return->items as $returnItem)
                                        @php
                                            $oldItemName = $returnItem->saleItem->description ?? 'N/A';
                                        @endphp
                                        @foreach($return->exchangeItems as $exchangeItem)
                                            @php
                                                if ($exchangeItem->item_type === 'painting') {
                                                    $newItemName = $exchangeItem->painting->name ?? 'N/A';
                                                } else {
                                                    $newItemName = $exchangeItem->supply->name ?? 'N/A';
                                                }
                                            @endphp
                                            <div class="flex items-center gap-2 text-gray-700">
                                                <span class="line-through text-gray-500">{{ $oldItemName }}</span>
                                                <i class="fas fa-arrow-right text-blue-500"></i>
                                                <span class="font-medium text-blue-700">{{ $newItemName }}</span>
                                            </div>
                                        @endforeach
                                    @endforeach
                                </div>
                            @else
                                <!-- Show returned items -->
                                <div class="mt-2 text-sm text-gray-600">
                                    @foreach($return->items as $returnItem)
                                        <div>• {{ $returnItem->saleItem->description ?? 'N/A' }} (SL: {{ $returnItem->quantity }})</div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <a href="{{ route('returns.show', $return->id) }}" class="text-blue-600 hover:underline text-sm whitespace-nowrap ml-2">
                            {{ $return->return_code }}
                        </a>
                    </div>
                    @if($return->reason)
                    <p class="text-sm text-gray-500 mb-2 italic">Lý do: {{ $return->reason }}</p>
                    @endif
                    @if($return->type == 'exchange')
                        <p class="text-sm text-gray-700 mt-2 pt-2 border-t">
                            Chênh lệch: 
                            @if($return->exchange_amount > 0)
                                <span class="text-green-600 font-medium">+{{ number_format($return->exchange_amount) }}đ (Khách trả thêm)</span>
                            @elseif($return->exchange_amount < 0)
                                <span class="text-red-600 font-medium">{{ number_format(abs($return->exchange_amount)) }}đ (Hoàn lại)</span>
                            @else
                                <span class="text-gray-600">Không có chênh lệch</span>
                            @endif
                        </p>
                    @else
                        <p class="text-sm text-red-600 font-medium mt-2 pt-2 border-t">Hoàn tiền: {{ number_format($return->total_refund) }}đ</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

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
                @php
                    $hasExchange = $sale->returns->where('type', 'exchange')->where('status', 'completed')->count() > 0;
                @endphp
                @if($sale->payment_status == 'cancelled')
                    <span class="inline-block px-4 py-2 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">
                        <i class="fas fa-ban mr-1"></i>Đã hủy (Trả hàng)
                    </span>
                @elseif($sale->payment_status == 'paid')
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
                
                @if($hasExchange)
                    <div class="mt-2">
                        <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                            <i class="fas fa-exchange-alt mr-1"></i>Có đổi hàng
                        </span>
                    </div>
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
