@extends('layouts.app')

@section('title', 'Chi tiết hóa đơn')
@section('page-title', 'Chi tiết hóa đơn ' . $sale->invoice_code)
@section('page-description', 'Xem chi tiết hóa đơn bán hàng')

@section('header-actions')
<div class="flex flex-wrap gap-2">
    @if($sale->canApprove())
    <form method="POST" action="{{ route('sales.approve', $sale->id) }}" class="inline">
        @csrf
        <button type="submit" onclick="return confirm('Xác nhận duyệt phiếu bán hàng này?')" class="bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700 text-sm whitespace-nowrap">
            <i class="fas fa-check-circle mr-1"></i>Duyệt
        </button>
    </form>
    @endif
    @if($sale->isPending() && $sale->paid_amount == 0)
    <form method="POST" action="{{ route('sales.cancel', $sale->id) }}" class="inline">
        @csrf
        <button type="submit" onclick="return confirm('Xác nhận hủy phiếu bán hàng này?')" class="bg-red-600 text-white px-3 py-1.5 rounded-lg hover:bg-red-700 text-sm whitespace-nowrap">
            <i class="fas fa-ban mr-1"></i>Hủy
        </button>
    </form>
    @endif
    <a href="{{ route('sales.print', $sale->id) }}" target="_blank" class="bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 text-sm whitespace-nowrap">
        <i class="fas fa-print mr-1"></i>In
    </a>
    @if($sale->canEdit())
    <a href="{{ route('sales.edit', $sale->id) }}" class="bg-yellow-600 text-white px-3 py-1.5 rounded-lg hover:bg-yellow-700 text-sm whitespace-nowrap">
        <i class="fas fa-edit mr-1"></i>Sửa
    </a>
    @endif
    <a href="{{ route('sales.index') }}" class="bg-gray-600 text-white px-3 py-1.5 rounded-lg hover:bg-gray-700 text-sm whitespace-nowrap">
        <i class="fas fa-arrow-left mr-1"></i>Quay lại
    </a>
</div>
@endsection

