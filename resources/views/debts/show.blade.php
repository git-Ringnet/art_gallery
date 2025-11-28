@extends('layouts.app')

@section('title', 'Chi tiết Công nợ')
@section('page-title', 'Chi tiết Công nợ')
@section('page-description', 'Thông tin chi tiết và lịch sử thanh toán')

@section('header-actions')
<div class="flex flex-wrap gap-2">
    <a href="{{ route('debt.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1.5 rounded-lg transition-colors text-sm whitespace-nowrap">
        <i class="fas fa-arrow-left mr-1"></i>Quay lại
    </a>
    @if($debt->sale->payment_status !== 'paid')
    <button onclick="showCollectModal()" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-lg transition-colors text-sm whitespace-nowrap">
        Thanh toán
    </button>
    @endif
</div>
@endsection

@section('content')
<x-alert />

@push('scripts')
<script src="{{ asset('js/number-format.js') }}"></script>
@endpush

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Debt Info -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-lg p-4 fade-in mb-4">
            <h3 class="text-base font-bold text-gray-800 mb-3 flex items-center">
                <i class="fas fa-file-invoice text-blue-500 mr-2"></i>
                Thông tin công nợ
            </h3>
            
            <div class="space-y-3">
                <div>
                    <label class="text-xs text-gray-500 font-medium">Mã hóa đơn</label>
                    <p class="font-bold text-blue-600 text-base">{{ $debt->sale->invoice_code }}</p>
                </div>
                
                <div>
                    <label class="text-xs text-gray-500 font-medium">Tên khách hàng</label>
                    <p class="font-medium text-gray-900 text-sm">{{ $debt->customer->name }}</p>
                </div>
                
                <div>
                    <label class="text-xs text-gray-500 font-medium">Số điện thoại</label>
                    <p class="font-medium text-gray-900 text-sm">
                        <i class="fas fa-phone text-blue-500 mr-1"></i>{{ $debt->customer->phone ?? '-' }}
                    </p>
                </div>
                
                <div>
                    <label class="text-xs text-gray-500 font-medium">Ngày mua hàng</label>
                    <p class="font-medium text-gray-900 text-sm">{{ $debt->sale->sale_date->format('d/m/Y') }}</p>
                </div>
                
                <div>
                    <label class="text-xs text-gray-500 font-medium">Hạn trả tiền</label>
                    <p class="font-medium {{ $debt->isOverdue() ? 'text-red-600' : 'text-gray-900' }} text-sm">
                        {{ $debt->due_date ? $debt->due_date->format('d/m/Y') : '-' }}
                        @if($debt->isOverdue())
                            <span class="text-xs ml-1">(Quá hạn)</span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- Amount Summary - LOGIC MỚI -->
            <div class="mt-4 pt-4 border-t-2 space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 font-medium text-sm">Tổng tiền HĐ:</span>
                    <div class="text-right">
                        @if($debt->sale->total_usd > 0 && $debt->sale->total_vnd > 0)
                            {{-- Mixed: Hiển thị cả USD và VND --}}
                            <div class="font-bold text-gray-900 text-base">${{ number_format($debt->sale->total_usd, 2) }} + {{ number_format($debt->sale->total_vnd, 0, ',', '.') }}đ</div>
                        @elseif($debt->sale->total_usd > 0)
                            {{-- USD only --}}
                            <div class="font-bold text-gray-900 text-base">${{ number_format($debt->sale->total_usd, 2) }}</div>
                        @else
                            {{-- VND only --}}
                            <div class="font-bold text-gray-900 text-base">{{ number_format($debt->sale->total_vnd, 0, ',', '.') }}đ</div>
                        @endif
                    </div>
                </div>
                <div class="flex justify-between items-center bg-green-50 p-2 rounded-lg">
                    <span class="text-green-700 font-medium text-sm">Đã trả:</span>
                    <div class="text-right">
                        @if($debt->sale->total_usd > 0 && $debt->sale->total_vnd > 0)
                            {{-- Mixed: Hiển thị cả USD và VND --}}
                            <div class="font-bold text-green-700 text-base">${{ number_format($debt->sale->paid_usd, 2) }} + {{ number_format($debt->sale->paid_vnd, 0, ',', '.') }}đ</div>
                        @elseif($debt->sale->total_usd > 0)
                            {{-- USD only --}}
                            <div class="font-bold text-green-700 text-base">${{ number_format($debt->sale->paid_usd, 2) }}</div>
                        @else
                            {{-- VND only --}}
                            <div class="font-bold text-green-700 text-base">{{ number_format($debt->sale->paid_vnd, 0, ',', '.') }}đ</div>
                        @endif
                    </div>
                </div>
                <div class="flex justify-between items-center pt-2 border-t-2 bg-red-50 p-3 rounded-lg border-2 border-red-200">
                    <span class="text-red-700 font-bold text-base">Còn nợ:</span>
                    <div class="text-right">
                        @if($debt->sale->total_usd > 0 && $debt->sale->total_vnd > 0)
                            {{-- Mixed: Hiển thị cả USD và VND --}}
                            <div class="font-bold text-red-600 text-xl">${{ number_format($debt->sale->debt_usd, 2) }} + {{ number_format($debt->sale->debt_vnd, 0, ',', '.') }}đ</div>
                        @elseif($debt->sale->total_usd > 0)
                            {{-- USD only --}}
                            <div class="font-bold text-red-600 text-xl">${{ number_format($debt->sale->debt_usd, 2) }}</div>
                        @else
                            {{-- VND only --}}
                            <div class="font-bold text-red-600 text-xl">{{ number_format($debt->sale->debt_vnd, 0, ',', '.') }}đ</div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Status Badge -->
            <div class="mt-6">
                @if($debt->status === 'cancelled')
                    <div class="bg-gray-100 text-gray-800 px-4 py-2 rounded-lg text-center font-medium">
                        <i class="fas fa-ban mr-2"></i>Đã hủy (Trả hàng)
                    </div>
                @elseif($debt->status === 'paid')
                    <div class="bg-green-100 text-green-800 px-4 py-2 rounded-lg text-center font-medium">
                        <i class="fas fa-check-circle mr-2"></i>Đã thanh toán đầy đủ
                    </div>
                @elseif($debt->isOverdue())
                    <div class="bg-red-100 text-red-800 px-4 py-2 rounded-lg text-center font-medium">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Quá hạn thanh toán
                    </div>
                @elseif($debt->status === 'partial')
                    <div class="bg-yellow-100 text-yellow-800 px-4 py-2 rounded-lg text-center font-medium">
                        <i class="fas fa-clock mr-2"></i>Đã trả một phần
                    </div>
                @else
                    <div class="bg-gray-100 text-gray-800 px-4 py-2 rounded-lg text-center font-medium">
                        <i class="fas fa-hourglass-half mr-2"></i>Chưa thanh toán
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-lg p-4 fade-in">
            <h3 class="text-base font-bold text-gray-800 mb-3">Thao tác nhanh</h3>
            <div class="space-y-2">
                <a href="{{ route('sales.show', $debt->sale_id) }}" class="block w-full bg-blue-100 text-blue-700 px-3 py-2 rounded-lg hover:bg-blue-200 transition-colors text-center text-sm">
                    <i class="fas fa-file-invoice mr-1"></i>Xem hóa đơn
                </a>
                <a href="{{ route('customers.show', $debt->customer_id) }}" class="block w-full bg-purple-100 text-purple-700 px-3 py-2 rounded-lg hover:bg-purple-200 transition-colors text-center text-sm">
                    <i class="fas fa-user mr-1"></i>Xem khách hàng
                </a>
            </div>
        </div>
    </div>

    <!-- Payment History -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-lg p-4 fade-in">
            <h3 class="text-base font-bold text-gray-800 mb-3 flex items-center">
                <i class="fas fa-receipt text-blue-500 mr-2"></i>
                Các lần khách đã trả tiền
            </h3>
            <p class="text-xs text-gray-500 mb-3 italic">Danh sách các lần khách hàng đã thanh toán cho hóa đơn này</p>

            @if($debt->sale->payments->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-700">Ngày trả</th>
                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-700">Số tiền</th>
                            <th class="px-2 py-2 text-center text-xs font-medium text-gray-700">Hình thức</th>
                            <th class="px-2 py-2 text-center text-xs font-medium text-gray-700">Loại GD</th>
                            <th class="px-2 py-2 text-center text-xs font-medium text-gray-700">Người thu</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-700">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($debt->sale->payments as $payment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 py-2 text-xs whitespace-nowrap">
                                @php
                                    $paymentDateTime = $payment->payment_date->timezone('Asia/Ho_Chi_Minh');
                                    $timeStr = $paymentDateTime->format('H:i:s');
                                    // Chỉ hiển thị giờ nếu không phải 00:00:00 hoặc 07:00:00 (data cũ từ UTC)
                                    $hasTime = $timeStr !== '00:00:00' && $timeStr !== '07:00:00';
                                @endphp
                                <div>{{ $paymentDateTime->format('d/m/Y') }}</div>
                                @if($hasTime)
                                    <div class="text-xs text-gray-500">{{ $paymentDateTime->format('H:i') }}</div>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-right text-xs whitespace-nowrap">
                                @php
                                    $hasUsd = $payment->payment_usd > 0;
                                    $hasVnd = $payment->payment_vnd > 0;
                                    $exchangeRate = $payment->payment_exchange_rate ?? $payment->sale->exchange_rate;
                                    // Tránh chia cho 0 với phiếu VND
                                    if ($exchangeRate <= 0) $exchangeRate = 1;
                                    $isRefund = $payment->amount < 0;
                                @endphp

                                @if($isRefund)
                                    <div class="text-red-600 font-medium">
                                        <i class="fas fa-undo mr-1"></i>{{ number_format(abs($payment->amount), 0, ',', '.') }}đ
                                        <span class="text-xs block">(Hoàn tiền)</span>
                                    </div>
                                @elseif($hasUsd && !$hasVnd)
                                    {{-- Chỉ trả USD --}}
                                    <div class="font-bold text-blue-600">${{ number_format($payment->payment_usd, 2) }}</div>
                                    @php
                                        // Kiểm tra xem có phải thanh toán chéo không (hóa đơn VND, trả USD)
                                        $isVndInvoice = $payment->sale->saleItems->where('currency', 'VND')->count() > 0;
                                        $isUsdInvoice = $payment->sale->saleItems->where('currency', 'USD')->count() > 0;
                                        $isCrossCurrency = $isVndInvoice && !$isUsdInvoice; // Hóa đơn VND, trả USD
                                    @endphp
                                    @if($isCrossCurrency && $exchangeRate > 1)
                                        <div class="text-xs text-gray-500">≈ {{ number_format($payment->payment_usd * $exchangeRate, 0, ',', '.') }}đ (Tỷ giá: {{ number_format($exchangeRate, 0, ',', '.') }})</div>
                                    @endif
                                @elseif($hasVnd && !$hasUsd)
                                    {{-- Chỉ trả VND --}}
                                    <div class="font-bold text-green-600">{{ number_format($payment->payment_vnd, 0, ',', '.') }}đ</div>
                                    @php
                                        // Kiểm tra xem có phải thanh toán chéo không (hóa đơn USD, trả VND)
                                        $isVndInvoice = $payment->sale->saleItems->where('currency', 'VND')->count() > 0;
                                        $isUsdInvoice = $payment->sale->saleItems->where('currency', 'USD')->count() > 0;
                                        $isCrossCurrency = $isUsdInvoice && !$isVndInvoice; // Hóa đơn USD, trả VND
                                    @endphp
                                    @if($isCrossCurrency && $exchangeRate > 1)
                                        <div class="text-xs text-gray-500">≈ ${{ number_format($payment->payment_vnd / $exchangeRate, 2) }} (Tỷ giá: {{ number_format($exchangeRate, 0, ',', '.') }})</div>
                                    @endif
                                @elseif($hasUsd && $hasVnd)
                                    {{-- Trả cả hai --}}
                                    <div class="font-bold">
                                        <span class="text-blue-600">${{ number_format($payment->payment_usd, 2) }}</span>
                                        <span class="text-gray-400 mx-0.5">+</span>
                                        <span class="text-green-600">{{ number_format($payment->payment_vnd, 0, ',', '.') }}đ</span>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        Tổng: ${{ number_format($payment->payment_usd + ($payment->payment_vnd / $exchangeRate), 2) }} (Tỷ giá: {{ number_format($exchangeRate, 0, ',', '.') }})
                                    </div>
                                @else
                                    {{-- Fallback cho dữ liệu cũ --}}
                                    <div class="font-medium text-green-600">{{ number_format($payment->amount, 0, ',', '.') }}đ</div>
                                    @php
                                        // Kiểm tra xem có phải thanh toán chéo không (hóa đơn USD, trả VND)
                                        $isVndInvoice = $payment->sale->saleItems->where('currency', 'VND')->count() > 0;
                                        $isUsdInvoice = $payment->sale->saleItems->where('currency', 'USD')->count() > 0;
                                        $isCrossCurrency = $isUsdInvoice && !$isVndInvoice; // Hóa đơn USD, trả VND
                                    @endphp
                                    @if($isCrossCurrency && $exchangeRate > 1)
                                        <div class="text-xs text-gray-500">≈ ${{ number_format($payment->amount / $exchangeRate, 2) }} (Tỷ giá: {{ number_format($exchangeRate, 0, ',', '.') }})</div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-2 py-2 text-center">
                                @if($payment->payment_method === 'cash')
                                    <span class="px-1.5 py-0.5 bg-green-100 text-green-800 text-xs rounded-full whitespace-nowrap">
                                        Tiền Mặt
                                    </span>
                                @elseif($payment->payment_method === 'bank_transfer')
                                    <span class="px-1.5 py-0.5 bg-blue-100 text-blue-800 text-xs rounded-full whitespace-nowrap">
                                        C.Khoản
                                    </span>
                                @else
                                    <span class="px-1.5 py-0.5 bg-purple-100 text-purple-800 text-xs rounded-full whitespace-nowrap">
                                        Thẻ
                                    </span>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-center">
                                @php
                                    $transactionType = $payment->transaction_type ?? 'sale_payment';
                                @endphp
                                @if($transactionType === 'sale_payment')
                                    <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full font-semibold whitespace-nowrap">
                                        Bán Hàng
                                    </span>
                                @elseif($transactionType === 'return')
                                    <span class="px-1.5 py-0.5 bg-orange-100 text-orange-700 text-xs rounded-full font-semibold whitespace-nowrap">
                                        Trả Hàng
                                    </span>
                                @elseif($transactionType === 'exchange' || $transactionType === 'exchange_payment')
                                    <span class="px-1.5 py-0.5 bg-purple-100 text-purple-700 text-xs rounded-full font-semibold whitespace-nowrap">
                                        Đổi Hàng
                                    </span>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-center text-xs">
                                @if($payment->createdBy)
                                    <div class="flex items-center justify-center">
                                        <i class="fas fa-user-circle text-blue-500 mr-1"></i>
                                        <span class="truncate max-w-[100px]" title="{{ $payment->createdBy->name }}">{{ $payment->createdBy->name }}</span>
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-xs text-gray-600 truncate max-w-[150px]" title="{{ $payment->notes ?? '-' }}">{{ $payment->notes ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-receipt text-4xl mb-2"></i>
                <p>Chưa có thanh toán nào</p>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Collect Payment Modal -->
<div id="collectModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-75 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
    <div class="relative mx-auto p-4 border border-gray-300 w-full max-w-lg shadow-2xl rounded-xl bg-white">
        <div class="mt-1">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-900 flex items-center">
                    Thanh toán
                </h3>
                <button onclick="closeCollectModal()" class="text-gray-400 hover:text-gray-600 text-2xl w-8 h-8">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="{{ route('debt.collect', $debt->id) }}" id="payment-form" onsubmit="return validatePayment(event)">
                @csrf
                
                <div class="space-y-4">
                    <!-- Số tiền còn nợ hiển thị rõ -->
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                        <p class="text-sm text-gray-700 mb-1 font-medium">Số tiền còn thanh toán:</p>
                        @if($debt->sale->total_usd > 0 && $debt->sale->total_vnd > 0)
                            {{-- Mixed: Hiển thị cả USD và VND --}}
                            <div class="text-2xl font-bold text-red-600">${{ number_format($debt->sale->debt_usd, 2) }} + {{ number_format($debt->sale->debt_vnd, 0, ',', '.') }}đ</div>
                        @elseif($debt->sale->total_usd > 0)
                            {{-- USD only --}}
                            <div class="text-2xl font-bold text-red-600">${{ number_format($debt->sale->debt_usd, 2) }}</div>
                        @else
                            {{-- VND only --}}
                            <div class="text-2xl font-bold text-red-600">{{ number_format($debt->sale->debt_vnd, 0, ',', '.') }}đ</div>
                        @endif
                    </div>

                    <!-- Tỉ giá hiện tại -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                        <label class="block text-sm font-bold text-yellow-900 mb-2">
                            <i class="fas fa-exchange-alt mr-1"></i>Tỷ giá hiện tại (VND/USD)
                        </label>
                        <input type="text" 
                               name="current_exchange_rate" 
                               id="current_rate" 
                               class="w-full px-3 py-2 text-base font-semibold border border-yellow-300 rounded-lg focus:ring-2 focus:ring-yellow-500 bg-white" 
                               value="{{ number_format($debt->sale->exchange_rate, 0, ',', '.') }}"
                               oninput="formatVND(this); calculatePayment()" 
                               onblur="formatVND(this)">
                        <p class="text-xs text-yellow-700 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>Tỉ giá ban đầu: {{ number_format($debt->sale->exchange_rate, 0, ',', '.') }} VND/USD
                        </p>
                    </div>

                    <!-- Thanh toán USD/VND -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-bold text-blue-900 mb-2">
                                Trả bằng USD
                            </label>
                            <input type="text" 
                                   name="payment_usd" 
                                   id="payment_usd" 
                                   class="w-full px-3 py-2 text-base font-semibold border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                   placeholder="0.00"
                                   oninput="formatUSD(this); calculatePayment()">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-green-900 mb-2">
                                Trả bằng VND
                            </label>
                            <input type="text" 
                                   name="payment_vnd" 
                                   id="payment_vnd" 
                                   class="w-full px-3 py-2 text-base font-semibold border border-green-300 rounded-lg focus:ring-2 focus:ring-green-500"
                                   placeholder="0"
                                   oninput="formatVND(this); calculatePayment()">
                        </div>
                    </div>

                    <!-- Tổng thanh toán -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <label class="block text-sm font-bold text-blue-900 mb-2">
                            Tổng thanh toán quy đổi
                        </label>
                        
                        <!-- Hiển thị USD -->
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-600">Quy đổi USD:</span>
                            <input type="text" 
                                   id="total-payment-usd" 
                                   readonly
                                   class="w-2/3 px-2 py-1 text-lg font-bold bg-transparent border-none text-right text-blue-600 focus:ring-0"
                                   value="$0.00">
                        </div>
                        
                        <!-- Hiển thị VND -->
                        <div class="flex justify-between items-center border-t border-blue-200 pt-2">
                            <span class="text-sm text-gray-600">Quy đổi VND:</span>
                            <input type="text" 
                                   name="amount" 
                                   id="payment-amount" 
                                   readonly
                                   class="w-2/3 px-2 py-1 text-base font-medium bg-transparent border-none text-right text-gray-600 focus:ring-0"
                                   value="0đ">
                        </div>
                        
                        <div id="payment-warning" class="hidden mt-2 text-xs text-red-600 bg-red-100 p-2 rounded"></div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-900 mb-2">
                            Phương thức thanh toán <span class="text-red-500">*</span>
                        </label>
                        <select name="payment_method" required class="w-full px-3 py-2 text-sm font-medium border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="cash">Tiền mặt</option>
                            <option value="bank_transfer">Chuyển khoản</option>
                            <option value="card">Thẻ</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-900 mb-2">Ghi chú</label>
                        <textarea name="notes" rows="2" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Nhập ghi chú thanh toán..."></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeCollectModal()" class="bg-gray-500 text-white px-4 py-2 text-sm font-bold rounded-lg hover:bg-gray-600 transition-coloors">
                        <i class="fas fa-times mr-1"></i>Hủy
                    </button>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 text-sm font-bold rounded-lg hover:bg-green-700 transition-colors shadow-lg">
                        <i class="fas fa-check mr-1"></i>Xác nhận thu tiền
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const maxDebtVnd = {{ $debt->sale->debt_vnd }};
const maxDebtUsd = {{ $debt->sale->debt_usd }};

function showCollectModal() {
    document.getElementById('collectModal').classList.remove('hidden');
    // Reset form
    document.getElementById('payment-form').reset();
    // Set default exchange rate if empty
    if (!document.getElementById('current_rate').value) {
        document.getElementById('current_rate').value = "{{ number_format($debt->sale->exchange_rate, 0, ',', '.') }}";
    }
    calculatePayment();
}

function closeCollectModal() {
    document.getElementById('collectModal').classList.add('hidden');
}

function formatUSD(input) {
    let value = input.value.replace(/[^\d.]/g, '');
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }
    if (parts[1] && parts[1].length > 2) {
        value = parts[0] + '.' + parts[1].substring(0, 2);
    }
    input.value = value;
}

