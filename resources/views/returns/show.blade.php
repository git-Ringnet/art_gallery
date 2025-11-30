@extends('layouts.app')

@section('title', 'Chi tiết phiếu trả hàng')
@section('page-title', 'Chi tiết phiếu trả hàng')
@section('page-description', 'Thông tin chi tiết giao dịch trả hàng')

@section('content')
<x-alert />

<div class="bg-white rounded-xl shadow-lg p-4 glass-effect">
    @php
        $exchangeRate = ($return->exchange_rate && $return->exchange_rate > 0) ? $return->exchange_rate : 25000;
        
        // LOGIC: Detect currency từ sale gốc - dùng currency field
        $sale = $return->sale;
        $isUsdPrimary = false;
        $isVndPrimary = false;
        $isMixed = false;
        
        // Detect từ sale items currency field (chính xác hơn)
        if ($sale) {
            $usdItems = $sale->items->where('currency', 'USD')->count();
            $vndItems = $sale->items->where('currency', 'VND')->count();
            
            if ($usdItems > 0 && $vndItems == 0) {
                $isUsdPrimary = true;
            } elseif ($vndItems > 0 && $usdItems == 0) {
                $isVndPrimary = true;
            } elseif ($usdItems > 0 && $vndItems > 0) {
                $isMixed = true;
            }
        }
        
        // Fallback: nếu không detect được, dùng VND mặc định
        if (!$isUsdPrimary && !$isVndPrimary && !$isMixed) {
            $isVndPrimary = true;
        }
    @endphp
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
    @if($return->type == 'exchange' && ($return->exchange_amount > 0 || $return->exchange_amount_usd > 0))
        @php
            // Kiểm tra xem có thông tin payment trong notes không
            $hasPendingPayment = strpos($return->notes, '[PAYMENT_INFO]') !== false;
            
            // Lấy exchange amount (riêng USD và VND)
            $exchangeAmountUsd = $return->exchange_amount_usd ?? 0;
            $exchangeAmountVnd = $return->exchange_amount ?? 0;
            
            // Tính số tiền đã trả cho phiếu đổi hàng này (riêng USD và VND)
            $exchangePaymentsUsd = $return->sale->payments()
                ->where('transaction_type', 'exchange_payment')
                ->where('notes', 'like', "%{$return->return_code}%")
                ->sum('payment_usd');
            $exchangePaymentsVnd = $return->sale->payments()
                ->where('transaction_type', 'exchange_payment')
                ->where('notes', 'like', "%{$return->return_code}%")
                ->sum('payment_vnd');
            
            // Lấy tỷ giá từ payment
            $topPaymentExchangeRate = $return->sale->payments()
                ->where('transaction_type', 'exchange_payment')
                ->where('notes', 'like', "%{$return->return_code}%")
                ->value('payment_exchange_rate') ?? ($return->sale->exchange_rate ?? 1);
            
            // Xử lý thanh toán chéo (backward compatibility)
            if ($exchangeAmountUsd > 0 && $exchangeAmountVnd == 0 && $exchangePaymentsUsd == 0 && $exchangePaymentsVnd > 0 && $topPaymentExchangeRate > 0) {
                $exchangePaymentsUsd = $exchangePaymentsVnd / $topPaymentExchangeRate;
                $exchangePaymentsVnd = 0;
            } elseif ($exchangeAmountVnd > 0 && $exchangeAmountUsd == 0 && $exchangePaymentsVnd == 0 && $exchangePaymentsUsd > 0 && $topPaymentExchangeRate > 0) {
                $exchangePaymentsVnd = $exchangePaymentsUsd * $topPaymentExchangeRate;
                $exchangePaymentsUsd = 0;
            }
        @endphp
        
        @if($return->status == 'pending' && $hasPendingPayment)
        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-yellow-600 mr-2"></i>
                <div>
                    <h4 class="font-semibold text-sm text-yellow-800">Thông báo về thanh toán</h4>
                    <p class="text-xs text-yellow-700 mt-1">
                        Khách hàng cần trả thêm 
                        @if($exchangeAmountUsd > 0 && $exchangeAmountVnd > 0)
                            <strong>${{ number_format($exchangeAmountUsd, 2) }}</strong> + <strong>{{ number_format($exchangeAmountVnd, 0, ',', '.') }}đ</strong>
                        @elseif($exchangeAmountUsd > 0)
                            <strong>${{ number_format($exchangeAmountUsd, 2) }}</strong>
                        @else
                            <strong>{{ number_format($exchangeAmountVnd, 0, ',', '.') }}đ</strong>
                        @endif
                        cho đơn đổi hàng này.
                        @if($exchangePaymentsUsd > 0 || $exchangePaymentsVnd > 0)
                        <br><strong>Đã trả:</strong> 
                        @if($exchangePaymentsUsd > 0 && $exchangePaymentsVnd > 0)
                            ${{ number_format($exchangePaymentsUsd, 2) }} + {{ number_format($exchangePaymentsVnd, 0, ',', '.') }}đ
                        @elseif($exchangePaymentsUsd > 0)
                            ${{ number_format($exchangePaymentsUsd, 2) }}
                        @else
                            {{ number_format($exchangePaymentsVnd, 0, ',', '.') }}đ
                        @endif
                        cho sản phẩm mới.
                        <br>Còn lại: 
                        @php
                            $remainingUsd = $exchangeAmountUsd - $exchangePaymentsUsd;
                            $remainingVnd = $exchangeAmountVnd - $exchangePaymentsVnd;
                        @endphp
                        @if($remainingUsd > 0 && $remainingVnd > 0)
                            <strong>${{ number_format($remainingUsd, 2) }}</strong> + <strong>{{ number_format($remainingVnd, 0, ',', '.') }}đ</strong>
                        @elseif($remainingUsd > 0)
                            <strong>${{ number_format($remainingUsd, 2) }}</strong>
                        @elseif($remainingVnd > 0)
                            <strong>{{ number_format($remainingVnd, 0, ',', '.') }}đ</strong>
                        @else
                            <strong>$0.00</strong>
                        @endif
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
                        Khách hàng cần trả thêm 
                        @if($exchangeAmountUsd > 0 && $exchangeAmountVnd > 0)
                            <strong>${{ number_format($exchangeAmountUsd, 2) }}</strong> + <strong>{{ number_format($exchangeAmountVnd, 0, ',', '.') }}đ</strong>
                        @elseif($exchangeAmountUsd > 0)
                            <strong>${{ number_format($exchangeAmountUsd, 2) }}</strong>
                        @else
                            <strong>{{ number_format($exchangeAmountVnd, 0, ',', '.') }}đ</strong>
                        @endif
                        cho đơn đổi hàng này.
                        @if($exchangePaymentsUsd > 0 || $exchangePaymentsVnd > 0)
                        <br><strong>Đã trả:</strong> 
                        @if($exchangePaymentsUsd > 0 && $exchangePaymentsVnd > 0)
                            ${{ number_format($exchangePaymentsUsd, 2) }} + {{ number_format($exchangePaymentsVnd, 0, ',', '.') }}đ
                        @elseif($exchangePaymentsUsd > 0)
                            ${{ number_format($exchangePaymentsUsd, 2) }}
                        @else
                            {{ number_format($exchangePaymentsVnd, 0, ',', '.') }}đ
                        @endif
                        cho sản phẩm mới.
                        <br>Còn lại: 
                        @php
                            $remainingUsd = $exchangeAmountUsd - $exchangePaymentsUsd;
                            $remainingVnd = $exchangeAmountVnd - $exchangePaymentsVnd;
                        @endphp
                        @if($remainingUsd > 0 && $remainingVnd > 0)
                            <strong>${{ number_format($remainingUsd, 2) }}</strong> + <strong>{{ number_format($remainingVnd, 0, ',', '.') }}đ</strong>
                        @elseif($remainingUsd > 0)
                            <strong>${{ number_format($remainingUsd, 2) }}</strong>
                        @elseif($remainingVnd > 0)
                            <strong>{{ number_format($remainingVnd, 0, ',', '.') }}đ</strong>
                        @else
                            <strong>$0.00</strong>
                        @endif
                        @endif
                        <br>Số tiền sẽ được cập nhật vào phiếu bán hàng khi phiếu đổi hàng được <strong>hoàn thành</strong>.
                    </p>
                </div>
            </div>
        </div>
        @elseif($return->status == 'completed' && ($exchangePaymentsUsd > 0 || $exchangePaymentsVnd > 0))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-check-double text-green-600 mr-2"></i>
                <div>
                    <h4 class="font-semibold text-sm text-green-800">Đã hoàn thành thanh toán</h4>
                    <p class="text-xs text-green-700 mt-1">
                        Số tiền 
                        @if($exchangePaymentsUsd > 0 && $exchangePaymentsVnd > 0)
                            <strong>${{ number_format($exchangePaymentsUsd, 2) }}</strong> + <strong>{{ number_format($exchangePaymentsVnd, 0, ',', '.') }}đ</strong>
                        @elseif($exchangePaymentsUsd > 0)
                            <strong>${{ number_format($exchangePaymentsUsd, 2) }}</strong>
                        @else
                            <strong>{{ number_format($exchangePaymentsVnd, 0, ',', '.') }}đ</strong>
                        @endif
                        đã được cập nhật vào phiếu bán hàng.
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
                                <td class="px-1 py-1.5 text-center text-xs font-medium">{{ $item->quantity }}</td>
                                <td class="px-1 py-1.5 text-right text-xs whitespace-nowrap">
                                    @php
                                        $currency = $item->saleItem->currency ?? 'VND';
                                        $unitPriceUsd = $item->unit_price_usd ?? 0;
                                        $unitPriceVnd = $item->unit_price ?? 0;
                                    @endphp
                                    @if($currency === 'USD')
                                        <div class="font-bold">${{ number_format($unitPriceUsd, 2) }}</div>
                                    @else
                                        <div class="font-bold">{{ number_format($unitPriceVnd, 0, ',', '.') }}đ</div>
                                    @endif
                                </td>
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
                                <td class="px-1 py-1.5 text-right text-xs font-semibold text-red-600 whitespace-nowrap">
                                    @php
                                        $currency = $item->saleItem->currency ?? 'VND';
                                        $subtotalUsd = $item->subtotal_usd ?? 0;
                                        $subtotalVnd = $item->subtotal ?? 0;
                                    @endphp
                                    @if($currency === 'USD')
                                        <div class="font-bold">${{ number_format($subtotalUsd, 2) }}</div>
                                    @else
                                        <div class="font-bold">{{ number_format($subtotalVnd, 0, ',', '.') }}đ</div>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-2 pt-2 border-t">
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-600">Giá gốc SP trả:</span>
                        <div class="text-right">
                            @php
                                // Tính tổng giá trị hàng trả (giá gốc)
                                $totalValueUsd = 0;
                                $totalValueVnd = 0;
                                
                                foreach ($return->items as $retItem) {
                                    $itemCurrency = $retItem->saleItem->currency ?? 'VND';
                                    if ($itemCurrency === 'USD') {
                                        $totalValueUsd += $retItem->subtotal_usd ?? 0;
                                    } else {
                                        $totalValueVnd += $retItem->subtotal ?? 0;
                                    }
                                }
                            @endphp
                            @if($totalValueUsd > 0 && $totalValueVnd == 0)
                                <div class="text-blue-600 font-bold">${{ number_format($totalValueUsd, 2) }}</div>
                            @elseif($totalValueVnd > 0 && $totalValueUsd == 0)
                                <div class="text-blue-600 font-bold">{{ number_format($totalValueVnd, 0, ',', '.') }}đ</div>
                            @else
                                @if($totalValueUsd > 0)
                                    <div class="text-blue-600 font-bold">${{ number_format($totalValueUsd, 2) }}</div>
                                @endif
                                @if($totalValueVnd > 0)
                                    <div class="text-blue-600 font-bold">{{ number_format($totalValueVnd, 0, ',', '.') }}đ</div>
                                @endif
                            @endif
                        </div>
                    </div>
                    
                    @if($return->type == 'return')
                    @php
                        // Lấy số tiền thực tế hoàn lại (đã tính theo tỷ lệ đã trả)
                        $actualRefundUsd = $return->total_refund_usd ?? 0;
                        $actualRefundVnd = $return->total_refund ?? 0;
                    @endphp
                    @if($actualRefundUsd > 0 || $actualRefundVnd > 0)
                    <div class="flex justify-between text-sm font-bold mt-2 pt-2 border-t border-green-200">
                        <span class="text-green-700">Hoàn lại khách:</span>
                        <div class="text-right">
                            @if($actualRefundUsd > 0 && $actualRefundVnd == 0)
                                <div class="text-green-600 font-bold text-base">${{ number_format($actualRefundUsd, 2) }}</div>
                            @elseif($actualRefundVnd > 0 && $actualRefundUsd == 0)
                                <div class="text-green-600 font-bold text-base">{{ number_format($actualRefundVnd, 0, ',', '.') }}đ</div>
                            @else
                                @if($actualRefundUsd > 0)
                                    <div class="text-green-600 font-bold text-base">${{ number_format($actualRefundUsd, 2) }}</div>
                                @endif
                                @if($actualRefundVnd > 0)
                                    <div class="text-green-600 font-bold text-base">{{ number_format($actualRefundVnd, 0, ',', '.') }}đ</div>
                                @endif
                            @endif
                            <div class="text-xs text-gray-500 mt-1">(Theo tỷ lệ đã thanh toán)</div>
                        </div>
                    </div>
                    @endif
                    @endif
                    
                    @if($return->type == 'exchange')
                    @php
                        // Tính số tiền đã trả cho SP cũ (theo tỷ lệ)
                        $sale = $return->sale;
                        
                        // Lấy số tiền đã trả TRƯỚC KHI đổi hàng (không bao gồm exchange_payment)
                        $initialPaidUsd = $sale->payments()
                            ->where('transaction_type', '!=', 'exchange_payment')
                            ->sum('payment_usd');
                        $initialPaidVnd = $sale->payments()
                            ->where('transaction_type', '!=', 'exchange_payment')
                            ->sum('payment_vnd');
                        
                        // Tính tỷ lệ đã trả (riêng USD và VND) - dựa trên số tiền trả ban đầu
                        $originalTotalUsd = $sale->original_total_usd ?? $sale->total_usd;
                        $originalTotalVnd = $sale->original_total_vnd ?? $sale->total_vnd;
                        
                        $paidRatioUsd = $originalTotalUsd > 0 ? ($initialPaidUsd / $originalTotalUsd) : 0;
                        $paidRatioVnd = $originalTotalVnd > 0 ? ($initialPaidVnd / $originalTotalVnd) : 0;
                        
                        // Số tiền đã trả cho SP cũ = Giá gốc SP * Tỷ lệ đã trả (TRƯỚC KHI đổi hàng)
                        $paidForReturnedUsd = $totalValueUsd * $paidRatioUsd;
                        $paidForReturnedVnd = $totalValueVnd * $paidRatioVnd;
                    @endphp
                    @if($paidForReturnedUsd > 0 || $paidForReturnedVnd > 0)
                    <div class="flex justify-between text-sm font-bold mt-2 pt-2 border-t border-green-200">
                        <span class="text-green-700">Đã trả cho SP cũ:</span>
                        <div class="text-right">
                            @if($paidForReturnedUsd > 0 && $paidForReturnedVnd == 0)
                                <div class="text-green-600 font-bold text-base">${{ number_format($paidForReturnedUsd, 2) }}</div>
                            @elseif($paidForReturnedVnd > 0 && $paidForReturnedUsd == 0)
                                <div class="text-green-600 font-bold text-base">{{ number_format($paidForReturnedVnd, 0, ',', '.') }}đ</div>
                            @else
                                @if($paidForReturnedUsd > 0)
                                    <div class="text-green-600 font-bold text-base">${{ number_format($paidForReturnedUsd, 2) }}</div>
                                @endif
                                @if($paidForReturnedVnd > 0)
                                    <div class="text-green-600 font-bold text-base">{{ number_format($paidForReturnedVnd, 0, ',', '.') }}đ</div>
                                @endif
                            @endif
                            <div class="text-xs text-gray-500 mt-1">({{ number_format($paidRatioUsd * 100, 1) }}% đã thanh toán)</div>
                        </div>
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
                                <td class="px-1 py-1.5 text-center text-xs font-medium">{{ $item->quantity }}</td>
                                <td class="px-1 py-1.5 text-right text-xs whitespace-nowrap">
                                    @php
                                        // Lấy giá từ exchange item, đúng currency
                                        $currency = $item->currency ?? 'VND';
                                        $unitPriceUsd = $item->unit_price_usd ?? 0;
                                        $unitPriceVnd = $item->unit_price ?? 0;
                                    @endphp
                                    @if($currency === 'USD')
                                        <div class="font-bold">${{ number_format($unitPriceUsd, 2) }}</div>
                                    @else
                                        <div class="font-bold">{{ number_format($unitPriceVnd, 0, ',', '.') }}đ</div>
                                    @endif
                                </td>
                                <td class="px-1 py-1.5 text-center text-xs">
                                    @if($item->discount_percent > 0)
                                        <span class="text-red-600">{{ $item->discount_percent }}%</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-1 py-1.5 text-right text-xs font-semibold text-green-600 whitespace-nowrap">
                                    @php
                                        // Lấy subtotal từ exchange item
                                        $currency = $item->currency ?? 'VND';
                                        $subtotalUsd = $item->subtotal_usd ?? 0;
                                        $subtotalVnd = $item->subtotal ?? 0;
                                    @endphp
                                    @if($currency === 'USD')
                                        <div class="font-bold">${{ number_format($subtotalUsd, 2) }}</div>
                                    @else
                                        <div class="font-bold">{{ number_format($subtotalVnd, 0, ',', '.') }}đ</div>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-2 pt-2 border-t">
                    <div class="flex justify-between text-xs font-semibold">
                        <span>Tổng đổi:</span>
                        <div class="text-right">
                            @php
                                // Tính tổng theo từng loại tiền, không quy đổi
                                $totalExchangeUsd = 0;
                                $totalExchangeVnd = 0;
                                
                                foreach ($return->exchangeItems as $exItem) {
                                    $exCurrency = $exItem->currency ?? 'VND';
                                    
                                    if ($exCurrency === 'USD') {
                                        $totalExchangeUsd += $exItem->subtotal_usd ?? 0;
                                    } else {
                                        $totalExchangeVnd += $exItem->subtotal ?? 0;
                                    }
                                }
                            @endphp
                            @if($totalExchangeUsd > 0 && $totalExchangeVnd == 0)
                                <div class="text-green-600 font-bold">${{ number_format($totalExchangeUsd, 2) }}</div>
                            @elseif($totalExchangeVnd > 0 && $totalExchangeUsd == 0)
                                <div class="text-green-600 font-bold">{{ number_format($totalExchangeVnd, 0, ',', '.') }}đ</div>
                            @else
                                @if($totalExchangeUsd > 0)
                                    <div class="text-green-600 font-bold">${{ number_format($totalExchangeUsd, 2) }}</div>
                                @endif
                                @if($totalExchangeVnd > 0)
                                    <div class="text-green-600 font-bold">{{ number_format($totalExchangeVnd, 0, ',', '.') }}đ</div>
                                @endif
                            @endif
                        </div>
                    </div>
                    @php
                        // Tính số tiền đã trả cho phiếu đổi hàng này (RIÊNG USD và VND)
                        $paidUsdItems = $return->sale->payments()
                            ->where('transaction_type', 'exchange_payment')
                            ->where('notes', 'like', "%{$return->return_code}%")
                            ->sum('payment_usd');
                            
                        $paidVndItems = $return->sale->payments()
                            ->where('transaction_type', 'exchange_payment')
                            ->where('notes', 'like', "%{$return->return_code}%")
                            ->sum('payment_vnd');
                    @endphp
                    @if($paidUsdItems > 0 || $paidVndItems > 0)
                    <div class="flex justify-between text-xs mt-1">
                        <span class="text-gray-600">Đã trả:</span>
                        <div class="text-right">
                            @if($paidUsdItems > 0 && $paidVndItems > 0)
                                <span class="font-semibold text-green-600">${{ number_format($paidUsdItems, 2) }} + {{ number_format($paidVndItems, 0, ',', '.') }}đ</span>
                            @elseif($paidUsdItems > 0)
                                <span class="font-semibold text-green-600">${{ number_format($paidUsdItems, 2) }}</span>
                            @else
                                <span class="font-semibold text-green-600">{{ number_format($paidVndItems, 0, ',', '.') }}đ</span>
                            @endif
                        </div>
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
        @if($return->exchange_amount != 0 || $return->exchange_amount_usd != 0 || $return->total_refund != 0 || $return->total_refund_usd != 0)
        <div class="mt-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
            <div class="flex justify-between items-center">
                @php
                    $exchangeAmountUsd = $return->exchange_amount_usd ?? 0;
                    $exchangeAmountVnd = $return->exchange_amount ?? 0;
                    
                    $totalRefundUsd = $return->total_refund_usd ?? 0;
                    $totalRefundVnd = $return->total_refund ?? 0;
                    
                    // Xác định xem có phải khách trả thêm hay hoàn lại (xử lý mixed currency)
                    $hasExchangeUsd = $exchangeAmountUsd > 0;
                    $hasExchangeVnd = $exchangeAmountVnd > 0;
                    $hasRefundUsd = $totalRefundUsd > 0;
                    $hasRefundVnd = $totalRefundVnd > 0;
                    
                    // Trường hợp mixed: có cả trả thêm và hoàn lại
                    $isMixed = ($hasExchangeUsd || $hasExchangeVnd) && ($hasRefundUsd || $hasRefundVnd);
                @endphp
                
                @if($isMixed)
                    {{-- Mixed: Hiển thị cả 2 --}}
                    <div class="space-y-2">
                        @if($hasExchangeUsd || $hasExchangeVnd)
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-sm">Khách trả thêm:</span>
                            <span class="font-bold text-base">
                                @if($hasExchangeUsd)
                                    <span class="text-red-600">+${{ number_format($exchangeAmountUsd, 2) }}</span>
                                @endif
                                @if($hasExchangeVnd)
                                    <span class="text-red-600">+{{ number_format($exchangeAmountVnd, 0, ',', '.') }}đ</span>
                                @endif
                            </span>
                        </div>
                        @endif
                        @if($hasRefundUsd || $hasRefundVnd)
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-sm">Hoàn lại khách:</span>
                            <span class="font-bold text-base">
                                @if($hasRefundUsd)
                                    <span class="text-green-600">${{ number_format($totalRefundUsd, 2) }}</span>
                                @endif
                                @if($hasRefundVnd)
                                    <span class="text-green-600">{{ number_format($totalRefundVnd, 0, ',', '.') }}đ</span>
                                @endif
                            </span>
                        </div>
                        @endif
                    </div>
                @elseif($hasExchangeUsd || $hasExchangeVnd)
                    <span class="font-semibold text-sm">Khách trả thêm:</span>
                    <span class="font-bold text-base">
                        @if($hasExchangeUsd && !$hasExchangeVnd)
                            <span class="text-red-600">+${{ number_format($exchangeAmountUsd, 2) }}</span>
                        @elseif($hasExchangeVnd && !$hasExchangeUsd)
                            <span class="text-red-600">+{{ number_format($exchangeAmountVnd, 0, ',', '.') }}đ</span>
                        @else
                            @if($hasExchangeUsd)
                                <span class="text-red-600">+${{ number_format($exchangeAmountUsd, 2) }}</span>
                            @endif
                            @if($hasExchangeVnd)
                                <span class="text-red-600">+{{ number_format($exchangeAmountVnd, 0, ',', '.') }}đ</span>
                            @endif
                        @endif
                    </span>
                @elseif($hasRefundUsd || $hasRefundVnd)
                    <span class="font-semibold text-sm">Hoàn lại khách:</span>
                    <span class="font-bold text-base">
                        @if($hasRefundUsd && !$hasRefundVnd)
                            <span class="text-green-600">${{ number_format($totalRefundUsd, 2) }}</span>
                        @elseif($hasRefundVnd && !$hasRefundUsd)
                            <span class="text-green-600">{{ number_format($totalRefundVnd, 0, ',', '.') }}đ</span>
                        @else
                            @if($hasRefundUsd)
                                <span class="text-green-600">${{ number_format($totalRefundUsd, 2) }}</span>
                            @endif
                            @if($hasRefundVnd)
                                <span class="text-green-600">{{ number_format($totalRefundVnd, 0, ',', '.') }}đ</span>
                            @endif
                        @endif
                    </span>
                @else
                    <span class="font-semibold text-sm">Chênh lệch:</span>
                    <span class="font-bold text-base text-gray-600">Ngang giá</span>
                @endif
            </div>
            
            @if($exchangeAmountUsd > 0 || $exchangeAmountVnd > 0)
            @php
                // Tính số tiền đã trả cho phiếu đổi hàng này (RIÊNG USD và VND)
                $paidUsdForExchange = $return->sale->payments()
                    ->where('transaction_type', 'exchange_payment')
                    ->where('notes', 'like', "%{$return->return_code}%")
                    ->sum('payment_usd');
                    
                $paidVndForExchange = $return->sale->payments()
                    ->where('transaction_type', 'exchange_payment')
                    ->where('notes', 'like', "%{$return->return_code}%")
                    ->sum('payment_vnd');
                
                // Lấy tỷ giá từ payment hoặc sale
                $paymentExchangeRate = $return->sale->payments()
                    ->where('transaction_type', 'exchange_payment')
                    ->where('notes', 'like', "%{$return->return_code}%")
                    ->value('payment_exchange_rate') ?? $exchangeRate;
                
                // Lưu số tiền gốc trước khi quy đổi (để hiển thị)
                $originalPaidUsd = $paidUsdForExchange;
                $originalPaidVnd = $paidVndForExchange;
                $isCrossPayment = false;
                $convertedAmount = 0;
                $crossPaymentType = null; // 'usd_to_vnd' hoặc 'vnd_to_usd'
                
                // Lấy thông tin original từ Payment notes (format: [ORIGINAL:usd,vnd])
                $paymentNotes = $return->sale->payments()
                    ->where('transaction_type', 'exchange_payment')
                    ->where('notes', 'like', "%{$return->return_code}%")
                    ->value('notes') ?? '';
                
                $originalFromPayment = null;
                if (preg_match('/\[ORIGINAL:([\d.]+),([\d.]+)\]/', $paymentNotes, $matches)) {
                    $originalFromPayment = [
                        'usd' => (float)$matches[1],
                        'vnd' => (float)$matches[2]
                    ];
                }
                
                // Xử lý thanh toán chéo
                // Case 1: Nợ USD, có original từ Payment notes
                if ($exchangeAmountUsd > 0 && $exchangeAmountVnd == 0 && $paidUsdForExchange > 0 && $originalFromPayment) {
                    if ($originalFromPayment['vnd'] > 0 && $originalFromPayment['usd'] == 0) {
                        // Thanh toán chéo: trả VND, đã quy đổi sang USD
                        $originalPaidVnd = $originalFromPayment['vnd'];
                        $originalPaidUsd = 0;
                        $convertedAmount = $paidUsdForExchange;
                        $isCrossPayment = true;
                        $crossPaymentType = 'vnd_to_usd';
                    }
                }
                // Case 1b: Nợ USD nhưng chỉ có payment VND (phiếu cũ chưa quy đổi)
                elseif ($exchangeAmountUsd > 0 && $exchangeAmountVnd == 0 && $paidUsdForExchange == 0 && $paidVndForExchange > 0 && $paymentExchangeRate > 0) {
                    $convertedAmount = $paidVndForExchange / $paymentExchangeRate;
                    $paidUsdForExchange = $convertedAmount;
                    $isCrossPayment = true;
                    $crossPaymentType = 'vnd_to_usd';
                }
                // Case 2: Nợ VND, có original từ Payment notes
                elseif ($exchangeAmountVnd > 0 && $exchangeAmountUsd == 0 && $paidVndForExchange > 0 && $originalFromPayment) {
                    if ($originalFromPayment['usd'] > 0 && $originalFromPayment['vnd'] == 0) {
                        // Thanh toán chéo: trả USD, đã quy đổi sang VND
                        $originalPaidUsd = $originalFromPayment['usd'];
                        $originalPaidVnd = 0;
                        $convertedAmount = $paidVndForExchange;
                        $isCrossPayment = true;
                        $crossPaymentType = 'usd_to_vnd';
                    }
                }
                // Case 2b: Nợ VND, phiếu cũ (Sale VND không có exchange_rate)
                elseif ($exchangeAmountVnd > 0 && $exchangeAmountUsd == 0 && $paidVndForExchange > 0 && $paidUsdForExchange == 0) {
                    $saleExchangeRate = $return->sale->exchange_rate ?? 0;
                    if ($paymentExchangeRate > 0 && $saleExchangeRate == 0) {
                        $originalPaidUsd = $paidVndForExchange / $paymentExchangeRate;
                        $originalPaidVnd = 0;
                        $convertedAmount = $paidVndForExchange;
                        $isCrossPayment = true;
                        $crossPaymentType = 'usd_to_vnd';
                    }
                }
                // Case 2c: Nợ VND nhưng chỉ có payment USD (phiếu cũ chưa quy đổi)
                elseif ($exchangeAmountVnd > 0 && $exchangeAmountUsd == 0 && $paidVndForExchange == 0 && $paidUsdForExchange > 0 && $paymentExchangeRate > 0) {
                    $convertedAmount = $paidUsdForExchange * $paymentExchangeRate;
                    $paidVndForExchange = $convertedAmount;
                    $isCrossPayment = true;
                    $crossPaymentType = 'usd_to_vnd';
                }
                
                // Tính còn nợ (riêng USD và VND)
                $remainingDebtUsd = max(0, $exchangeAmountUsd - $paidUsdForExchange);
                $remainingDebtVnd = max(0, $exchangeAmountVnd - $paidVndForExchange);
                
                $hasPaidSomething = $originalPaidUsd > 0 || $originalPaidVnd > 0;
            @endphp
            
            @if($hasPaidSomething)
            <div class="mt-2 pt-2 border-t border-blue-300">
                <div class="flex justify-between text-xs">
                    <span class="text-gray-600">Đã trả:</span>
                    <div class="text-right">
                        @if($isCrossPayment && $convertedAmount > 0)
                            {{-- Thanh toán chéo: Hiển thị số tiền gốc trước, quy đổi ở dưới --}}
                            @if($crossPaymentType == 'vnd_to_usd')
                                {{-- Nợ USD, trả VND → Hiển thị VND trước, USD quy đổi ở dưới --}}
                                <span class="font-semibold text-green-600">{{ number_format($originalPaidVnd, 0, ',', '.') }}đ</span>
                                <div class="text-[10px] text-gray-500">≈ ${{ number_format($convertedAmount, 2) }}</div>
                            @elseif($crossPaymentType == 'usd_to_vnd')
                                {{-- Nợ VND, trả USD → Hiển thị USD trước, VND quy đổi ở dưới --}}
                                <span class="font-semibold text-green-600">${{ number_format($originalPaidUsd, 2) }}</span>
                                <div class="text-[10px] text-gray-500">≈ {{ number_format($convertedAmount, 0, ',', '.') }}đ</div>
                            @endif
                        @else
                            {{-- Thanh toán song song: Hiển thị số tiền gốc --}}
                            @if($originalPaidUsd > 0 && $originalPaidVnd > 0)
                                <span class="font-semibold text-green-600">${{ number_format($originalPaidUsd, 2) }} + {{ number_format($originalPaidVnd, 0, ',', '.') }}đ</span>
                            @elseif($originalPaidUsd > 0)
                                <span class="font-semibold text-green-600">${{ number_format($originalPaidUsd, 2) }}</span>
                            @else
                                <span class="font-semibold text-green-600">{{ number_format($originalPaidVnd, 0, ',', '.') }}đ</span>
                            @endif
                        @endif
                    </div>
                </div>
                @if($remainingDebtUsd > 0 || $remainingDebtVnd > 0)
                <div class="flex justify-between text-xs mt-1">
                    <span class="text-gray-600">Còn nợ:</span>
                    <span class="font-semibold text-red-600">
                        @if($remainingDebtUsd > 0 && $remainingDebtVnd > 0)
                            ${{ number_format($remainingDebtUsd, 2) }} + {{ number_format($remainingDebtVnd, 0, ',', '.') }}đ
                        @elseif($remainingDebtUsd > 0)
                            ${{ number_format($remainingDebtUsd, 2) }}
                        @else
                            {{ number_format($remainingDebtVnd, 0, ',', '.') }}đ
                        @endif
                    </span>
                </div>
                @endif
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
                        <td class="px-2 py-2 text-xs text-right whitespace-nowrap">
                            @php
                                // Lấy giá từ return item, không quy đổi
                                $currency = $item->saleItem->currency ?? 'VND';
                                $unitPriceUsd = $item->unit_price_usd ?? 0;
                                $unitPriceVnd = $item->unit_price ?? 0;
                            @endphp
                            @if($currency === 'USD')
                                <div class="font-bold">${{ number_format($unitPriceUsd, 2) }}</div>
                            @else
                                <div class="font-bold">{{ number_format($unitPriceVnd, 0, ',', '.') }}đ</div>
                            @endif
                        </td>
                        <td class="px-2 py-2 text-xs text-right font-medium whitespace-nowrap">
                            @php
                                // Lấy subtotal từ return item, không quy đổi
                                $currency = $item->saleItem->currency ?? 'VND';
                                $subtotalUsd = $item->subtotal_usd ?? 0;
                                $subtotalVnd = $item->subtotal ?? 0;
                            @endphp
                            @if($currency === 'USD')
                                <div class="font-bold">${{ number_format($subtotalUsd, 2) }}</div>
                            @else
                                <div class="font-bold">{{ number_format($subtotalVnd, 0, ',', '.') }}đ</div>
                            @endif
                        </td>
                        <td class="px-2 py-2 text-xs text-gray-600 truncate max-w-[100px]">{{ $item->reason ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="3" class="px-2 py-2 text-right text-xs font-semibold">Tổng:</td>
                        <td class="px-2 py-2 text-right text-xs font-semibold">{{ $return->items->sum('quantity') }}</td>
                        <td colspan="3" class="px-2 py-2 text-right text-sm font-semibold whitespace-nowrap">
                            <div class="mb-1">
                                <div class="text-xs text-gray-600 mb-1">Tổng giá trị hàng trả:</div>
                                @php
                                    // Tính tổng giá trị hàng trả
                                    $totalValueUsd = 0;
                                    $totalValueVnd = 0;
                                    
                                    foreach ($return->items as $retItem) {
                                        $itemCurrency = $retItem->saleItem->currency ?? 'VND';
                                        if ($itemCurrency === 'USD') {
                                            $totalValueUsd += $retItem->subtotal_usd ?? 0;
                                        } else {
                                            $totalValueVnd += $retItem->subtotal ?? 0;
                                        }
                                    }
                                @endphp
                                @if($totalValueUsd > 0 && $totalValueVnd == 0)
                                    <div class="text-blue-600 font-bold">${{ number_format($totalValueUsd, 2) }}</div>
                                @elseif($totalValueVnd > 0 && $totalValueUsd == 0)
                                    <div class="text-blue-600 font-bold">{{ number_format($totalValueVnd, 0, ',', '.') }}đ</div>
                                @else
                                    @if($totalValueUsd > 0)
                                        <div class="text-blue-600 font-bold">${{ number_format($totalValueUsd, 2) }}</div>
                                    @endif
                                    @if($totalValueVnd > 0)
                                        <div class="text-blue-600 font-bold">{{ number_format($totalValueVnd, 0, ',', '.') }}đ</div>
                                    @endif
                                @endif
                            </div>
                            
                            @php
                                // Lấy tiền hoàn lại thực tế
                                $actualRefundUsd = $return->total_refund_usd ?? 0;
                                $actualRefundVnd = $return->total_refund ?? 0;
                            @endphp
                            @if($actualRefundUsd > 0 || $actualRefundVnd > 0)
                            <div class="pt-2 border-t border-green-200">
                                <div class="text-xs text-green-700 mb-1">Hoàn lại khách:</div>
                                @if($actualRefundUsd > 0 && $actualRefundVnd == 0)
                                    <div class="text-green-600 font-bold text-base">${{ number_format($actualRefundUsd, 2) }}</div>
                                @elseif($actualRefundVnd > 0 && $actualRefundUsd == 0)
                                    <div class="text-green-600 font-bold text-base">{{ number_format($actualRefundVnd, 0, ',', '.') }}đ</div>
                                @else
                                    @if($actualRefundUsd > 0)
                                        <div class="text-green-600 font-bold text-base">${{ number_format($actualRefundUsd, 2) }}</div>
                                    @endif
                                    @if($actualRefundVnd > 0)
                                        <div class="text-green-600 font-bold text-base">{{ number_format($actualRefundVnd, 0, ',', '.') }}đ</div>
                                    @endif
                                @endif
                                <div class="text-[10px] text-gray-500 mt-1">(Theo tỷ lệ đã thanh toán)</div>
                            </div>
                            @endif
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
                            // Lấy số tiền gốc (trước quy đổi) nếu có
                            $origUsd = $paymentInfo['original_payment_usd'] ?? $paymentInfo['payment_usd'] ?? 0;
                            $origVnd = $paymentInfo['original_payment_vnd'] ?? $paymentInfo['payment_vnd'] ?? 0;
                            $finalUsd = $paymentInfo['payment_usd'] ?? 0;
                            $finalVnd = $paymentInfo['payment_vnd'] ?? 0;
                            
                            $displayNotes = 'Khách hàng đã trả: ';
                            
                            // Hiển thị số tiền gốc
                            if ($origUsd > 0 && $origVnd > 0) {
                                $displayNotes .= '$' . number_format($origUsd, 2) . ' + ' . number_format($origVnd, 0, ',', '.') . 'đ';
                            } elseif ($origUsd > 0) {
                                $displayNotes .= '$' . number_format($origUsd, 2);
                            } elseif ($origVnd > 0) {
                                $displayNotes .= number_format($origVnd, 0, ',', '.') . 'đ';
                            }
                            
                            // Nếu có quy đổi, hiển thị thêm
                            if (($origUsd != $finalUsd || $origVnd != $finalVnd) && ($finalUsd > 0 || $finalVnd > 0)) {
                                $displayNotes .= ' (quy đổi: ';
                                if ($finalUsd > 0) {
                                    $displayNotes .= '$' . number_format($finalUsd, 2);
                                }
                                if ($finalVnd > 0) {
                                    $displayNotes .= ($finalUsd > 0 ? ' + ' : '') . number_format($finalVnd, 0, ',', '.') . 'đ';
                                }
                                $displayNotes .= ')';
                            }
                            
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