@section('content')
<x-alert />

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Left Column -->
    <div class="lg:col-span-2 space-y-4">
        <!-- Customer Info -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="font-semibold text-base mb-3 flex items-center">
                <i class="fas fa-user text-blue-600 mr-2"></i>
                Thông tin khách hàng
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <p class="text-xs text-gray-600">Tên khách hàng</p>
                    <p class="font-medium text-sm">{{ $sale->customer->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-600">Số điện thoại</p>
                    <p class="font-medium text-sm">{{ $sale->customer->phone }}</p>
                </div>
                @if($sale->customer->email)
                <div>
                    <p class="text-xs text-gray-600">Email</p>
                    <p class="font-medium text-sm">{{ $sale->customer->email }}</p>
                </div>
                @endif
                @if($sale->customer->address)
                <div>
                    <p class="text-xs text-gray-600">Địa chỉ</p>
                    <p class="font-medium text-sm">{{ $sale->customer->address }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Sale Items -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="font-semibold text-base mb-3 flex items-center justify-between">
                <span class="flex items-center">
                    <i class="fas fa-shopping-cart text-green-600 mr-2"></i>
                    Sản phẩm
                    @if($sale->returns->where('type', 'exchange')->where('status', 'completed')->count() > 0)
                        <span class="ml-2 text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">
                            Đã đổi hàng
                        </span>
                    @endif
                </span>
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">Hình</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                            <th class="px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase">SL</th>
                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase">Đơn giá</th>
                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase">Giảm</th>
                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @php $displayIndex = 0; @endphp
                        @foreach($sale->saleItems as $item)
                            @if($item->quantity > 0)
                                @php 
                                    $displayIndex++; 
                                    $isReturned = $item->is_returned ?? false;
                                    $rowClass = $isReturned ? 'bg-red-50 opacity-60' : '';
                                    $textClass = $isReturned ? 'line-through text-gray-400' : '';
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td class="px-2 py-2 text-xs {{ $textClass }}">{{ $displayIndex }}</td>
                                    <td class="px-2 py-2">
                                        @if($item->painting && $item->painting->image)
                                            <img src="{{ asset('storage/' . $item->painting->image) }}" alt="{{ $item->painting->name }}" 
                                                class="w-12 h-12 object-cover rounded {{ $isReturned ? 'opacity-40' : 'cursor-pointer hover:opacity-80' }} transition-opacity"
                                                @if(!$isReturned) onclick="showImageModal('{{ asset('storage/' . $item->painting->image) }}', '{{ $item->painting->name }}')" @endif>
                                        @else
                                            <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center {{ $isReturned ? 'opacity-40' : '' }}">
                                                <i class="fas fa-image text-gray-400 text-xs"></i>
                                            </div>
                                        @endif
                                        @if($isReturned)
                                            <div class="text-xs text-red-600 font-semibold mt-0.5">
                                                <i class="fas fa-undo"></i>Trả
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2">
                                        <div class="font-medium text-xs {{ $textClass }}">{{ $item->description }}</div>
                                        @if($item->painting)
                                            <div class="text-xs text-gray-500 {{ $textClass }}">{{ $item->painting->code }}</div>
                                        @endif
                                        @if($item->supply)
                                            <div class="text-xs text-gray-500 {{ $textClass }}">{{ $item->supply->name }} ({{ $item->supply_length }}m)</div>
                                        @endif
                                        @if($isReturned && $item->returned_quantity > 0)
                                            <div class="text-xs text-red-600">Trả: {{ $item->returned_quantity }}/{{ $item->quantity }}</div>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-center text-xs {{ $textClass }}">{{ $item->quantity }}</td>
                                    <td class="px-2 py-2 text-right text-xs {{ $textClass }} whitespace-nowrap">
                                        @if($item->currency == 'USD')
                                            <div>${{ number_format($item->price_usd, 2) }}</div>
                                            <div class="text-xs text-gray-500">{{ number_format($item->price_vnd) }}đ</div>
                                        @else
                                            <div>{{ number_format($item->price_vnd) }}đ</div>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-right text-xs {{ $textClass }}">
                                        @if($item->discount_percent > 0)
                                            <span class="text-red-600">{{ number_format($item->discount_percent, 0) }}%</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-right text-xs font-semibold {{ $textClass }} whitespace-nowrap">
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
                <i class="fas fa-history text-orange-600 mr-2"></i>
                Các lần đổi hàng / trả hàng
            </h3>
            <p class="text-sm text-gray-500 mb-3 italic">Danh sách các lần khách hàng đã đổi hoặc trả sản phẩm</p>
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
                                            // Giá của sản phẩm cũ (từ return_items)
                                            $oldItemPrice = $returnItem->subtotal ?? 0;
                                        @endphp
                                        @foreach($return->exchangeItems as $exchangeItem)
                                            @php
                                                if ($exchangeItem->item_type === 'painting') {
                                                    $newItemName = $exchangeItem->painting->name ?? 'N/A';
                                                } else {
                                                    $newItemName = $exchangeItem->supply->name ?? 'N/A';
                                                }
                                                // Giá của sản phẩm mới (từ exchange_items)
                                                $newItemPrice = $exchangeItem->subtotal ?? 0;
                                            @endphp
                                            <div class="text-gray-700 space-y-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="line-through text-gray-500">{{ $oldItemName }}</span>
                                                    <span class="text-gray-500 text-xs">({{ number_format($oldItemPrice) }}đ)</span>
                                                    <i class="fas fa-arrow-right text-blue-500"></i>
                                                    <span class="font-medium text-blue-700">{{ $newItemName }}</span>
                                                    <span class="text-blue-600 text-xs font-medium">({{ number_format($newItemPrice) }}đ)</span>
                                                </div>
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
                <i class="fas fa-receipt text-green-600 mr-2"></i>
                Các lần khách đã trả tiền
            </h3>
            <p class="text-sm text-gray-500 mb-3 italic">Danh sách các lần khách hàng đã thanh toán cho hóa đơn này</p>
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
    <div class="space-y-4">
        <!-- Sale Info -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="font-semibold text-base mb-3">Thông tin hóa đơn</h3>
            <div class="space-y-2">
                <div>
                    <p class="text-xs text-gray-600">Mã hóa đơn</p>
                    <p class="font-bold text-blue-600 text-sm">{{ $sale->invoice_code }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-600">Ngày bán</p>
                    <p class="font-medium text-sm">{{ $sale->sale_date->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-600">Showroom</p>
                    <p class="font-medium text-sm">{{ $sale->showroom->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-600">Nhân viên</p>
                    <p class="font-medium text-sm">{{ $sale->user ? $sale->user->name : 'Chưa xác định' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-600">Tỷ giá</p>
                    <p class="font-medium text-sm">1 USD = {{ number_format($sale->exchange_rate) }} VND</p>
                </div>
            </div>
        </div>

        <!-- Totals -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="font-semibold text-base mb-3">Tổng kết</h3>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Tạm tính:</span>
                    <div class="text-right">
                        <div class="font-medium text-xs">${{ number_format($sale->subtotal_usd, 2) }}</div>
                        <div class="text-xs text-gray-500">{{ number_format($sale->subtotal_vnd) }}đ</div>
                    </div>
                </div>
                @if($sale->discount_percent > 0)
                <div class="flex justify-between text-red-600 text-sm">
                    <span>Giảm ({{ $sale->discount_percent }}%):</span>
                    <div class="text-right">
                        <div class="font-medium text-xs">-${{ number_format($sale->discount_usd, 2) }}</div>
                        <div class="text-xs">-{{ number_format($sale->discount_vnd) }}đ</div>
                    </div>
                </div>
                @endif
                <div class="border-t pt-2 flex justify-between">
                    <span class="font-bold text-base">Tổng cộng:</span>
                    <div class="text-right">
                        @php
                            // Kiểm tra xem có return hoặc exchange không
                            $hasReturns = $sale->returns()->where('status', 'completed')->where('type', 'return')->exists();
                            $hasExchanges = $sale->returns()->where('status', 'completed')->where('type', 'exchange')->exists();
                            
                            // Lấy original_total
                            if ($sale->original_total_vnd) {
                                $originalTotal = $sale->original_total_vnd;
                                $originalTotalUsd = $sale->original_total_usd;
                            } else {
                                // Tính từ items (cho dữ liệu cũ)
                                $originalTotal = $sale->saleItems->sum('total_vnd');
                                $originalTotalUsd = $originalTotal / $sale->exchange_rate;
                            }
                            
                            // Kiểm tra xem có thay đổi tổng tiền không
                            $totalChanged = ($hasReturns || $hasExchanges) && $originalTotal != $sale->total_vnd;
                        @endphp
                        
                        @if($hasReturns && $sale->total_vnd == 0)
                            <!-- Trả hết - hiển thị giá gốc không gạch ngang -->
                            <div class="font-bold text-base text-gray-900">${{ number_format($originalTotalUsd, 2) }}</div>
                            <div class="text-xs text-gray-500">{{ number_format($originalTotal) }}đ</div>
                            <div class="text-xs text-red-600 mt-0.5">
                                <i class="fas fa-undo"></i>Trả hết
                            </div>
                        @elseif($totalChanged)
                            <!-- Có thay đổi (trả hàng hoặc đổi hàng) - hiển thị tổng cũ bị gạch -->
                            <div class="text-xs text-gray-400 line-through mb-0.5">
                                ${{ number_format($originalTotalUsd, 2) }} / {{ number_format($originalTotal) }}đ
                            </div>
                            <!-- Hiển thị tổng mới -->
                            <div class="font-bold text-base {{ $hasExchanges ? 'text-purple-600' : 'text-green-600' }}">
                                ${{ number_format($sale->total_usd, 2) }}
                            </div>
                            <div class="text-xs {{ $hasExchanges ? 'text-purple-600' : 'text-green-600' }}">
                                {{ number_format($sale->total_vnd) }}đ
                            </div>
                            <div class="text-xs {{ $hasExchanges ? 'text-purple-600' : 'text-orange-600' }} mt-0.5">
                                @if($hasExchanges)
                                    <i class="fas fa-exchange-alt"></i>Đổi hàng
                                @else
                                    <i class="fas fa-info-circle"></i>Trừ hàng trả
                                @endif
                            </div>
                        @else
                            <!-- Không có thay đổi -->
                            <div class="font-bold text-base text-green-600">${{ number_format($sale->total_usd, 2) }}</div>
                            <div class="text-xs text-gray-500">{{ number_format($sale->total_vnd) }}đ</div>
                        @endif
                    </div>
                </div>
                <div class="flex justify-between bg-blue-50 p-2 rounded text-sm">
                    <span class="text-blue-700 font-medium">Đã trả:</span>
                    <span class="font-bold text-blue-700">{{ number_format($sale->paid_amount) }}đ</span>
                </div>
                @if($sale->sale_status == 'cancelled')
                <div class="flex justify-between text-gray-600 bg-gray-50 p-2 rounded border border-gray-200 text-sm">
                    <span class="font-bold">
                        <i class="fas fa-ban"></i>Đã hủy
                    </span>
                    <span class="font-bold">Không nợ</span>
                </div>
                @elseif($sale->debt_amount > 0)
                <div class="flex justify-between text-red-600 bg-red-50 p-2 rounded border border-red-200 text-sm">
                    <span class="font-bold">Còn thiếu:</span>
                    <span class="font-bold text-base">{{ number_format($sale->debt_amount) }}đ</span>
                </div>
                @else
                <div class="flex justify-between text-green-600 bg-green-50 p-2 rounded border border-green-200 text-sm">
                    <span class="font-bold">
                        <i class="fas fa-check-circle"></i>Đã TT đủ
                    </span>
                </div>
                @endif
            </div>
        </div>

        <!-- Status -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="font-semibold text-lg mb-4">Trạng thái</h3>
            <div class="space-y-3">
                <!-- Sale Status -->
                <div>
                    <p class="text-sm text-gray-600 mb-2 font-medium">Tình trạng phiếu:</p>
                    <div class="text-center">
                        @if($sale->sale_status == 'pending')
                            <span class="inline-block px-4 py-2 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                <i class="fas fa-clock mr-1"></i>Chờ duyệt
                            </span>
                        @elseif($sale->sale_status == 'completed')
                            <span class="inline-block px-4 py-2 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Đã hoàn thành
                            </span>
                        @elseif($sale->sale_status == 'cancelled')
                            <span class="inline-block px-4 py-2 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">
                                <i class="fas fa-ban mr-1"></i>Đã hủy
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Payment Status -->
                <div class="pt-3 border-t">
                    <p class="text-sm text-gray-600 mb-2 font-medium">Thanh toán:</p>
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

<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center p-4" onclick="closeImageModal()">
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
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeImageModal();
    }
});
</script>
@endpush

@endsection
