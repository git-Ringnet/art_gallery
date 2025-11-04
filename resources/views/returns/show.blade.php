@extends('layouts.app')

@section('title', 'Chi tiết phiếu trả hàng')
@section('page-title', 'Chi tiết phiếu trả hàng')
@section('page-description', 'Thông tin chi tiết giao dịch trả hàng')

@section('content')
<x-alert />

<div class="bg-white rounded-xl shadow-lg p-4 glass-effect">
    <!-- Header Actions -->
    <div class="flex justify-between items-center mb-4">
        <a href="{{ route('returns.index') }}?tab=list" class="text-blue-600 hover:text-blue-800 text-sm">
            <i class="fas fa-arrow-left mr-1"></i>Quay lại
        </a>
        <div class="flex space-x-2">
            @if($return->status == 'pending')
            <a href="{{ route('returns.edit', $return->id) }}" class="bg-yellow-600 text-white px-3 py-1.5 text-sm rounded-lg hover:bg-yellow-700 transition-colors">
                <i class="fas fa-edit mr-1"></i>Sửa
            </a>
            <form action="{{ route('returns.approve', $return->id) }}" method="POST" class="inline">
                @csrf
                @method('PUT')
                <button type="submit" class="bg-green-600 text-white px-3 py-1.5 text-sm rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-check mr-1"></i>Duyệt
                </button>
            </form>
            @endif
            @if($return->status == 'approved')
            <form action="{{ route('returns.complete', $return->id) }}" method="POST" class="inline">
                @csrf
                @method('PUT')
                <button type="submit" class="bg-blue-600 text-white px-3 py-1.5 text-sm rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-check-double mr-1"></i>Xong
                </button>
            </form>
            @endif
            @if($return->status != 'cancelled' && $return->status != 'completed')
            <form action="{{ route('returns.cancel', $return->id) }}" method="POST" class="inline" onsubmit="return confirm('Hủy phiếu?')">
                @csrf
                @method('PUT')
                <button type="submit" class="bg-red-600 text-white px-3 py-1.5 text-sm rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-ban mr-1"></i>Hủy
                </button>
            </form>
            @endif
            <button onclick="window.print()" class="bg-gray-600 text-white px-3 py-1.5 text-sm rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-print mr-1"></i>In
            </button>
        </div>
    </div>

    <!-- Payment Status Alert for Exchange -->
    @if($return->type == 'exchange' && $return->exchange_amount > 0)
        @php
            // Kiểm tra xem có thông tin payment trong notes không
            $hasPendingPayment = strpos($return->notes, '[PAYMENT_INFO]') !== false;
            
            // Tính số tiền đã trả cho phiếu đổi hàng này (dựa vào notes chứa return_code)
            $exchangePayments = $return->sale->payments()
                ->where('notes', 'like', "%{$return->return_code}%")
                ->sum('amount');
        @endphp
        
        @if($return->status == 'pending' && $hasPendingPayment)
        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-yellow-600 mr-2"></i>
                <div>
                    <h4 class="font-semibold text-sm text-yellow-800">Thông báo về thanh toán</h4>
                    <p class="text-xs text-yellow-700 mt-1">
                        Khách hàng cần trả thêm <strong>{{ number_format($return->exchange_amount, 0, ',', '.') }}đ</strong> cho đơn đổi hàng này.
                        @if($exchangePayments > 0)
                        <br><strong>Đã trả:</strong> {{ number_format($exchangePayments, 0, ',', '.') }}đ cho sản phẩm mới.
                        <br>Còn lại: <strong>{{ number_format($return->exchange_amount - $exchangePayments, 0, ',', '.') }}đ</strong>
                        @endif
                        <br>Số tiền sẽ được cập nhật vào phiếu bán hàng khi phiếu đổi hàng được <strong>duyệt và hoàn thành</strong>.
                    </p>
                </div>
            </div>
        </div>
        @elseif($return->status == 'approved')
        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-blue-600 mr-2"></i>
                <div>
                    <h4 class="font-semibold text-sm text-blue-800">Phiếu đã được duyệt</h4>
                    <p class="text-xs text-blue-700 mt-1">
                        Khách hàng cần trả thêm <strong>{{ number_format($return->exchange_amount, 0, ',', '.') }}đ</strong> cho đơn đổi hàng này.
                        @if($exchangePayments > 0)
                        <br><strong>Đã trả:</strong> {{ number_format($exchangePayments, 0, ',', '.') }}đ cho sản phẩm mới.
                        <br>Còn lại: <strong>{{ number_format($return->exchange_amount - $exchangePayments, 0, ',', '.') }}đ</strong>
                        @endif
                        <br>Số tiền sẽ được cập nhật vào phiếu bán hàng khi phiếu đổi hàng được <strong>hoàn thành</strong>.
                    </p>
                </div>
            </div>
        </div>
        @elseif($return->status == 'completed' && $exchangePayments > 0)
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-check-double text-green-600 mr-2"></i>
                <div>
                    <h4 class="font-semibold text-sm text-green-800">Đã hoàn thành thanh toán</h4>
                    <p class="text-xs text-green-700 mt-1">
                        Số tiền <strong>{{ number_format($exchangePayments, 0, ',', '.') }}đ</strong> đã được cập nhật vào phiếu bán hàng.
                    </p>
                </div>
            </div>
        </div>
        @endif
    @endif

    <!-- Return Info -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <!-- Left Column -->
        <div class="space-y-3">
            <div class="bg-gray-50 p-3 rounded-lg">
                <h3 class="font-semibold text-base mb-2">Thông tin phiếu</h3>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 text-xs">Mã phiếu:</span>
                        <span class="font-medium text-xs">{{ $return->return_code }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 text-xs">Loại:</span>
                        <span>
                            @if($return->type == 'exchange')
                                <span class="px-1.5 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Đổi hàng</span>
                            @else
                                <span class="px-1.5 py-0.5 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">Trả hàng</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 text-xs">Mã HD gốc:</span>
                        <a href="{{ route('sales.show', $return->sale_id) }}" class="font-medium text-xs text-blue-600 hover:underline">
                            {{ $return->sale->invoice_code ?? 'N/A' }}
                        </a>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 text-xs">Ngày:</span>
                        <span class="font-medium text-xs">{{ $return->return_date->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 text-xs">Trạng thái:</span>
                        <span>
                            @if($return->status == 'completed')
                                <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-green-100 text-green-800">Đã xong</span>
                            @elseif($return->status == 'approved')
                                <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-blue-100 text-blue-800">Đã duyệt</span>
                            @elseif($return->status == 'pending')
                                <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-yellow-100 text-yellow-800">Chờ duyệt</span>
                            @else
                                <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-red-100 text-red-800">Hủy</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 text-xs">Người xử lý:</span>
                        <span class="font-medium text-xs truncate max-w-[150px]">{{ $return->processedBy->name ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-3">
            <div class="bg-gray-50 p-3 rounded-lg">
                <h3 class="font-semibold text-base mb-2">Thông tin khách hàng</h3>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 text-xs">Tên Khách hàng:</span>
                        <span class="font-medium text-xs truncate max-w-[150px]">{{ $return->customer->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 text-xs">SĐT:</span>
                        <span class="font-medium text-xs">{{ $return->customer->phone ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 text-xs">Email:</span>
                        <span class="font-medium text-xs truncate max-w-[150px]">{{ $return->customer->email ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 text-xs">Địa chỉ:</span>
                        <span class="font-medium text-xs text-right truncate max-w-[150px]">{{ $return->customer->address ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Return Items -->
    <div class="mb-4">
        <h3 class="font-semibold text-base mb-2">
            @if($return->type == 'exchange')
                Sản phẩm đổi/trả
            @else
                Sản phẩm trả lại
            @endif
        </h3>
        
        @if($return->type == 'exchange')
        <!-- Exchange View with Arrows -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Returned Items -->
            <div class="border rounded-lg p-3 bg-red-50">
                <h4 class="font-medium mb-2 text-sm text-red-800">
                    <i class="fas fa-arrow-left mr-1"></i>Sản phẩm trả lại
                </h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-white border-b">
                            <tr>
                                <th class="px-1 py-1.5 text-left text-xs">Hình ảnh</th>
                                <th class="px-1 py-1.5 text-left text-xs">Sản phẩm</th>
                                <th class="px-1 py-1.5 text-left text-xs">Vật tư</th>
                                <th class="px-1 py-1.5 text-center text-xs">Mét</th>
                                <th class="px-1 py-1.5 text-center text-xs">Số lượng</th>
                                <th class="px-1 py-1.5 text-right text-xs">Giá</th>
                                <th class="px-1 py-1.5 text-center text-xs">Giảm giá</th>
                                <th class="px-1 py-1.5 text-right text-xs">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            @foreach($return->items as $item)
                            <tr class="border-b">
                                <td class="px-1 py-1.5">
                                    @if($item->item_type === 'painting' && $item->painting && $item->painting->image)
                                        <img src="{{ asset('storage/' . $item->painting->image) }}" alt="{{ $item->painting->name }}" 
                                            class="w-10 h-10 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity"
                                            onclick="showImageModal('{{ asset('storage/' . $item->painting->image) }}', '{{ $item->painting->name }}')">
                                    @else
                                        <div class="w-10 h-10 bg-gray-200 rounded flex items-center justify-center">
                                            <i class="fas fa-image text-gray-400 text-xs"></i>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-1 py-1.5">
                                    <p class="font-medium text-xs truncate max-w-[100px]">
                                        @if($item->item_type === 'painting')
                                            {{ $item->painting->name ?? 'N/A' }}
                                        @else
                                            {{ $item->supply->name ?? 'N/A' }}
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-600">
                                        @if($item->item_type === 'painting')
                                            <span class="px-1 py-0.5 rounded-full bg-purple-100 text-purple-800">Tranh</span>
                                        @else
                                            <span class="px-1 py-0.5 rounded-full bg-blue-100 text-blue-800">Vật tư</span>
                                        @endif
                                    </p>
                                </td>
                                <td class="px-1 py-1.5 text-xs truncate max-w-[80px]">
                                    @if($item->supply_id)
                                        {{ $item->frameSupply->name ?? 'N/A' }}
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-1 py-1.5 text-center text-xs">
                                    @if($item->supply_length)
                                        {{ $item->supply_length }}
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-1 py-1.5 text-center text-xs font-medium">{{ $item->quantity }}</td>
                                <td class="px-1 py-1.5 text-right text-xs whitespace-nowrap">{{ number_format($item->unit_price, 0, ',', '.') }}đ</td>
                                <td class="px-1 py-1.5 text-center text-xs">
                                    @php
                                        $saleItem = $item->saleItem;
                                        $discount = $saleItem ? $saleItem->discount_percent : 0;
                                    @endphp
                                    @if($discount > 0)
                                        <span class="text-red-600">{{ $discount }}%</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-1 py-1.5 text-right text-xs font-semibold text-red-600 whitespace-nowrap">{{ number_format($item->subtotal, 0, ',', '.') }}đ</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-2 pt-2 border-t">
                    <div class="flex justify-between text-xs font-semibold">
                        <span>Tổng trả:</span>
                        <span class="text-red-600">{{ number_format($return->total_refund, 0, ',', '.') }}đ</span>
                    </div>
                    @if($return->type == 'exchange')
                    @php
                        // Lấy tổng số tiền đã trả ban đầu cho hóa đơn gốc (trước khi đổi hàng)
                        $sale = $return->sale;
                        $initialPayments = $sale->payments()
                            ->where('transaction_type', '!=', 'exchange_payment')
                            ->where('created_at', '<', $return->created_at)
                            ->sum('amount');
                    @endphp
                    @if($initialPayments > 0)
                    <div class="flex justify-between text-xs mt-1">
                        <span class="text-gray-600">Đã trả (ban đầu):</span>
                        <span class="font-semibold text-green-600">{{ number_format($initialPayments, 0, ',', '.') }}đ</span>
                    </div>
                    @endif
                    @endif
                </div>
            </div>
            
            <!-- Exchange Arrow -->
            <div class="hidden lg:flex items-center justify-center absolute left-1/2 transform -translate-x-1/2 z-10">
                <div class="bg-blue-600 text-white rounded-full p-4 shadow-lg">
                    <i class="fas fa-exchange-alt text-sm"></i>
                </div>
            </div>
            
            <!-- Exchange Items -->
            <div class="border rounded-lg p-3 bg-green-50">
                <h4 class="font-medium mb-2 text-sm text-green-800">
                    <i class="fas fa-arrow-right mr-1"></i>Sản phẩm đổi mới
                </h4>
                @if($return->exchangeItems->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-white border-b">
                            <tr>
                                <th class="px-1 py-1.5 text-left text-xs">Hình ảnh</th>
                                <th class="px-1 py-1.5 text-left text-xs">Sản phẩm</th>
                                <th class="px-1 py-1.5 text-left text-xs">Vật tư</th>
                                <th class="px-1 py-1.5 text-center text-xs">Mét</th>
                                <th class="px-1 py-1.5 text-center text-xs">Số lượng</th>
                                <th class="px-1 py-1.5 text-right text-xs">Đơn giá</th>
                                <th class="px-1 py-1.5 text-center text-xs">Giảm giá</th>
                                <th class="px-1 py-1.5 text-right text-xs">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            @foreach($return->exchangeItems as $item)
                            <tr class="border-b">
                                <td class="px-1 py-1.5">
                                    @if($item->item_type === 'painting' && $item->painting && $item->painting->image)
                                        <img src="{{ asset('storage/' . $item->painting->image) }}" alt="{{ $item->painting->name }}" 
                                            class="w-10 h-10 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity"
                                            onclick="showImageModal('{{ asset('storage/' . $item->painting->image) }}', '{{ $item->painting->name }}')">
                                    @else
                                        <div class="w-10 h-10 bg-gray-200 rounded flex items-center justify-center">
                                            <i class="fas fa-image text-gray-400 text-xs"></i>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-1 py-1.5">
                                    <p class="font-medium text-xs truncate max-w-[100px]">
                                        @if($item->item_type === 'painting')
                                            {{ $item->painting->name ?? 'N/A' }}
                                        @else
                                            {{ $item->supply->name ?? 'N/A' }}
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-600">
                                        @if($item->item_type === 'painting')
                                            <span class="px-1 py-0.5 rounded-full bg-purple-100 text-purple-800">Tranh</span>
                                        @else
                                            <span class="px-1 py-0.5 rounded-full bg-blue-100 text-blue-800">Vật tư</span>
                                        @endif
                                    </p>
                                </td>
                                <td class="px-1 py-1.5 text-xs truncate max-w-[80px]">
                                    @if($item->supply_id)
                                        {{ $item->frameSupply->name ?? 'N/A' }}
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-1 py-1.5 text-center text-xs">
                                    @if($item->supply_length)
                                        {{ $item->supply_length }}
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-1 py-1.5 text-center text-xs font-medium">{{ $item->quantity }}</td>
                                <td class="px-1 py-1.5 text-right text-xs whitespace-nowrap">{{ number_format($item->unit_price, 0, ',', '.') }}đ</td>
                                <td class="px-1 py-1.5 text-center text-xs">
                                    @if($item->discount_percent > 0)
                                        <span class="text-red-600">{{ $item->discount_percent }}%</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-1 py-1.5 text-right text-xs font-semibold text-green-600 whitespace-nowrap">{{ number_format($item->subtotal, 0, ',', '.') }}đ</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-2 pt-2 border-t">
                    <div class="flex justify-between text-xs font-semibold">
                        <span>Tổng đổi:</span>
                        <span class="text-green-600">{{ number_format($return->exchangeItems->sum('subtotal'), 0, ',', '.') }}đ</span>
                    </div>
                    @php
                        // Tính số tiền đã trả cho phiếu đổi hàng này
                        $exchangePayments = $return->sale->payments()
                            ->where('transaction_type', 'exchange_payment')
                            ->where('notes', 'like', "%{$return->return_code}%")
                            ->sum('amount');
                    @endphp
                    @if($exchangePayments > 0)
                    <div class="flex justify-between text-xs mt-1">
                        <span class="text-gray-600">Đã trả:</span>
                        <span class="font-semibold text-green-600">{{ number_format($exchangePayments, 0, ',', '.') }}đ</span>
                    </div>
                    @endif
                </div>
                @else
                <div class="text-center text-gray-500 py-3">
                    <p class="text-xs">Chưa có SP đổi</p>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Exchange Summary -->
        @if($return->exchange_amount != 0)
        <div class="mt-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
            <div class="flex justify-between items-center">
                <span class="font-semibold text-sm">Chênh lệch:</span>
                <span class="font-bold text-base">
                    @if($return->exchange_amount > 0)
                        <span class="text-red-600">+{{ number_format($return->exchange_amount, 0, ',', '.') }}đ (KH trả thêm)</span>
                    @else
                        <span class="text-green-600">{{ number_format($return->exchange_amount, 0, ',', '.') }}đ (Hoàn lại)</span>
                    @endif
                </span>
            </div>
            
            @if($return->exchange_amount > 0)
            @php
                // Tính số tiền đã trả cho phiếu đổi hàng này
                $exchangePayments = $return->sale->payments()
                    ->where('transaction_type', 'exchange_payment')
                    ->where('notes', 'like', "%{$return->return_code}%")
                    ->sum('amount');
                $remainingDebt = $return->exchange_amount - $exchangePayments;
            @endphp
            
            @if($exchangePayments > 0)
            <div class="mt-2 pt-2 border-t border-blue-300">
                <div class="flex justify-between text-xs">
                    <span class="text-gray-600">Đã trả:</span>
                    <span class="font-semibold text-green-600">{{ number_format($exchangePayments, 0, ',', '.') }}đ</span>
                </div>
                <div class="flex justify-between text-xs mt-1">
                    <span class="text-gray-600">Còn nợ:</span>
                    <span class="font-semibold text-red-600">{{ number_format($remainingDebt, 0, ',', '.') }}đ</span>
                </div>
            </div>
            @endif
            @endif
        </div>
        @endif
        
        @else
        <!-- Regular Return View -->
        <div class="overflow-x-auto">
            <table class="w-full table-auto text-xs">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500">STT</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500">Hình ảnh</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500">Sản phẩm</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500">Loại</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500">Số lượng</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500">Giá</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500">Tiền</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500">Lý do</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($return->items as $index => $item)
                    <tr>
                        <td class="px-2 py-2 text-xs">{{ $index + 1 }}</td>
                        <td class="px-2 py-2">
                            @if($item->item_type === 'painting' && $item->painting && $item->painting->image)
                                <img src="{{ asset('storage/' . $item->painting->image) }}" alt="{{ $item->painting->name }}" 
                                    class="w-12 h-12 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity"
                                    onclick="showImageModal('{{ asset('storage/' . $item->painting->image) }}', '{{ $item->painting->name }}')">
                            @else
                                <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400 text-xs"></i>
                                </div>
                            @endif
                        </td>
                        <td class="px-2 py-2 text-xs font-medium truncate max-w-[150px]">
                            @if($item->item_type === 'painting')
                                {{ $item->painting->name ?? 'N/A' }}
                            @else
                                {{ $item->supply->name ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-2 py-2 text-xs">
                            @if($item->item_type === 'painting')
                                <span class="px-1.5 py-0.5 text-xs rounded-full bg-purple-100 text-purple-800">Tranh</span>
                            @else
                                <span class="px-1.5 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800">Vật tư</span>
                            @endif
                        </td>
                        <td class="px-2 py-2 text-xs text-right">{{ $item->quantity }}</td>
                        <td class="px-2 py-2 text-xs text-right whitespace-nowrap">{{ number_format($item->unit_price, 0, ',', '.') }}đ</td>
                        <td class="px-2 py-2 text-xs text-right font-medium whitespace-nowrap">{{ number_format($item->subtotal, 0, ',', '.') }}đ</td>
                        <td class="px-2 py-2 text-xs text-gray-600 truncate max-w-[100px]">{{ $item->reason ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="3" class="px-2 py-2 text-right text-xs font-semibold">Tổng:</td>
                        <td class="px-2 py-2 text-right text-xs font-semibold">{{ $return->items->sum('quantity') }}</td>
                        <td colspan="3" class="px-2 py-2 text-right text-sm font-semibold text-red-600 whitespace-nowrap">
                            {{ number_format($return->total_refund, 0, ',', '.') }}đ
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>

    <!-- Reason and Notes -->
    @if($return->reason || $return->notes)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @if($return->reason)
        <div class="bg-yellow-50 p-3 rounded-lg border border-yellow-200">
            <h4 class="font-semibold text-sm mb-1">Lý do:</h4>
            <p class="text-gray-700 text-xs">{{ $return->reason }}</p>
        </div>
        @endif
        
        @if($return->notes)
        <div class="bg-blue-50 p-3 rounded-lg border border-blue-200">
            <h4 class="font-semibold text-sm mb-1">Ghi chú:</h4>
            @php
                // Parse payment info nếu có
                $notes = $return->notes;
                $displayNotes = $notes;
                
                if (strpos($notes, '[PAYMENT_INFO]') !== false) {
                    // Tách phần JSON ra
                    $parts = explode('[PAYMENT_INFO]', $notes);
                    $jsonPart = $parts[1] ?? '';
                    
                    if ($jsonPart) {
                        $paymentInfo = json_decode($jsonPart, true);
                        if ($paymentInfo) {
                            $displayNotes = 'Khách hàng đã trả ' . number_format($paymentInfo['payment_amount'], 0, ',', '.') . 'đ';
                            $displayNotes .= ' bằng ' . ($paymentInfo['payment_method'] == 'cash' ? 'tiền mặt' : 'chuyển khoản');
                            $displayNotes .= ' vào ngày ' . date('d/m/Y', strtotime($paymentInfo['payment_date']));
                        }
                    }
                }
            @endphp
            <p class="text-gray-700 text-xs">{{ $displayNotes }}</p>
        </div>
        @endif
    </div>
    @endif
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