function calculatePayment() {
    const usdInput = document.getElementById('payment_usd');
    const vndInput = document.getElementById('payment_vnd');
    const rateInput = document.getElementById('current_rate');
    const totalUsdDisplay = document.getElementById('total-payment-usd');
    const totalVndDisplay = document.getElementById('payment-amount');
    const warningDiv = document.getElementById('payment-warning');
    
    if (!usdInput || !vndInput || !rateInput) return;
    
    // Lấy giá trị
    const usd = parseFloat(usdInput.value.replace(/[^\d.]/g, '')) || 0;
    const vnd = parseFloat(vndInput.value.replace(/[^\d]/g, '')) || 0;
    let rate = parseFloat(rateInput.value.replace(/[^\d]/g, '')) || {{ $debt->sale->exchange_rate }};
    
    // Tránh chia cho 0 với phiếu VND (exchange_rate = 0)
    if (rate <= 0) rate = 1;
    
    // Xác định loại hóa đơn
    const invoiceTotalUsd = {{ $debt->sale->total_usd }};
    const invoiceTotalVnd = {{ $debt->sale->total_vnd }};
    const isUsdInvoice = invoiceTotalUsd > 0 && invoiceTotalVnd == 0;
    const isVndInvoice = invoiceTotalVnd > 0 && invoiceTotalUsd == 0;
    const isMixedInvoice = invoiceTotalUsd > 0 && invoiceTotalVnd > 0;
    
    // Tính toán và hiển thị theo quy tắc
    if (isUsdInvoice) {
        // Hóa đơn USD
        if (usd > 0 && vnd == 0) {
            // Trả USD → Chỉ hiển thị USD
            totalUsdDisplay.value = '$' + usd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            totalVndDisplay.value = '-';
        } else if (vnd > 0 && usd == 0) {
            // Trả VND → Quy đổi sang USD (thanh toán chéo)
            const convertedUsd = vnd / rate;
            totalUsdDisplay.value = '$' + convertedUsd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            totalVndDisplay.value = vnd.toLocaleString('vi-VN') + 'đ (quy đổi)';
        } else if (usd > 0 && vnd > 0) {
            // Trả cả hai
            const totalUsd = usd + (vnd / rate);
            totalUsdDisplay.value = '$' + totalUsd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            totalVndDisplay.value = vnd.toLocaleString('vi-VN') + 'đ (+ $' + usd.toFixed(2) + ')';
        } else {
            totalUsdDisplay.value = '$0.00';
            totalVndDisplay.value = '-';
        }
    } else if (isVndInvoice) {
        // Hóa đơn VND
        if (vnd > 0 && usd == 0) {
            // Trả VND → Chỉ hiển thị VND
            totalUsdDisplay.value = '-';
            totalVndDisplay.value = vnd.toLocaleString('vi-VN') + 'đ';
        } else if (usd > 0 && vnd == 0) {
            // Trả USD → Quy đổi sang VND (thanh toán chéo)
            const convertedVnd = usd * rate;
            totalUsdDisplay.value = '$' + usd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' (quy đổi)';
            totalVndDisplay.value = convertedVnd.toLocaleString('vi-VN') + 'đ';
        } else if (usd > 0 && vnd > 0) {
            // Trả cả hai
            const totalVnd = vnd + (usd * rate);
            totalUsdDisplay.value = '$' + usd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' (+ ' + vnd.toLocaleString('vi-VN') + 'đ)';
            totalVndDisplay.value = totalVnd.toLocaleString('vi-VN') + 'đ';
        } else {
            totalUsdDisplay.value = '-';
            totalVndDisplay.value = '0đ';
        }
    } else {
        // Hóa đơn hỗn hợp - hiển thị cả hai
        const totalUsd = usd + (vnd / rate);
        const totalVnd = (usd * rate) + vnd;
        totalUsdDisplay.value = '$' + totalUsd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        totalVndDisplay.value = totalVnd.toLocaleString('vi-VN') + 'đ';
    }
    
    // Cảnh báo real-time
    warningDiv.classList.add('hidden');
    warningDiv.className = 'mt-2 text-xs p-2 rounded'; // Reset classes
    
    // 1. Cảnh báo thanh toán chéo cần tỷ giá
    const originalRate = {{ $debt->sale->exchange_rate }};
    let hasWarning = false;
    
    if (isUsdInvoice && vnd > 0 && usd == 0) {
        // Hóa đơn USD, trả VND - cần tỷ giá
        if (rate <= 1 || (rate == originalRate && originalRate == 0)) {
            warningDiv.className += ' text-orange-600 bg-orange-100 border border-orange-300';
            warningDiv.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> <strong>Thanh toán chéo:</strong> Hóa đơn USD nhưng trả VND. Vui lòng nhập tỷ giá hiện tại!';
            warningDiv.classList.remove('hidden');
            hasWarning = true;
        } else {
            warningDiv.className += ' text-blue-600 bg-blue-100 border border-blue-300';
            warningDiv.innerHTML = '<i class="fas fa-info-circle mr-1"></i> Thanh toán chéo: Hóa đơn USD, trả VND. Tỷ giá áp dụng: ' + rate.toLocaleString('vi-VN') + ' VND/USD';
            warningDiv.classList.remove('hidden');
            hasWarning = true;
        }
    } else if (isVndInvoice && usd > 0 && vnd == 0) {
        // Hóa đơn VND, trả USD - cần tỷ giá
        if (rate <= 1 || (rate == originalRate && originalRate == 0)) {
            warningDiv.className += ' text-orange-600 bg-orange-100 border border-orange-300';
            warningDiv.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> <strong>Thanh toán chéo:</strong> Hóa đơn VND nhưng trả USD. Vui lòng nhập tỷ giá hiện tại!';
            warningDiv.classList.remove('hidden');
            hasWarning = true;
        } else {
            warningDiv.className += ' text-blue-600 bg-blue-100 border border-blue-300';
            warningDiv.innerHTML = '<i class="fas fa-info-circle mr-1"></i> Thanh toán chéo: Hóa đơn VND, trả USD. Tỷ giá áp dụng: ' + rate.toLocaleString('vi-VN') + ' VND/USD';
            warningDiv.classList.remove('hidden');
            hasWarning = true;
        }
    }
    
    // 2. Cảnh báo vượt quá nợ (chỉ khi không có cảnh báo tỷ giá)
    if (!hasWarning) {
        const tolerance = 0.01;
        const vndTolerance = 1000;
        let isOverPayment = false;
        
        if (isUsdInvoice || isMixedInvoice) {
            const totalUsd = usd + (vnd / rate);
            if (totalUsd > maxDebtUsd + tolerance) {
                const totalVnd = (usd * rate) + vnd;
                if (totalVnd > maxDebtVnd + vndTolerance) {
                    isOverPayment = true;
                }
            }
        } else if (isVndInvoice) {
            const totalVnd = vnd + (usd * rate);
            if (totalVnd > maxDebtVnd + vndTolerance) {
                isOverPayment = true;
            }
        }
        
        if (isOverPayment) {
            warningDiv.className += ' text-red-600 bg-red-100 border border-red-300';
            warningDiv.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i> Số tiền vượt quá nợ còn lại!`;
            warningDiv.classList.remove('hidden');
        }
    }
}

function validatePayment(event) {
    const usdInput = document.getElementById('payment_usd');
    const vndInput = document.getElementById('payment_vnd');
    const rateInput = document.getElementById('current_rate');
    const totalVndInput = document.getElementById('payment-amount');
    
    const usd = parseFloat(usdInput.value.replace(/[^\d.]/g, '')) || 0;
    const vnd = parseFloat(vndInput.value.replace(/[^\d]/g, '')) || 0;
    let rate = parseFloat(rateInput.value.replace(/[^\d]/g, '')) || {{ $debt->sale->exchange_rate }};
    
    // Tránh chia cho 0 với phiếu VND (exchange_rate = 0)
    if (rate <= 0) rate = 1;
    
    // Validate cơ bản
    if (usd === 0 && vnd === 0) {
        alert('Vui lòng nhập số tiền USD hoặc VND');
        return false;
    }
    
    // Tính toán lại để validate
    const totalUsd = usd + (vnd / rate);
    const totalVnd = (usd * rate) + vnd;
    
    // Logic validate (giống calculatePayment)
    const tolerance = 0.01;
    const vndTolerance = 1000;
    
    if (totalUsd > maxDebtUsd + tolerance) {
        if (totalVnd > maxDebtVnd + vndTolerance) {
            alert(`Số tiền thanh toán vượt quá số nợ!\n\nNợ còn lại: $${maxDebtUsd.toLocaleString('en-US', {minimumFractionDigits: 2})} (hoặc ${maxDebtVnd.toLocaleString('vi-VN')}đ)\n\nBạn đang trả: $${totalUsd.toLocaleString('en-US', {minimumFractionDigits: 2})} (quy đổi)`);
            return false;
        }
    }
    
    // Unformat values before submit
    usdInput.value = usd;
    vndInput.value = vnd;
    rateInput.value = rate;
    totalVndInput.value = totalVnd; // Submit tổng VND để controller xử lý (mặc dù controller sẽ tính lại từ usd/vnd components)
    
    return true;
}

// Close modal when clicking outside
document.getElementById('collectModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeCollectModal();
    }
});
</script>
@endsection
