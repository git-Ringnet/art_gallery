@extends('layouts.app')

@section('title', 'Chi tiết hóa đơn')
@section('page-title', 'Chi tiết hóa đơn ' . $sale->invoice_code)
@section('page-description', 'Xem chi tiết hóa đơn bán hàng')

@section('header-actions')
@php
    $canEditSale = \App\Helpers\PermissionHelper::canEditModel($sale, 'sales');
    $overpaidVnd = (float) ($sale->overpaid_vnd ?? 0);
    $overpaidUsd = (float) ($sale->overpaid_usd ?? 0);
    $hasOverpayment = ($sale->total_vnd > 0 && $overpaidVnd > 1000) || ($sale->total_usd > 0 && $overpaidUsd > 0.01) || ($sale->total_vnd <= 0 && $sale->total_usd <= 0 && (($sale->paid_vnd ?? 0) > 0 || ($sale->paid_usd ?? 0) > 0));
@endphp
<div class="flex flex-wrap gap-2">
    @if($hasOverpayment && $canEditSale)
    <button type="button" onclick="openRefundModal()" class="bg-purple-600 text-white px-3 py-1.5 rounded-lg hover:bg-purple-700 text-sm whitespace-nowrap shadow-md transition-all font-medium">
        <i class="fas fa-hand-holding-usd mr-1"></i>Hoàn tiền thừa
    </button>
    @endif
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
    @if($sale->canEdit() && ($sale->isPending() || $sale->payment_status !== 'paid'))
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
                            <th class="px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase">Kích thước(cm)</th>
                            <th class="px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase">SL</th>
                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase">Đơn giá</th>
                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase">Giảm(%)</th>
                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase">Giảm tiền</th>
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
                                                @if(!$isReturned) 
                                                    data-image-src="{{ asset('storage/' . $item->painting->image) }}"
                                                    data-image-title="{{ $item->painting->name }}"
                                                    onclick="showImageModalFromElement(this)" 
                                                @endif>
                                        @elseif($item->frame)
                                            <div class="w-12 h-12 bg-blue-100 rounded flex items-center justify-center {{ $isReturned ? 'opacity-40' : '' }}">
                                                <i class="fas fa-border-style text-blue-600 text-lg"></i>
                                            </div>
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
                                        @if($item->frame)
                                            <div class="text-xs text-blue-600 {{ $textClass }}">
                                                <i class="fas fa-border-style"></i> Khung: {{ $item->frame->name }}
                                            </div>
                                        @endif
                                        @if($isReturned && $item->returned_quantity > 0)
                                            <div class="text-xs text-red-600">Trả: {{ $item->returned_quantity }}/{{ $item->quantity }}</div>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-center text-xs {{ $textClass }} whitespace-nowrap">
                                        @if($item->painting && $item->painting->width && $item->painting->height)
                                            {{ (float)$item->painting->width }}x{{ (float)$item->painting->height }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-center text-xs {{ $textClass }}">{{ $item->quantity }}</td>
                                    <td class="px-2 py-2 text-right text-xs {{ $textClass }} whitespace-nowrap">
                                        @if($item->currency == 'USD')
                                            <div>${{ number_format($item->price_usd, (abs($item->price_usd - round($item->price_usd)) < 0.01 ? 0 : 2)) }}</div>
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
                                    <td class="px-2 py-2 text-right text-xs {{ $textClass }}">
                                        @if($item->discount_amount_usd > 0)
                                            <div class="text-red-600">-${{ number_format($item->discount_amount_usd, (abs($item->discount_amount_usd - round($item->discount_amount_usd)) < 0.01 ? 0 : 2)) }}</div>
                                        @endif
                                        @if($item->discount_amount_vnd > 0)
                                            <div class="text-red-600">-{{ number_format($item->discount_amount_vnd) }}đ</div>
                                        @endif
                                        @if($item->discount_amount_usd == 0 && $item->discount_amount_vnd == 0)
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-right text-xs font-semibold {{ $textClass }} whitespace-nowrap">
                                        @if($item->currency == 'USD')
                                            <div>${{ number_format($item->total_usd, (abs($item->total_usd - round($item->total_usd)) < 0.01 ? 0 : 2)) }}</div>
                                        @else
                                            <div>{{ number_format($item->total_vnd) }}đ</div>
                                        @endif
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
                                            // Giá của sản phẩm cũ (từ return_items) - Hiển thị đúng currency
                                            $oldCurrency = $returnItem->saleItem->currency ?? 'VND';
                                            $oldItemPriceUsd = $returnItem->subtotal_usd ?? 0;
                                            $oldItemPriceVnd = $returnItem->subtotal ?? 0;
                                        @endphp
                                        @foreach($return->exchangeItems as $exchangeItem)
                                            @php
                                                if ($exchangeItem->item_type === 'painting') {
                                                    $newItemName = $exchangeItem->painting->name ?? 'N/A';
                                                } else {
                                                    $newItemName = $exchangeItem->supply->name ?? 'N/A';
                                                }
                                                // Giá của sản phẩm mới (từ exchange_items) - Hiển thị đúng currency
                                                $newCurrency = $exchangeItem->currency ?? 'VND';
                                                $newItemPriceUsd = $exchangeItem->subtotal_usd ?? 0;
                                                $newItemPriceVnd = $exchangeItem->subtotal ?? 0;
                                            @endphp
                                            <div class="text-gray-700 space-y-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="line-through text-gray-500">{{ $oldItemName }}</span>
                                                    <span class="text-gray-500 text-xs">
                                                        @if($oldCurrency === 'USD')
                                                            (${{ number_format($oldItemPriceUsd, 0) }})
                                                        @else
                                                            ({{ number_format($oldItemPriceVnd) }}đ)
                                                        @endif
                                                    </span>
                                                    <i class="fas fa-arrow-right text-blue-500"></i>
                                                    <span class="font-medium text-blue-700">{{ $newItemName }}</span>
                                                    <span class="text-blue-600 text-xs font-medium">
                                                        @if($newCurrency === 'USD')
                                                            (${{ number_format($newItemPriceUsd, 0) }})
                                                        @else
                                                            ({{ number_format($newItemPriceVnd) }}đ)
                                                        @endif
                                                    </span>
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
                            @php
                                $exchangeAmountUsd = $return->exchange_amount_usd ?? 0;
                                $exchangeAmountVnd = $return->exchange_amount ?? 0;
                                $hasExchangeAmount = $exchangeAmountUsd != 0 || $exchangeAmountVnd != 0;
                            @endphp
                            @if($hasExchangeAmount)
                                @if($exchangeAmountUsd > 0 || $exchangeAmountVnd > 0)
                                    <span class="text-green-600 font-medium">
                                        @if($exchangeAmountUsd > 0 && $exchangeAmountVnd > 0)
                                            +${{ number_format($exchangeAmountUsd, 0) }} + {{ number_format($exchangeAmountVnd) }}đ
                                        @elseif($exchangeAmountUsd > 0)
                                            +${{ number_format($exchangeAmountUsd, 0) }}
                                        @else
                                            +{{ number_format($exchangeAmountVnd) }}đ
                                        @endif
                                        (Khách trả thêm)
                                    </span>
                                @else
                                    <span class="text-red-600 font-medium">
                                        @if($exchangeAmountUsd < 0 && $exchangeAmountVnd < 0)
                                            ${{ number_format(abs($exchangeAmountUsd), 2) }} + {{ number_format(abs($exchangeAmountVnd)) }}đ
                                        @elseif($exchangeAmountUsd < 0)
                                            ${{ number_format(abs($exchangeAmountUsd), 2) }}
                                        @else
                                            {{ number_format(abs($exchangeAmountVnd)) }}đ
                                        @endif
                                        (Hoàn lại)
                                    </span>
                                @endif
                            @else
                                <span class="text-gray-600">Không có chênh lệch</span>
                            @endif
                        </p>
                    @else
                        <p class="text-sm text-red-600 font-medium mt-2 pt-2 border-t">
                            Hoàn tiền: 
                            @php
                                $refundUsd = $return->total_refund_usd ?? 0;
                                $refundVnd = $return->total_refund ?? 0;
                            @endphp
                            @if($refundUsd > 0 && $refundVnd > 0)
                                ${{ number_format($refundUsd, 0) }} + {{ number_format($refundVnd) }}đ
                            @elseif($refundUsd > 0)
                                ${{ number_format($refundUsd, 0) }}
                            @else
                                {{ number_format($refundVnd) }}đ
                            @endif
                        </p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Payments -->
        @php
            $hasPaymentRecords = $sale->payments->count() > 0;
            $hasInitialPayment = ($sale->payment_usd ?? 0) > 0 || ($sale->payment_vnd ?? 0) > 0;
            $showPaymentSection = $hasPaymentRecords || $hasInitialPayment;
        @endphp
        
        @if($showPaymentSection)
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="font-semibold text-lg flex items-center">
                        <i class="fas fa-receipt text-green-600 mr-2"></i>
                        Các lần khách đã trả tiền
                    </h3>
                    <p class="text-sm text-gray-500 italic">Danh sách các lần khách hàng đã thanh toán cho hóa đơn này</p>
                </div>
                @if($hasOverpayment && $canEditSale)
                <button type="button" onclick="openRefundModal()" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-md transition-all whitespace-nowrap flex items-center">
                    <i class="fas fa-hand-holding-usd mr-1.5"></i>Hoàn tiền thừa
                </button>
                @endif
            </div>

            @if($hasOverpayment)
            <div class="p-3 bg-purple-50 border border-purple-200 rounded-lg mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                <div class="flex items-start sm:items-center text-purple-900 text-xs">
                    <i class="fas fa-exclamation-triangle text-purple-600 mr-2 mt-0.5 sm:mt-0 text-base"></i>
                    <div>
                        <span class="font-bold">Đơn hàng đang có khoản tiền thừa:</span>
                        <span class="font-extrabold text-sm text-purple-700 ml-1">
                            @if($overpaidUsd > 0 && $overpaidVnd > 0)
                                ${{ number_format($overpaidUsd, 2) }} + {{ number_format($overpaidVnd) }}đ
                            @elseif($overpaidUsd > 0)
                                ${{ number_format($overpaidUsd, 2) }}
                            @else
                                {{ number_format($overpaidVnd) }}đ
                            @endif
                        </span>
                        <span class="text-gray-500 italic block sm:inline sm:ml-1">(Do giảm giá trị đơn hàng sau khi nhận tiền)</span>
                    </div>
                </div>
            </div>
            @endif

            <div class="space-y-3">
                @if($hasPaymentRecords)
                    {{-- Hiển thị từ payment records (phiếu đã duyệt) --}}
                    @foreach($sale->payments->sortByDesc('payment_date')->sortByDesc('id') as $payment)
                    @php
                        // Xử lý cả số dương (payment) và số âm (refund)
                        $hasUsd = $payment->payment_usd != 0;
                        $hasVnd = $payment->payment_vnd != 0;
                        $exchangeRate = $payment->payment_exchange_rate ?? $sale->exchange_rate;
                        $isRefund = $payment->payment_usd < 0 || $payment->payment_vnd < 0 || $payment->transaction_type === 'refund';
                        $cardBgClass = $isRefund ? 'bg-red-50 border border-red-200' : 'bg-gray-50';
                        $colorClass = $isRefund ? 'text-red-600' : 'text-green-600';
                        $colorClassVnd = $isRefund ? 'text-red-600' : 'text-green-600';
                    @endphp
                    <div class="p-3 {{ $cardBgClass }} rounded-lg">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    @if($isRefund)
                                        <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-bold rounded-full border border-red-200">
                                             <i class="fas fa-undo mr-1"></i>Hoàn tiền thừa
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-semibold rounded-full border border-green-200">
                                            <i class="fas fa-arrow-down mr-1"></i>Thanh toán
                                        </span>
                                    @endif
                                </div>
                                
                                @if($hasUsd && !$hasVnd)
                                    {{-- Chỉ USD (hoặc refund USD) --}}
                                    <p class="font-bold text-lg {{ $colorClass }}">
                                        {{ $isRefund ? '-' : '' }}${{ number_format(abs($payment->payment_usd), 2) }}
                                    </p>
                                    @if($exchangeRate > 0)
                                    <p class="text-xs text-gray-500 mt-0.5">≈ {{ $isRefund ? '-' : '' }}{{ number_format(abs($payment->payment_usd * $exchangeRate)) }}đ (tỷ giá {{ number_format($exchangeRate) }})</p>
                                    @endif
                                @elseif($hasVnd && !$hasUsd)
                                    {{-- Chỉ VND (hoặc refund VND) --}}
                                    <p class="font-bold text-lg {{ $colorClassVnd }}">
                                        {{ $isRefund ? '-' : '' }}{{ number_format(abs($payment->payment_vnd)) }}đ
                                    </p>
                                    @if($exchangeRate > 0)
                                    <p class="text-xs text-gray-500 mt-0.5">≈ {{ $isRefund ? '-' : '' }}${{ number_format(abs($payment->payment_vnd / $exchangeRate), 2) }} (tỷ giá {{ number_format($exchangeRate) }})</p>
                                    @endif
                                @elseif($hasUsd && $hasVnd)
                                    {{-- Trả cả USD và VND --}}
                                    <p class="font-bold text-base">
                                        <span class="{{ $colorClass }}">{{ $isRefund ? '-' : '' }}${{ number_format(abs($payment->payment_usd), 2) }}</span>
                                        <span class="text-gray-400 mx-1">+</span>
                                        <span class="{{ $colorClassVnd }}">{{ $isRefund ? '-' : '' }}{{ number_format(abs($payment->payment_vnd)) }}đ</span>
                                    </p>
                                    @if($exchangeRate > 0)
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        Tổng: ≈ {{ $isRefund ? '-' : '' }}${{ number_format(abs($payment->payment_usd + ($payment->payment_vnd / $exchangeRate)), 2) }}
                                    </p>
                                    @endif
                                @else
                                    {{-- Fallback: Hiển thị amount --}}
                                    <p class="font-medium {{ $colorClass }}">{{ number_format($payment->amount) }}đ</p>
                                @endif
                                
                                <p class="text-xs text-gray-600 mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <span><i class="far fa-calendar-alt mr-1"></i>{{ $payment->payment_date->format('d/m/Y H:i') }}</span>
                                    <span>•</span>
                                    <span>
                                        @if($payment->payment_method == 'cash') Tiền mặt
                                        @elseif($payment->payment_method == 'bank_transfer') Chuyển khoản
                                        @elseif($payment->payment_method == 'card') Thẻ
                                        @else Khác
                                        @endif
                                    </span>
                                    @if($payment->createdBy)
                                    <span>•</span>
                                    <span class="text-gray-500"><i class="far fa-user mr-1"></i>{{ $payment->createdBy->name }}</span>
                                    @endif
                                    
                                    @if($sale->canEdit() && !$isRefund)
                                    <button type="button" 
                                        onclick="openEditPaymentModal(this)"
                                        data-id="{{ $payment->id }}"
                                        data-method="{{ $payment->payment_method }}"
                                        data-date="{{ $payment->payment_date ? $payment->payment_date->format('Y-m-d\TH:i') : '' }}"
                                        data-notes="{{ $payment->notes }}"
                                        class="ml-2 text-gray-400 hover:text-blue-600 transition-colors" 
                                        title="Chỉnh sửa thông tin thanh toán (ngày, phương thức, ghi chú)">
                                        <i class="fas fa-pen text-xs"></i>
                                    </button>
                                    @endif
                                </p>
                            </div>
                            @if($payment->notes)
                            <div class="text-xs bg-white px-2.5 py-1.5 rounded border border-gray-200 text-gray-700 max-w-[250px]">
                                <i class="fas fa-comment-dots text-gray-400 mr-1"></i>{{ $payment->notes }}
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                @else
                    {{-- Hiển thị thanh toán ban đầu từ sale (phiếu pending) --}}
                    <div class="p-3 bg-yellow-50 rounded border border-yellow-200">
                        <div class="flex justify-between items-center">
                            <div class="flex-1">
                                @php
                                    $hasUsd = ($sale->payment_usd ?? 0) > 0;
                                    $hasVnd = ($sale->payment_vnd ?? 0) > 0;
                                    $exchangeRate = $sale->exchange_rate;
                                @endphp
                                
                                @if($hasUsd && !$hasVnd)
                                    {{-- Chỉ trả USD --}}
                                    <p class="font-bold text-lg text-green-600">${{ number_format($sale->payment_usd, (abs($sale->payment_usd - round($sale->payment_usd)) < 0.01 ? 0 : 2)) }}</p>
                                    @if($exchangeRate > 0)
                                    <p class="text-xs text-gray-500 mt-0.5">≈ {{ number_format($sale->payment_usd * $exchangeRate) }}đ (tỷ giá {{ number_format($exchangeRate) }})</p>
                                    @endif
                                @elseif($hasVnd && !$hasUsd)
                                    {{-- Chỉ trả VND --}}
                                    <p class="font-bold text-lg text-green-600">{{ number_format($sale->payment_vnd) }}đ</p>
                                    @if($exchangeRate > 0)
                                    <p class="text-xs text-gray-500 mt-0.5">≈ ${{ number_format($sale->payment_vnd / $exchangeRate, (abs(($sale->payment_vnd / $exchangeRate) - round($sale->payment_vnd / $exchangeRate)) < 0.01 ? 0 : 2)) }} (tỷ giá {{ number_format($exchangeRate) }})</p>
                                    @endif
                                @elseif($hasUsd && $hasVnd)
                                    {{-- Trả cả USD và VND --}}
                                    <p class="font-bold text-base">
                                        <span class="text-green-600">${{ number_format($sale->payment_usd, (abs($sale->payment_usd - round($sale->payment_usd)) < 0.01 ? 0 : 2)) }}</span>
                                        <span class="text-gray-400 mx-1">+</span>
                                        <span class="text-green-600">{{ number_format($sale->payment_vnd) }}đ</span>
                                    </p>
                                    @if($exchangeRate > 0)
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        Tổng: ≈ ${{ number_format($sale->payment_usd + ($sale->payment_vnd / $exchangeRate), (abs(($sale->payment_usd + ($sale->payment_vnd / $exchangeRate)) - round($sale->payment_usd + ($sale->payment_vnd / $exchangeRate))) < 0.01 ? 0 : 2)) }}
                                    </p>
                                    @endif
                                @endif
                                
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ $sale->created_at->format('d/m/Y H:i') }} - 
                                    @if($sale->payment_method == 'cash') Tiền mặt
                                    @elseif($sale->payment_method == 'bank_transfer') Chuyển khoản
                                    @elseif($sale->payment_method == 'card') Thẻ
                                    @else Khác
                                    @endif
                                    <span class="ml-2 text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded">Chờ duyệt</span>
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
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
                @php
                    // Detect currency dựa trên original_total (trước khi trả hàng nếu có completed returns) hoặc items hiện tại
                    $hasCompletedReturns = $sale->returns()->where('status', 'completed')->where('type', 'return')->exists();
                    $originalHasUsd = $hasCompletedReturns ? (($sale->original_total_usd ?? $sale->total_usd) > 0) : ($sale->total_usd > 0);
                    $originalHasVnd = $hasCompletedReturns ? (($sale->original_total_vnd ?? $sale->total_vnd) > 0) : ($sale->total_vnd > 0);
                    
                    // Detect currency hiện tại (sau khi trả hàng)
                    $hasUsdTotal = $sale->total_usd > 0;
                    $hasVndTotal = $sale->total_vnd > 0;
                    
                    // Nếu ban đầu có cả USD và VND, giữ nguyên flag này
                    $isMixedCurrency = $originalHasUsd && $originalHasVnd;
                @endphp
                
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Tạm tính:</span>
                    <div class="text-right">
                        @if($hasUsdTotal && !$hasVndTotal)
                            <div class="font-medium text-xs">${{ number_format($sale->subtotal_usd, 0) }}</div>
                        @elseif($hasVndTotal && !$hasUsdTotal)
                            <div class="font-medium text-xs">{{ number_format($sale->subtotal_vnd) }}đ</div>
                        @else
                            {{-- Cả USD và VND - Hiển thị đều nhau --}}
                            <div class="font-medium text-xs text-blue-600">${{ number_format($sale->subtotal_usd, 0) }}</div>
                            <div class="font-medium text-xs text-green-600">{{ number_format($sale->subtotal_vnd) }}đ</div>
                        @endif
                    </div>
                </div>
                @if($sale->discount_percent > 0)
                <div class="flex justify-between text-red-600 text-sm">
                    <span>Giảm ({{ $sale->discount_percent }}%):</span>
                    <div class="text-right">
                        @if($hasUsdTotal && !$hasVndTotal)
                            <div class="font-medium text-xs">-${{ number_format($sale->saleItems->sum('total_usd') * ($sale->discount_percent/100), 2) }}</div>
                        @elseif($hasVndTotal && !$hasUsdTotal)
                            <div class="font-medium text-xs">-{{ number_format($sale->saleItems->sum('total_vnd') * ($sale->discount_percent/100)) }}đ</div>
                        @else
                            {{-- Cả USD và VND --}}
                            <div class="font-medium text-xs">-${{ number_format($sale->saleItems->sum('total_usd') * ($sale->discount_percent/100), 2) }}</div>
                            <div class="font-medium text-xs">-{{ number_format($sale->saleItems->sum('total_vnd') * ($sale->discount_percent/100)) }}đ</div>
                        @endif
                    </div>
                </div>
                @endif

                @if($sale->discount_amount_usd > 0 || $sale->discount_amount_vnd > 0)
                <div class="flex justify-between text-red-600 text-sm">
                    <span>Giảm tiền:</span>
                    <div class="text-right">
                        @if($sale->discount_amount_usd > 0)
                            <div class="font-medium text-xs">-${{ number_format($sale->discount_amount_usd, 0) }}</div>
                        @endif
                        @if($sale->discount_amount_vnd > 0)
                            <div class="font-medium text-xs">-{{ number_format($sale->discount_amount_vnd) }}đ</div>
                        @endif
                    </div>
                </div>
                @endif
                @if($sale->shipping_fee_usd > 0 || $sale->shipping_fee_vnd > 0)
                <div class="flex justify-between text-blue-600 text-sm">
                    <span>Phí vận chuyển:</span>
                    <div class="text-right">
                        @if($sale->shipping_fee_usd > 0)
                            <div class="font-medium text-xs">+${{ number_format($sale->shipping_fee_usd, 0) }}</div>
                        @endif
                        @if($sale->shipping_fee_vnd > 0)
                            <div class="font-medium text-xs">+{{ number_format($sale->shipping_fee_vnd) }}đ</div>
                        @endif
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
                                $exchangeRateCalc = $sale->exchange_rate ?: 1;
                                $originalTotalUsd = $originalTotal / $exchangeRateCalc;
                            }
                            
                            // Kiểm tra xem có thay đổi tổng tiền không
                            $totalChanged = ($hasReturns || $hasExchanges) && ($originalTotal != $sale->total_vnd || $originalTotalUsd != $sale->total_usd);
                            
                            // Kiểm tra trả hết (tất cả items đã returned)
                            $allReturnedShow = $sale->saleItems->where('is_returned', true)->count() == $sale->saleItems->count() && $sale->saleItems->count() > 0;
                        @endphp
                        
                        @if($allReturnedShow || ($hasReturns && $sale->total_vnd == 0 && $sale->total_usd == 0))
                            <!-- Trả hết - hiển thị giá gốc không gạch ngang -->
                            <div class="font-bold text-base text-gray-900">${{ number_format($originalTotalUsd, 0) }}</div>
                            <div class="text-xs text-gray-500">{{ number_format($originalTotal) }}đ</div>
                            <div class="text-xs text-red-600 mt-0.5">
                                <i class="fas fa-undo"></i>Trả hết
                            </div>
                        @elseif($totalChanged)
                            <!-- Có thay đổi (trả hàng hoặc đổi hàng) - hiển thị tổng cũ bị gạch -->
                            <div class="text-xs text-gray-400 line-through mb-0.5">
                                ${{ number_format($originalTotalUsd, 0) }} / {{ number_format($originalTotal) }}đ
                            </div>
                            <!-- Hiển thị tổng mới -->
                            <div class="font-bold text-base {{ $hasExchanges ? 'text-purple-600' : 'text-green-600' }}">
                                ${{ number_format($sale->total_usd, 0) }}
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
                            @if($hasUsdTotal && !$hasVndTotal)
                                <div class="font-bold text-base text-green-600">${{ number_format($sale->total_usd, 0) }}</div>
                            @elseif($hasVndTotal && !$hasUsdTotal)
                                <div class="font-bold text-base text-green-600">{{ number_format($sale->total_vnd) }}đ</div>
                            @else
                                {{-- Cả USD và VND - Hiển thị đều nhau --}}
                                <div class="font-bold text-base text-blue-600">${{ number_format($sale->total_usd, 0) }}</div>
                                <div class="font-bold text-base text-green-600">{{ number_format($sale->total_vnd) }}đ</div>
                            @endif
                        @endif
                    </div>
                </div>
                <div class="bg-blue-50 p-2 rounded text-sm">
                    <div class="flex justify-between">
                        <span class="text-blue-700 font-medium">Đã trả:</span>
                        <div class="text-right">
                            @if($isMixedCurrency)
                                {{-- Hóa đơn ban đầu có cả USD và VND - Hiển thị riêng từng loại --}}
                                @if($sale->paid_usd > 0 && $sale->paid_vnd > 0)
                                    <div class="font-bold text-sm text-blue-700">
                                        <div class="text-blue-600">USD: ${{ number_format($sale->paid_usd, (abs($sale->paid_usd - round($sale->paid_usd)) < 0.01 ? 0 : 2)) }}</div>
                                        <div class="text-green-600">VND: {{ number_format($sale->paid_vnd) }}đ</div>
                                    </div>
                                @elseif($sale->paid_usd > 0)
                                    <div class="font-bold text-blue-700">${{ number_format($sale->paid_usd, (abs($sale->paid_usd - round($sale->paid_usd)) < 0.01 ? 0 : 2)) }}</div>
                                @elseif($sale->paid_vnd > 0)
                                    <div class="font-bold text-blue-700">{{ number_format($sale->paid_vnd) }}đ</div>
                                @else
                                    <div class="font-bold text-blue-700">$0 / 0đ</div>
                                @endif
                            @elseif($originalHasUsd && !$originalHasVnd)
                                {{-- Chỉ USD --}}
                                @php
                                    $rawVndPaid = $sale->payments && $sale->payments->count() > 0 ? $sale->payments->sum('payment_vnd') : (float)($sale->payment_vnd ?? 0);
                                    $rawUsdPaid = $sale->payments && $sale->payments->count() > 0 ? $sale->payments->sum('payment_usd') : (float)($sale->payment_usd ?? 0);
                                @endphp
                                @if($rawVndPaid > 0 && $rawUsdPaid <= 0)
                                    <div class="font-bold text-blue-700">{{ number_format($rawVndPaid) }}đ</div>
                                    <div class="text-xs text-blue-600">≈ ${{ number_format($sale->paid_usd, (abs($sale->paid_usd - round($sale->paid_usd)) < 0.01 ? 0 : 2)) }}</div>
                                @elseif($rawVndPaid > 0 && $rawUsdPaid > 0)
                                    <div class="font-bold text-blue-700">${{ number_format($sale->paid_usd, (abs($sale->paid_usd - round($sale->paid_usd)) < 0.01 ? 0 : 2)) }}</div>
                                    <div class="text-xs text-blue-600">(${{ number_format($rawUsdPaid, (abs($rawUsdPaid - round($rawUsdPaid)) < 0.01 ? 0 : 2)) }} + {{ number_format($rawVndPaid) }}đ)</div>
                                @else
                                    <div class="font-bold text-blue-700">${{ number_format($sale->paid_usd, (abs($sale->paid_usd - round($sale->paid_usd)) < 0.01 ? 0 : 2)) }}</div>
                                @endif
                            @elseif($originalHasVnd && !$originalHasUsd)
                                {{-- Chỉ VND --}}
                                @php
                                    $rawVndPaid = $sale->payments && $sale->payments->count() > 0 ? $sale->payments->sum('payment_vnd') : (float)($sale->payment_vnd ?? 0);
                                    $rawUsdPaid = $sale->payments && $sale->payments->count() > 0 ? $sale->payments->sum('payment_usd') : (float)($sale->payment_usd ?? 0);
                                @endphp
                                @if($rawUsdPaid > 0 && $rawVndPaid <= 0)
                                    <div class="font-bold text-blue-700">${{ number_format($rawUsdPaid, (abs($rawUsdPaid - round($rawUsdPaid)) < 0.01 ? 0 : 2)) }}</div>
                                    <div class="text-xs text-blue-600">≈ {{ number_format($sale->paid_vnd) }}đ</div>
                                @elseif($rawUsdPaid > 0 && $rawVndPaid > 0)
                                    <div class="font-bold text-blue-700">{{ number_format($sale->paid_vnd) }}đ</div>
                                    <div class="text-xs text-blue-600">({{ number_format($rawVndPaid) }}đ + ${{ number_format($rawUsdPaid, (abs($rawUsdPaid - round($rawUsdPaid)) < 0.01 ? 0 : 2)) }})</div>
                                @else
                                    <div class="font-bold text-blue-700">{{ number_format($sale->paid_vnd) }}đ</div>
                                @endif
                            @endif
                        </div>
                    </div>
                    
                    @php
                        $overpaidUsd = (float)($sale->overpaid_usd ?? 0);
                    @endphp
                    
                    @if($overpaidUsd > 0.05 && $sale->total_vnd <= 0)
                        <div class="mt-1 text-xs text-blue-800 bg-blue-100 px-2 py-1 rounded border border-blue-200">
                            <i class="fas fa-info-circle mr-1"></i>
                            Gồm: ${{ number_format($sale->total_usd, (abs($sale->total_usd - round($sale->total_usd)) < 0.01 ? 0 : 2)) }} gốc
                            <span class="block text-right">+ ${{ number_format($overpaidUsd, (abs($overpaidUsd - round($overpaidUsd)) < 0.01 ? 0 : 2)) }}</span>
                        </div>
                    @endif
                </div>
                @if($sale->sale_status == 'cancelled')
                <div class="flex justify-between text-gray-600 bg-gray-50 p-2 rounded border border-gray-200 text-sm">
                    <span class="font-bold">
                        <i class="fas fa-ban"></i>Đã hủy
                    </span>
                    <span class="font-bold">Không nợ</span>
                </div>
                @elseif($sale->debt_usd > 0.05 || $sale->debt_vnd > 1000)
                <div class="flex justify-between text-red-600 bg-red-50 p-2 rounded border border-red-200 text-sm">
                    <span class="font-bold">Còn thiếu:</span>
                    <div class="text-right">
                        @if($sale->debt_usd > 0.05 && $sale->debt_vnd > 1000)
                            <div class="font-bold text-sm">
                                <span class="text-blue-600">USD: ${{ number_format($sale->debt_usd, (abs($sale->debt_usd - round($sale->debt_usd)) < 0.01 ? 0 : 2)) }}</span>
                            </div>
                            <div class="font-bold text-sm">
                                <span class="text-green-600">VND: {{ number_format($sale->debt_vnd) }}đ</span>
                            </div>
                        @elseif($sale->debt_usd > 0.05)
                            <div class="font-bold text-base text-blue-600">USD: ${{ number_format($sale->debt_usd, (abs($sale->debt_usd - round($sale->debt_usd)) < 0.01 ? 0 : 2)) }}</div>
                            @if($sale->exchange_rate > 0)
                            <div class="text-xs">≈ {{ number_format($sale->debt_usd * $sale->exchange_rate) }}đ</div>
                            @endif
                        @elseif($sale->debt_vnd > 1000)
                            <div class="font-bold text-base text-green-600">VND: {{ number_format($sale->debt_vnd) }}đ</div>
                            @if($sale->exchange_rate > 0)
                            <div class="text-xs">≈ ${{ number_format($sale->debt_vnd / $sale->exchange_rate, (abs(($sale->debt_vnd / $sale->exchange_rate) - round($sale->debt_vnd / $sale->exchange_rate)) < 0.01 ? 0 : 2)) }}</div>
                            @endif
                        @endif
                    </div>
                </div>
                @else
                <div class="flex flex-col text-green-600 bg-green-50 p-2 rounded border border-green-200 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="font-bold">
                            Đã thanh toán đủ
                        </span>
                        @if($overpaidUsd > 0.05 && $sale->total_vnd <= 0)
                            <span class="text-xs bg-green-100 px-2 py-0.5 rounded text-green-800 border border-green-200">
                                Dư ${{ number_format($overpaidUsd, (abs($overpaidUsd - round($overpaidUsd)) < 0.01 ? 0 : 2)) }} (Do tỷ giá)
                            </span>
                        @endif
                    </div>
                </div>
                @endif

                @if($hasOverpayment)
                <div class="bg-purple-50 p-3 rounded-xl border border-purple-200 text-sm mt-2">
                    <div class="flex justify-between items-center mb-1">
                        <span class="font-bold text-purple-900 flex items-center text-xs">
                            <i class="fas fa-hand-holding-usd text-purple-600 mr-1.5 text-sm"></i>Dư tiền cần hoàn:
                        </span>
                        <span class="font-extrabold text-sm text-purple-700">
                            @if($overpaidUsd > 0 && $overpaidVnd > 0)
                                ${{ number_format($overpaidUsd, 2) }} + {{ number_format($overpaidVnd) }}đ
                            @elseif($overpaidUsd > 0)
                                ${{ number_format($overpaidUsd, 2) }}
                            @else
                                {{ number_format($overpaidVnd) }}đ
                            @endif
                        </span>
                    </div>
                    <p class="text-[11px] text-purple-600 mb-2 italic">Tiền khách trả lớn hơn giá trị đơn sau khi cập nhật</p>
                    @if($canEditSale)
                    <button type="button" onclick="openRefundModal()" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 px-3 rounded-lg text-xs font-bold transition-all shadow-md flex items-center justify-center">
                        <i class="fas fa-undo mr-1.5"></i>Tạo phiếu hoàn tiền
                    </button>
                    @endif
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
                        @php
                            // Kiểm tra trả hết (tất cả items đã returned)
                            $allReturnedStatus = $sale->saleItems->where('is_returned', true)->count() == $sale->saleItems->count() && $sale->saleItems->count() > 0;
                        @endphp
                        @if($sale->sale_status == 'cancelled' || $allReturnedStatus)
                            <span class="inline-block px-4 py-2 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">
                                <i class="fas fa-ban mr-1"></i>Đã hủy
                            </span>
                        @elseif($sale->sale_status == 'pending')
                            <span class="inline-block px-4 py-2 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                <i class="fas fa-clock mr-1"></i>Chờ duyệt
                            </span>
                        @elseif($sale->sale_status == 'completed')
                            <span class="inline-block px-4 py-2 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Đã hoàn thành
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Payment Status -->
                <!-- <div class="pt-3 border-t">
                    <p class="text-sm text-gray-600 mb-2 font-medium">Thanh toán:</p>
                    <div class="text-center">
                        @php
                            $hasExchange = $sale->returns->where('type', 'exchange')->where('status', 'completed')->count() > 0;
                        @endphp
                        @if($sale->payment_status == 'cancelled' || $allReturnedStatus)
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
                </div> -->
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
function showImageModalFromElement(el) {
    if (el && el.dataset) {
        showImageModal(el.dataset.imageSrc, el.dataset.imageTitle);
    }
}

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

@push('scripts')
<!-- Edit Payment Modal (Draggable & No Background Blur) -->
<div id="editPaymentModal" class="fixed inset-0 z-50 hidden pointer-events-none flex items-center justify-center p-4" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div id="editPaymentCard" class="pointer-events-auto w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden select-none transition-shadow">
        <form id="editPaymentForm" method="POST" action="">
            @csrf
            @method('PUT')
            
            <!-- Header with Gradient (Draggable Handle) -->
            <div id="editPaymentHeader" class="bg-gradient-to-r from-blue-600 to-indigo-700 px-6 py-4 flex items-center justify-between cursor-move active:cursor-grabbing select-none" title="Kéo để di chuyển cửa sổ">
                <h3 class="text-lg font-bold text-white flex items-center pointer-events-none" id="modal-title">
                    <i class="fas fa-arrows-alt mr-2.5 text-sm opacity-80"></i>
                    <i class="fas fa-edit mr-2 opacity-90"></i>
                    Cập nhật thanh toán
                </h3>
                <button type="button" onclick="closeEditPaymentModal()" class="text-white opacity-75 hover:opacity-100 transition-opacity p-1 rounded-lg hover:bg-white/10" title="Đóng">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="bg-white px-6 py-6 select-text">
                <div class="space-y-5">
                    <!-- Payment Date Field -->
                    <div>
                        <label for="modal_payment_date" class="block text-sm font-semibold text-gray-700 mb-1.5 flex items-center">
                            <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                            Ngày & Giờ thanh toán
                        </label>
                        <input type="datetime-local" name="payment_date" id="modal_payment_date"
                            class="block w-full px-4 py-2.5 text-gray-900 border border-gray-300 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all bg-gray-50/50">
                        <p class="text-xs text-gray-500 mt-1">Chọn ngày và giờ khách thực tế đã chuyển khoản/thanh toán</p>
                    </div>

                    <!-- Payment Method Field -->
                    <div>
                        <label for="payment_method" class="block text-sm font-semibold text-gray-700 mb-1.5 flex items-center">
                            <i class="fas fa-wallet mr-2 text-blue-500"></i>
                            Hình thức thanh toán
                        </label>
                        <div class="relative">
                            <select name="payment_method" id="payment_method" 
                                class="block w-full pl-4 pr-10 py-2.5 text-gray-900 border border-gray-300 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all appearance-none bg-gray-50/50">
                                <option value="cash">Tiền mặt</option>
                                <option value="bank_transfer">Chuyển khoản</option>
                                <option value="card">Thẻ</option>
                                <option value="other">Khác</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Notes Field -->
                    <div>
                        <label for="modal_notes" class="block text-sm font-semibold text-gray-700 mb-1.5 flex items-center">
                            <i class="fas fa-sticky-note mr-2 text-blue-500"></i>
                            Ghi chú
                        </label>
                        <textarea name="notes" id="modal_notes" rows="3" 
                            class="block w-full px-4 py-3 text-gray-900 border border-gray-300 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all bg-gray-50/50 placeholder-gray-400"
                            placeholder="Nhập ghi chú thanh toán..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Footer with Actions -->
            <div class="px-6 py-4 bg-gray-50 flex flex-row-reverse gap-3 border-t border-gray-100 select-text">
                <button type="submit" class="inline-flex justify-center items-center px-6 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl shadow-lg shadow-blue-600/30 hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/40 transform active:scale-95 transition-all">
                    <i class="fas fa-check mr-2"></i>
                    Cập nhật
                </button>
                <button type="button" onclick="closeEditPaymentModal()" class="inline-flex justify-center items-center px-6 py-2.5 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 hover:text-gray-800 transition-all">
                    Hủy
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditPaymentModal(btn) {
        const id = btn.dataset.id;
        const method = btn.dataset.method;
        const date = btn.dataset.date;
        const notes = btn.dataset.notes;
        
        let url = "{{ route('payments.update', ':id') }}";
        url = url.replace(':id', id);
        
        document.getElementById('editPaymentForm').action = url;
        document.getElementById('payment_method').value = method;
        if (document.getElementById('modal_payment_date')) {
            document.getElementById('modal_payment_date').value = date || '';
        }
        document.getElementById('modal_notes').value = notes || '';
        
        const modal = document.getElementById('editPaymentModal');
        const card = document.getElementById('editPaymentCard');
        
        // Reset modal position when re-opening
        card.style.position = '';
        card.style.left = '';
        card.style.top = '';
        card.style.margin = '';
        card.style.transform = '';
        
        modal.classList.remove('hidden');
    }

    function closeEditPaymentModal() {
        document.getElementById('editPaymentModal').classList.add('hidden');
    }

    // Draggable modal logic
    (function initDraggableModal() {
        const header = document.getElementById('editPaymentHeader');
        const card = document.getElementById('editPaymentCard');
        if (!header || !card) return;

        let isDragging = false;
        let startX, startY, initialLeft, initialTop;

        header.addEventListener('mousedown', function(e) {
            // Không kéo khi click vào nút đóng
            if (e.target.closest('button')) return;

            isDragging = true;
            
            const rect = card.getBoundingClientRect();
            startX = e.clientX;
            startY = e.clientY;
            initialLeft = rect.left;
            initialTop = rect.top;

            card.style.position = 'fixed';
            card.style.left = `${initialLeft}px`;
            card.style.top = `${initialTop}px`;
            card.style.margin = '0';
            card.style.transform = 'none';

            document.body.style.userSelect = 'none';
        });

        document.addEventListener('mousemove', function(e) {
            if (!isDragging) return;

            const dx = e.clientX - startX;
            const dy = e.clientY - startY;

            let newLeft = initialLeft + dx;
            let newTop = initialTop + dy;

            // Giới hạn để không kéo modal mất khỏi màn hình
            const minVisible = 80;
            const maxLeft = window.innerWidth - minVisible;
            const maxTop = window.innerHeight - minVisible;

            newLeft = Math.max(-card.offsetWidth + minVisible, Math.min(maxLeft, newLeft));
            newTop = Math.max(10, Math.min(maxTop, newTop));

            card.style.left = `${newLeft}px`;
            card.style.top = `${newTop}px`;
        });

        document.addEventListener('mouseup', function() {
            if (isDragging) {
                isDragging = false;
                document.body.style.userSelect = '';
            }
        });
    })();
</script>

<!-- Refund Overpayment Modal -->
<div id="refundModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="refund-modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Overlay -->
        <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-60 backdrop-blur-sm" aria-hidden="true" onclick="closeRefundModal()"></div>
        
        <!-- Center modal -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
            <form id="refundForm" method="POST" action="{{ route('sales.refund', $sale->id) }}">
                @csrf
                
                <!-- Header with Purple Gradient -->
                <div class="bg-gradient-to-r from-purple-600 to-indigo-700 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white flex items-center" id="refund-modal-title">
                        <i class="fas fa-hand-holding-usd mr-3 opacity-90"></i>
                        Hoàn tiền thừa cho khách hàng
                    </h3>
                    <button type="button" onclick="closeRefundModal()" class="text-white opacity-70 hover:opacity-100 transition-opacity">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="bg-white px-6 py-6 space-y-4">
                    <!-- Info Alert -->
                    <div class="p-3 bg-purple-50 border border-purple-200 rounded-xl text-xs text-purple-900">
                        <div class="font-bold flex items-center mb-1">
                            <i class="fas fa-info-circle mr-1.5 text-purple-600"></i>Thông tin tiền thừa:
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-1">
                            <div>Tiền thừa (VND): <strong class="text-purple-700">{{ number_format($overpaidVnd) }}đ</strong></div>
                            @if($overpaidUsd > 0)
                            <div>Tiền thừa (USD): <strong class="text-purple-700">${{ number_format($overpaidUsd, 2) }}</strong></div>
                            @endif
                        </div>
                    </div>

                    <!-- Refund Amount VND -->
                    @if($overpaidVnd > 0 || $sale->total_vnd > 0 || ($overpaidVnd == 0 && $overpaidUsd == 0))
                    <div>
                        <label for="refund_amount_vnd" class="block text-sm font-semibold text-gray-700 mb-1">
                            Số tiền hoàn (VND) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="refund_amount_vnd" id="refund_amount_vnd" 
                            value="{{ number_format($overpaidVnd) }}"
                            oninput="formatVND(this)"
                            class="block w-full px-4 py-2.5 text-gray-900 border border-gray-300 rounded-xl focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 font-bold text-base"
                            placeholder="0">
                    </div>
                    @endif

                    <!-- Refund Amount USD -->
                    @if($overpaidUsd > 0 || $sale->total_usd > 0)
                    <div>
                        <label for="refund_amount_usd" class="block text-sm font-semibold text-gray-700 mb-1">
                            Số tiền hoàn (USD)
                        </label>
                        <input type="text" name="refund_amount_usd" id="refund_amount_usd" 
                            value="{{ $overpaidUsd > 0 ? number_format($overpaidUsd, 2) : '0' }}"
                            oninput="formatUSD(this)"
                            class="block w-full px-4 py-2.5 text-gray-900 border border-gray-300 rounded-xl focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 font-bold text-base"
                            placeholder="0">
                    </div>
                    @endif

                    <!-- Payment Method Field -->
                    <div>
                        <label for="refund_payment_method" class="block text-sm font-semibold text-gray-700 mb-1">
                            Hình thức hoàn tiền <span class="text-red-500">*</span>
                        </label>
                        <select name="payment_method" id="refund_payment_method" 
                            class="block w-full px-4 py-2.5 text-gray-900 border border-gray-300 rounded-xl focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 bg-gray-50/50">
                            <option value="bank_transfer" selected>Chuyển khoản</option>
                            <option value="cash">Tiền mặt</option>
                            <option value="card">Thẻ</option>
                            <option value="other">Khác</option>
                        </select>
                    </div>

                    <!-- Refund Date -->
                    <div>
                        <label for="refund_date" class="block text-sm font-semibold text-gray-700 mb-1">
                            Ngày hoàn tiền
                        </label>
                        <input type="datetime-local" name="refund_date" id="refund_date" 
                            value="{{ now('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i') }}"
                            class="block w-full px-4 py-2 text-gray-900 border border-gray-300 rounded-xl focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 bg-gray-50/50 text-sm">
                    </div>

                    <!-- Notes Field -->
                    <div>
                        <label for="refund_notes" class="block text-sm font-semibold text-gray-700 mb-1">
                            Ghi chú / Lý do hoàn tiền
                        </label>
                        <textarea name="notes" id="refund_notes" rows="2" 
                            class="block w-full px-4 py-2.5 text-gray-900 border border-gray-300 rounded-xl focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 bg-gray-50/50 text-sm placeholder-gray-400"
                            placeholder="Nhập lý do hoàn tiền thừa...">Hoàn tiền thừa do điều chỉnh giảm giá trị hóa đơn {{ $sale->invoice_code }}</textarea>
                    </div>
                </div>

                <!-- Footer with Actions -->
                <div class="px-6 py-4 bg-gray-50 flex flex-row-reverse gap-3 border-t border-gray-100">
                    <button type="submit" onclick="return confirm('Xác nhận tạo phiếu hoàn tiền cho khách hàng?')" class="inline-flex justify-center items-center px-6 py-2.5 text-sm font-bold text-white bg-purple-600 rounded-xl shadow-lg shadow-purple-600/30 hover:bg-purple-700 focus:outline-none focus:ring-4 focus:ring-purple-500/40 transform active:scale-95 transition-all">
                        <i class="fas fa-check mr-2"></i>
                        Xác nhận hoàn tiền
                    </button>
                    <button type="button" onclick="closeRefundModal()" class="inline-flex justify-center items-center px-6 py-2.5 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 hover:text-gray-800 transition-all">
                        Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openRefundModal() {
        const refundDateInput = document.getElementById('refund_date');
        if (refundDateInput) {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            refundDateInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
        }
        document.getElementById('refundModal').classList.remove('hidden');
    }

    function closeRefundModal() {
        document.getElementById('refundModal').classList.add('hidden');
    }

    // Helper formatters
    function formatVND(input) {
        let value = input.value.replace(/[^\d]/g, '');
        if (value) {
            input.value = parseInt(value).toLocaleString('vi-VN');
        }
    }

    function formatUSD(input) {
        let value = input.value.replace(/[^\d.]/g, '');
        const parts = value.split('.');
        if (parts.length > 2) {
            value = parts[0] + '.' + parts.slice(1).join('');
            parts.length = 2;
            parts[0] = value.split('.')[0];
            parts[1] = value.split('.')[1];
        }
        if (parts[0]) {
            parts[0] = parseInt(parts[0]).toLocaleString('en-US');
        }
        if (parts[1]) {
            parts[1] = parts[1].substring(0, 2);
        }
        input.value = parts.length > 1 ? parts[0] + '.' + (parts[1] || '') : parts[0];
    }
</script>
@endpush
