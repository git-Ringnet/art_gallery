@extends('layouts.app')

@section('title', 'Sửa hóa đơn bán hàng')
@section('page-title', 'Sửa hóa đơn bán hàng')
@section('page-description', 'Chỉnh sửa hóa đơn bán hàng')

@section('content')
    <x-alert />

    <!-- Confirm Modal for Edit Sales -->
    <x-confirm-modal id="confirm-edit-modal" title="Xác nhận cập nhật hóa đơn"
        message="Bạn có chắc chắn muốn cập nhật hóa đơn này?" confirmText="Cập nhật" cancelText="Quay lại" type="warning">
        <div id="confirm-edit-summary" class="text-sm">
            <!-- Order summary will be populated by JavaScript -->
        </div>
    </x-confirm-modal>

    @php
        $hasReturns = $sale->returns()->whereIn('status', ['approved', 'completed'])->exists();
    @endphp

    @if($hasReturns)
        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl mr-3"></i>
                <div>
                    <h4 class="text-yellow-800 font-semibold">Lưu ý: Phiếu này đã có trả/đổi hàng</h4>
                    <p class="text-yellow-700 text-sm">Không thể sửa danh sách sản phẩm. Chỉ có thể trả thêm tiền hoặc cập nhật
                        thông tin khách hàng.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-lg p-4 glass-effect">
        <form action="{{ route('sales.update', $sale->id) }}" method="POST" id="sales-form">
            @csrf
            @method('PUT')

            <!-- BƯỚC 1: THÔNG TIN CƠ BẢN -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg mb-4">
                <h3 class="text-base font-bold text-blue-900 mb-3 flex items-center">
                    <span
                        class="bg-blue-500 text-white w-7 h-7 rounded-full flex items-center justify-center mr-2 text-sm">1</span>
                    Thông tin hóa đơn
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Số hóa đơn</label>
                        <div class="flex gap-2">
                            <input type="text" name="invoice_code" id="invoice_code"
                                class="flex-1 px-3 py-1.5 text-sm border border-gray-300 rounded-lg font-medium text-blue-600"
                                value="{{ $sale->invoice_code }}">
                            <button type="button" onclick="generateInvoiceCode()"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-lg transition-colors"
                                title="Tự động tạo">
                                <i class="fas fa-magic text-sm"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Showroom <span
                                class="text-red-500">*</span></label>
                        <select name="showroom_id" id="showroom_id" required
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Chọn showroom --</option>
                            @foreach($showrooms as $showroom)
                                <option value="{{ $showroom->id }}" data-code="{{ $showroom->code }}" {{ $sale->showroom_id == $showroom->id ? 'selected' : '' }}>{{ $showroom->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Ngày bán <span
                                class="text-red-500">*</span></label>
                        <input type="date" name="sale_date" required
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            value="{{ $sale->sale_date->format('Y-m-d') }}">
                    </div>
                </div>
            </div>

            <!-- BƯỚC 2: THÔNG TIN KHÁCH HÀNG -->
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg mb-4">
                <h3 class="text-base font-bold text-green-900 mb-3 flex items-center">
                    <span
                        class="bg-green-500 text-white w-7 h-7 rounded-full flex items-center justify-center mr-2 text-sm">2</span>
                    Thông tin khách hàng
                </h3>
                <div class="grid grid-cols-1 gap-3 mb-3">
                    <div class="relative">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Tên khách hàng <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="customer_name" id="customer_name" required
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                            value="{{ $sale->customer->name }}" autocomplete="off"
                            onkeyup="filterCustomers(this.value); clearCustomerId()" onfocus="showAllCustomers()"
                            onclick="showAllCustomers()">
                        <input type="hidden" name="customer_id" id="customer_id" value="{{ $sale->customer_id }}">
                        <div id="customer-suggestions"
                            class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-y-auto hidden shadow-lg">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>Nhập tên để tìm khách hàng cũ hoặc nhập thông tin mới để
                            tạo khách hàng mới
                        </p>
                    </div>
                </div>
                <!-- Các trường thông tin khách hàng - LUÔN HIỂN THỊ -->
                <div id="customer-details" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Số điện thoại</label>
                        <input type="tel" name="customer_phone" id="customer_phone"
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                            value="{{ $sale->customer->phone }}" placeholder="Nhập số điện thoại...">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="customer_email" id="customer_email"
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                            value="{{ $sale->customer->email }}" placeholder="Nhập email...">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Địa chỉ</label>
                        <input type="text" name="customer_address" id="customer_address"
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                            value="{{ $sale->customer->address }}" placeholder="Nhập địa chỉ...">
                    </div>
                </div>
                <!-- Thông báo khi chọn khách hàng có sẵn -->
                <div id="customer-selected-notice" class="hidden mt-2 p-2 bg-green-100 border border-green-300 rounded-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-green-700">
                            <i class="fas fa-check-circle mr-1"></i>Đã chọn khách hàng có sẵn
                        </span>
                        <button type="button" onclick="resetCustomerFields()"
                            class="text-sm text-red-600 hover:text-red-800">
                            <i class="fas fa-times mr-1"></i>Xóa & Nhập mới
                        </button>
                    </div>
                </div>
                <!-- Cảnh báo trùng tên khách hàng -->
                <div id="customer-duplicate-warning"
                    class="hidden mt-2 p-3 bg-yellow-100 border border-yellow-400 rounded-lg">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mt-0.5"></i>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-yellow-800">Phát hiện khách hàng trùng tên!</p>
                            <p class="text-xs text-yellow-700 mt-1" id="duplicate-customer-info"></p>
                            <div class="flex gap-2 mt-2">
                                <button type="button" onclick="useExistingCustomer()"
                                    class="px-3 py-1 text-xs bg-green-500 text-white rounded hover:bg-green-600 transition-colors">
                                    <i class="fas fa-user-check mr-1"></i>Dùng KH có sẵn
                                </button>
                                <button type="button" onclick="forceCreateNewCustomer()"
                                    class="px-3 py-1 text-xs bg-orange-500 text-white rounded hover:bg-orange-600 transition-colors">
                                    <i class="fas fa-user-plus mr-1"></i>Tạo KH mới (trùng tên)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="force_new_customer" id="force_new_customer" value="0">
            </div>

            <!-- BƯỚC 3: DANH SÁCH SẢN PHẨM -->
            @if($hasReturns)
                <!-- Hiển thị readonly khi đã có return -->
                <div class="bg-gray-50 border-l-4 border-gray-400 p-4 rounded-lg mb-4">
                    <h3 class="text-base font-bold text-gray-700 mb-3 flex items-center">
                        <span
                            class="bg-gray-400 text-white w-7 h-7 rounded-full flex items-center justify-center mr-2 text-sm">3</span>
                        Danh sách sản phẩm (Chỉ xem)
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th class="px-2 py-2 text-left text-xs">Hình</th>
                                    <th class="px-2 py-2 text-left text-xs">Sản phẩm</th>
                                    <th class="px-2 py-2 text-center text-xs">SL</th>
                                    <th class="px-2 py-2 text-right text-xs">Giá USD</th>
                                    <th class="px-2 py-2 text-right text-xs">Giá VND</th>
                                    <th class="px-2 py-2 text-right text-xs">Giảm(%)</th>
                                    <th class="px-2 py-2 text-right text-xs">Giảm tiền</th>
                                    <th class="px-2 py-2 text-right text-xs">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y">
                                @foreach($sale->saleItems as $item)
                                    <tr class="{{ $item->is_returned ? 'bg-red-50 opacity-60' : '' }}">
                                        <td class="px-2 py-2">
                                            @if($item->painting_id && $item->painting && $item->painting->image)
                                                <img src="{{ asset('storage/' . $item->painting->image) }}"
                                                    class="w-12 h-12 object-cover rounded" alt="{{ $item->description }}">
                                            @else
                                                <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                                    <i class="fas fa-image text-gray-400 text-xs"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-2 py-2">
                                            <div
                                                class="font-medium text-xs {{ $item->is_returned ? 'line-through text-gray-500' : '' }}">
                                                {{ $item->description }}
                                            </div>
                                            @if($item->is_returned)
                                                <span class="text-xs text-red-600 font-semibold">
                                                    <i class="fas fa-undo"></i>Trả
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-2 py-2 text-center text-xs">{{ $item->quantity }}</td>
                                        <td class="px-2 py-2 text-right text-xs whitespace-nowrap">
                                            @if($item->currency === 'USD')
                                                ${{ number_format($item->price_usd, 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-2 py-2 text-right text-xs whitespace-nowrap">
                                            @if($item->currency === 'VND' || $item->currency === 'BOTH')
                                                {{ number_format($item->price_vnd, 0, ',', '.') }}đ
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-2 py-2 text-right text-xs">{{ $item->discount_percent }}%</td>
                                        <td class="px-2 py-2 text-right text-xs">
                                            @if($item->currency === 'USD')
                                                ${{ number_format($item->discount_amount_usd, 2) }}
                                            @elseif($item->currency === 'VND')
                                                {{ number_format($item->discount_amount_vnd, 0, ',', '.') }}đ
                                            @else
                                                ${{ number_format($item->discount_amount_usd, 2) }} <br>
                                                {{ number_format($item->discount_amount_vnd, 0, ',', '.') }}đ
                                            @endif
                                        </td>
                                        <td class="px-2 py-2 text-right font-semibold text-xs whitespace-nowrap">
                                            {{ number_format($item->total_vnd, 0, ',', '.') }}đ
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <!-- Form edit bình thường khi chưa có return -->
                <div class="bg-purple-50 border-l-4 border-purple-500 p-4 rounded-lg mb-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3 gap-2">
                        <h3 class="text-base font-bold text-purple-900 flex items-center">
                            <span
                                class="bg-purple-500 text-white w-7 h-7 rounded-full flex items-center justify-center mr-2 text-sm">3</span>
                            Danh sách sản phẩm
                        </h3>
                        <button type="button" onclick="addItem()"
                            class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-1.5 rounded-lg transition-colors font-medium text-sm whitespace-nowrap">
                            <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
                        </button>
                    </div>
                    <div class="#">
                        <table class="w-full border-collapse text-sm">
                            <thead>
                                <tr class="bg-purple-100">
                                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 border">Hình</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 border">Mô tả(Mã
                                        tranh/Khung)</th>
                                    <th class="px-2 py-2 text-center text-xs font-medium text-gray-700 border">SL</th>
                                    <th class="px-2 py-2 text-center text-xs font-medium text-gray-700 border">Loại tiền</th>
                                    <th class="px-2 py-2 text-right text-xs font-medium text-gray-700 border">Giá USD</th>
                                    <th class="px-2 py-2 text-right text-xs font-medium text-gray-700 border">Giá VND</th>
                                    <th class="px-2 py-2 text-center text-xs font-medium text-gray-700 border">Giảm(%)</th>
                                    <th class="px-2 py-2 text-center text-xs font-medium text-gray-700 border">Giảm tiền</th>
                                    <th class="px-2 py-2 text-center text-xs font-medium text-gray-700 border">Xóa</th>
                                </tr>
                            </thead>
                            <tbody id="items-body" class="bg-white"></tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- BƯỚC 4: TÍNH TOÁN & THANH TOÁN -->
            <div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded-lg mb-4">
                <h3 class="text-base font-bold text-orange-900 mb-3 flex items-center">
                    <span
                        class="bg-orange-500 text-white w-7 h-7 rounded-full flex items-center justify-center mr-2 text-sm">4</span>
                    Tính toán & Thanh toán
                </h3>

                <!-- Tỷ giá và Giảm giá -->
                <!-- Tỷ giá ban đầu, Giảm giá và Tổng tiền -->
                <!-- Tỷ giá và Giảm giá -->
                <!-- Tỷ giá ban đầu, Giảm giá và Tổng tiền -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Tỷ giá (VND/USD)<span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="exchange_rate" id="rate" required
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                            value="{{ round($sale->exchange_rate) }}" placeholder="25000"
                            oninput="calcTotalPaid(); calcDebt(); calc()" onchange="calc()">
                        <div class="mt-1 text-xs text-blue-600">
                            <i class="fas fa-info-circle mr-1"></i>Tỷ giá gốc:
                            {{ number_format(round($sale->exchange_rate)) }}
                        </div>
                        <div class="mt-1 text-xs text-gray-500 italic">
                            Dùng để quy đổi khi thanh toán chéo (VND→USD hoặc USD→VND)
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Giảm giá (%)</label>
                        <input type="number" name="discount_percent" id="discount"
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                            value="{{ round($sale->discount_percent) }}" min="0" max="100" step="1" onchange="calc()">
                    </div>
                    <div id="discount-usd-section">
                        <label class="block text-xs font-medium text-blue-700 mb-1">Giảm tiền (USD)</label>
                        <input type="text" name="discount_amount_usd" id="discount_amount_usd"
                            class="w-full px-3 py-1.5 text-sm border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            value="{{ $sale->discount_amount_usd > 0 ? number_format($sale->discount_amount_usd, 2) : '0.00' }}"
                            oninput="calc()" onblur="formatUSD(this)" placeholder="0.00">
                    </div>
                    <div id="discount-vnd-section">
                        <label class="block text-xs font-medium text-green-700 mb-1">Giảm tiền (VND)</label>
                        <input type="text" name="discount_amount_vnd" id="discount_amount_vnd"
                            class="w-full px-3 py-1.5 text-sm border border-green-300 rounded-lg focus:ring-2 focus:ring-green-500"
                            value="{{ $sale->discount_amount_vnd > 0 ? number_format($sale->discount_amount_vnd) : '0' }}"
                            oninput="formatVND(this); calc()" onblur="formatVND(this)" placeholder="0">
                    </div>
                </div>

                <!-- Phí vận chuyển -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-blue-700 mb-1">Phí vận chuyển (USD)</label>
                        <input type="text" name="shipping_fee_usd" id="shipping_fee_usd"
                            class="w-full px-3 py-1.5 text-sm border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            value="{{ number_format($sale->shipping_fee_usd ?? 0, 2) }}" oninput="calc()"
                            onblur="formatUSD(this)" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-green-700 mb-1">Phí vận chuyển (VND)</label>
                        <input type="text" name="shipping_fee_vnd" id="shipping_fee_vnd"
                            class="w-full px-3 py-1.5 text-sm border border-green-300 rounded-lg focus:ring-2 focus:ring-green-500"
                            value="{{ number_format($sale->shipping_fee_vnd ?? 0) }}" oninput="formatVND(this); calc()"
                            onblur="formatVND(this)" placeholder="0">
                    </div>
                </div>

                <div class="mb-3">
                    <div>
                        <label class="block text-xs font-medium text-blue-900 mb-1">Tổng USD</label>
                        <input type="text" id="total_usd" readonly
                            class="w-full px-3 py-1.5 text-sm border-2 border-blue-300 rounded-lg bg-white font-bold text-blue-600"
                            value="${{ number_format($sale->total_usd, 2) }}">
                    </div>
                </div>
                @php
                    // Kiểm tra xem có sản phẩm VND không
                    $hasVndItems = $sale->saleItems->where('currency', 'VND')->count() > 0;
                    $hasReturns = $sale->returns()->where('status', 'completed')->where('type', 'return')->exists();
                    $hasExchanges = $sale->returns()->where('status', 'completed')->where('type', 'exchange')->exists();

                    // Lấy original_total
                    if ($sale->original_total_vnd) {
                        $originalTotal = $sale->original_total_vnd;
                    } else {
                        $originalTotal = $sale->saleItems->sum('total_vnd');
                    }

                    $showStrikethrough = ($hasReturns || $hasExchanges) && $originalTotal != $sale->total_vnd;
                @endphp

                @if($hasVndItems)
                    <div>
                        <label class="block text-xs font-medium text-green-900 mb-1">Tổng VND</label>
                        @if($showStrikethrough)
                            <!-- Có trả/đổi hàng - hiển thị giá gốc gạch ngang -->
                            <div class="w-full px-3 py-1.5 text-sm border-2 border-green-300 rounded-lg bg-white">
                                <div class="text-xs text-gray-400 line-through">
                                    {{ number_format($originalTotal, 0, ',', '.') }}đ
                                </div>
                                <div class="font-bold text-orange-600 text-sm">
                                    {{ number_format($sale->total_vnd, 0, ',', '.') }}đ
                                </div>
                            </div>
                        @else
                            <!-- Không có trả/đổi hàng -->
                            <input type="text" id="total_vnd" readonly
                                class="w-full px-3 py-1.5 text-sm border-2 border-green-300 rounded-lg bg-white font-bold text-green-600"
                                value="{{ number_format($sale->total_vnd) }}đ">
                        @endif
                    </div>
                @else
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">Tổng VND là giá tổng với tỷ số quy đổi
                            cũ có thể sai số!</label>
                        <input type="text" id="total_vnd" readonly
                            class="w-full px-3 py-1.5 text-sm border-2 border-gray-200 rounded-lg bg-gray-50 font-bold text-gray-400"
                            value="0đ">
                    </div>
                @endif
            </div>

            <!-- Tổng tiền quy đổi (Hiển thị khi có USD) -->
            <div id="converted-total-wrapper" class="mb-4 hidden">
                <div class="p-3 bg-blue-50 border border-blue-100 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-bold text-blue-900">
                            <i class="fas fa-calculator mr-1"></i>Tổng tiền quy đổi (VND)
                        </span>
                        <span id="converted_total_vnd" class="text-lg font-bold text-blue-700">0đ</span>
                    </div>
                    <div class="text-[11px] text-blue-600 text-right mt-1 font-medium italic">
                        (Tổng USD x Tỷ giá + Tổng VND)
                    </div>
                </div>
            </div>

            <!-- Thanh toán -->
            <div class="bg-white p-3 rounded-lg border border-orange-200">
                <h4 class="text-sm font-semibold text-gray-700 mb-2">Thanh toán</h4>

                @if($sale->sale_status === 'completed')
                    <!-- Phiếu đã duyệt - cho phép trả thêm -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                        <!-- Cột USD -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Trả thêm USD</label>
                            <input type="text" id="paid_usd_display"
                                class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                                value="0.00" oninput="formatPaymentUSD(this)" placeholder="0.00">
                            <input type="hidden" name="payment_usd" id="paid_usd" value="0">

                            <!-- Lịch sử trả USD -->
                            @php
                                $hasUsdPayments = $sale->payments->where('payment_usd', '>', 0)->count() > 0;
                                $hasInitialUsd = ($sale->payment_usd ?? 0) > 0 && $sale->isPending();
                                $showUsdHistory = $hasUsdPayments || $hasInitialUsd;
                            @endphp
                            @if($showUsdHistory)
                                <div class="mt-2 p-2 bg-blue-50 rounded border border-blue-200">
                                    <div class="text-xs font-semibold text-blue-700 mb-1">
                                        <i class="fas fa-history mr-1"></i> Lịch sử USD
                                    </div>
                                    <div class="space-y-1 max-h-24 overflow-y-auto">
                                        @if($hasUsdPayments)
                                            @foreach($sale->payments->where('payment_usd', '>', 0) as $payment)
                                                <div class="flex justify-between items-center text-xs">
                                                    <span class="text-gray-600">{{ $payment->payment_date->format('d/m/Y') }}</span>
                                                    <span
                                                        class="font-semibold text-blue-600">+${{ number_format($payment->payment_usd, 2) }}</span>
                                                </div>
                                            @endforeach
                                        @else
                                            {{-- Hiển thị thanh toán ban đầu từ sale (pending) --}}
                                            <div class="flex justify-between items-center text-xs">
                                                <span class="text-gray-600">{{ $sale->created_at->format('d/m/Y') }}</span>
                                                <span
                                                    class="font-semibold text-blue-600">+${{ number_format($sale->payment_usd, 2) }}</span>
                                                <span class="ml-1 text-xs bg-yellow-100 text-yellow-800 px-1 rounded">Chờ
                                                    duyệt</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="mt-1 pt-1 border-t border-blue-300 flex justify-between items-center">
                                        <span class="text-xs font-semibold text-blue-700">Tổng USD:</span>
                                        <span
                                            class="text-sm font-bold text-blue-600">${{ number_format($hasUsdPayments ? $sale->payments->sum('payment_usd') : $sale->payment_usd, 2) }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Cột VND -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Trả thêm VND</label>
                            <input type="text" id="paid_vnd_display"
                                class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                                value="0" oninput="formatPaymentVND(this)" placeholder="0">
                            <input type="hidden" name="payment_vnd" id="paid_vnd" value="0">

                            <!-- Lịch sử trả VND -->
                            @php
                                $hasVndPayments = $sale->payments->where('payment_vnd', '>', 0)->count() > 0;
                                $hasInitialVnd = ($sale->payment_vnd ?? 0) > 0 && $sale->isPending();
                                $showVndHistory = $hasVndPayments || $hasInitialVnd;
                            @endphp
                            @if($showVndHistory)
                                <div class="mt-2 p-2 bg-green-50 rounded border border-green-200">
                                    <div class="text-xs font-semibold text-green-700 mb-1">
                                        <i class="fas fa-history mr-1"></i> Lịch sử VND
                                    </div>
                                    <div class="space-y-1 max-h-24 overflow-y-auto">
                                        @if($hasVndPayments)
                                            @foreach($sale->payments->where('payment_vnd', '>', 0) as $payment)
                                                <div class="flex justify-between items-center text-xs">
                                                    <span class="text-gray-600">{{ $payment->payment_date->format('d/m/Y') }}</span>
                                                    <span
                                                        class="font-semibold text-green-600">+{{ number_format($payment->payment_vnd) }}đ</span>
                                                </div>
                                            @endforeach
                                        @else
                                            {{-- Hiển thị thanh toán ban đầu từ sale (pending) --}}
                                            <div class="flex justify-between items-center text-xs">
                                                <span class="text-gray-600">{{ $sale->created_at->format('d/m/Y') }}</span>
                                                <span
                                                    class="font-semibold text-green-600">+{{ number_format($sale->payment_vnd) }}đ</span>
                                                <span class="ml-1 text-xs bg-yellow-100 text-yellow-800 px-1 rounded">Chờ
                                                    duyệt</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="mt-1 pt-1 border-t border-green-300 flex justify-between items-center">
                                        <span class="text-xs font-semibold text-green-700">Tổng VND:</span>
                                        <span
                                            class="text-sm font-bold text-green-600">{{ number_format($hasVndPayments ? $sale->payments->sum('payment_vnd') : $sale->payment_vnd) }}đ</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Phương thức</label>
                            <select name="payment_method" id="payment_method"
                                class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                                <option value="cash" {{ old('payment_method', $sale->payment_method ?? 'cash') == 'cash' ? 'selected' : '' }}>Tiền mặt</option>
                                <option value="bank_transfer" {{ old('payment_method', $sale->payment_method ?? 'cash') == 'bank_transfer' ? 'selected' : '' }}>Chuyển khoản</option>
                                <option value="card" {{ old('payment_method', $sale->payment_method ?? 'cash') == 'card' ? 'selected' : '' }}>Thẻ</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-blue-900 mb-1">
                                Tổng trả thêm (quy đổi)
                                <i class="fas fa-info-circle text-blue-500 cursor-pointer ml-1 hover:text-blue-700 transition-colors"
                                    onclick="showExchangeRateInfo()"
                                    title="Tổng = USD + (VND ÷ tỷ giá) hoặc (USD × tỷ giá) + VND"></i>
                            </label>
                            <input type="text" id="total_paid_display" readonly
                                class="w-full px-3 py-1.5 text-sm border border-blue-300 rounded-lg bg-blue-50 font-bold text-blue-600">
                            <input type="hidden" name="payment_amount" id="total_paid_value">
                            <div class="mt-1 text-xs text-blue-700 font-medium" id="total_paid_usd_display"></div>
                            <div class="mt-1 text-xs text-gray-600 italic">
                                <i class="fas fa-calculator mr-1"></i>Chi tiết:
                                <span id="payment_detail_text">USD: $0.00, VND: 0đ</span>
                            </div>
                            <div id="payment-warning"
                                class="hidden mt-2 text-xs text-red-600 bg-red-100 p-2 rounded flex items-center">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                <span id="payment-warning-text"></span>
                            </div>
                        </div>
                    </div>

                @else
                    <!-- Phiếu pending - cho phép sửa số tiền đã trả -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Đã trả USD</label>
                            <input type="text" id="paid_usd_display"
                                class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 bg-blue-50"
                                value="{{ number_format($sale->payment_usd ?? 0, 2) }}" oninput="formatPaymentUSD(this)"
                                placeholder="0.00">
                            <input type="hidden" name="payment_usd" id="paid_usd" value="{{ $sale->payment_usd ?? 0 }}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Đã trả VND</label>
                            <input type="text" id="paid_vnd_display"
                                class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 bg-blue-50"
                                value="{{ number_format($sale->payment_vnd ?? 0) }}" oninput="formatPaymentVND(this)"
                                placeholder="0">
                            <input type="hidden" name="payment_vnd" id="paid_vnd" value="{{ $sale->payment_vnd ?? 0 }}">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Phương thức</label>
                            <select name="payment_method" id="payment_method"
                                class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                                <option value="cash" {{ old('payment_method', $sale->payment_method ?? 'cash') == 'cash' ? 'selected' : '' }}>Tiền mặt</option>
                                <option value="bank_transfer" {{ old('payment_method', $sale->payment_method ?? 'cash') == 'bank_transfer' ? 'selected' : '' }}>Chuyển khoản</option>
                                <option value="card" {{ old('payment_method', $sale->payment_method ?? 'cash') == 'card' ? 'selected' : '' }}>Thẻ</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-blue-900 mb-1">Tổng đã trả (VND)</label>
                            <input type="text" id="total_paid_display" readonly
                                class="w-full px-3 py-1.5 text-sm border border-blue-300 rounded-lg bg-blue-50 font-bold text-blue-600">
                            <input type="hidden" name="payment_amount" id="total_paid_value">
                            <div class="mt-1 text-xs text-blue-700 font-medium" id="total_paid_usd_display"></div>
                            <div id="payment-warning"
                                class="hidden mt-2 text-xs text-red-600 bg-red-100 p-2 rounded flex items-center">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                <span id="payment-warning-text"></span>
                            </div>
                        </div>
                    </div>

                    <div class="p-2 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="text-xs text-blue-800 flex items-center">
                            <i class="fas fa-info-circle mr-1"></i>
                            <span>Số tiền này sẽ được ghi vào lịch sử thanh toán khi duyệt phiếu</span>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                    <div>
                        <label class="block text-xs font-medium text-yellow-900 mb-1">Nợ cũ</label>
                        <input type="text" id="current_debt" readonly
                            class="w-full px-3 py-1.5 text-sm border border-yellow-300 rounded-lg bg-white font-bold text-orange-600"
                            value="0đ">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-red-900 mb-1">Còn nợ (VND là giá tổng với tỷ số quy
                            đổi cũ có thể sai số!)</label>
                        <input type="text" id="debt" readonly
                            class="w-full px-3 py-1.5 text-sm border border-red-300 rounded-lg bg-white font-bold text-red-600"
                            value="{{ number_format($sale->debt_amount) }}đ">
                    </div>
                </div>
            </div>
    </div>

    <!-- Ghi chú -->
    <div class="mb-4">
        <label class="block text-xs font-medium text-gray-700 mb-1">Ghi chú</label>
        <textarea name="notes" rows="2"
            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="Nhập ghi chú (không bắt buộc)...">{{ $sale->notes }}</textarea>
    </div>

    <!-- Buttons -->
    <div class="flex flex-col sm:flex-row gap-2 pt-4 border-t-2 border-gray-200">
        <button type="button" onclick="confirmUpdateOrder()"
            class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg transition-colors font-medium shadow-lg text-sm">
            <i class="fas fa-save mr-2"></i>Cập nhật hóa đơn
        </button>
        <a href="{{ route('sales.show', $sale->id) }}"
            class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-lg transition-colors font-medium text-center shadow-lg text-sm">
            <i class="fas fa-times mr-2"></i>Hủy bỏ
        </a>
    </div>
    </form>
    </div>

    @push('scripts')
        <script src="{{ asset('js/number-format.js') }}"></script>
        <script>
            let idx = 0;
            const paintings = @json($paintings);
            const supplies = @json($supplies);
            const customers = @json($customers);
            const saleItems = @json($sale->saleItems);

            // Xử lý autocomplete khách hàng
            function filterCustomers(query) {
                const suggestions = document.getElementById('customer-suggestions');

                if (!query) {
                    suggestions.classList.add('hidden');
                    return;
                }

                const filtered = customers.filter(c =>
                    c.name.toLowerCase().includes(query.toLowerCase()) ||
                    (c.phone && c.phone.includes(query)) ||
                    (c.email && c.email.toLowerCase().includes(query.toLowerCase()))
                );

                if (filtered.length > 0) {
                    suggestions.innerHTML = filtered.map(c => `
                                                                                                                                    <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b" onclick="selectCustomer(${c.id})">
                                                                                                                                        <div class="font-medium">${c.name}</div>
                                                                                                                                        <div class="text-xs text-gray-500">${c.phone || 'Không có SĐT'} | ${c.email || 'Không có email'}</div>
                                                                                                                                    </div>
                                                                                                                                `).join('');
                    suggestions.classList.remove('hidden');
                } else {
                    suggestions.classList.add('hidden');
                }
            }

            // Hiển thị tất cả khách hàng khi hover/focus
            function showAllCustomers() {
                const suggestions = document.getElementById('customer-suggestions');
                const input = document.getElementById('customer_name');

                if (customers.length > 0) {
                    suggestions.innerHTML = customers.map(c => `
                                                                                                                                    <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b" onclick="selectCustomer(${c.id})">
                                                                                                                                        <div class="font-medium">${c.name}</div>
                                                                                                                                        <div class="text-xs text-gray-500">${c.phone || 'Không có SĐT'} | ${c.email || 'Không có email'}</div>
                                                                                                                                    </div>
                                                                                                                                `).join('');
                    suggestions.classList.remove('hidden');
                }
            }

            function selectCustomer(id) {
                const customer = customers.find(c => c.id == id);
                if (customer) {
                    document.getElementById('customer_id').value = customer.id;
                    document.getElementById('customer_name').value = customer.name;
                    document.getElementById('customer_phone').value = customer.phone || '';
                    document.getElementById('customer_email').value = customer.email || '';
                    document.getElementById('customer_address').value = customer.address || '';
                    document.getElementById('customer-suggestions').classList.add('hidden');

                    // Hiển thị thông báo đã chọn khách hàng có sẵn
                    document.getElementById('customer-selected-notice').classList.remove('hidden');

                    // Đánh dấu các input là đã chọn (thay đổi style)
                    const inputs = ['customer_phone', 'customer_email', 'customer_address'];
                    inputs.forEach(inputId => {
                        const input = document.getElementById(inputId);
                        input.classList.add('bg-green-50', 'border-green-300');
                    });

                    // Load công nợ hiện tại
                    loadCurrentDebt(customer.id);
                }
            }

            // Xóa customer_id khi người dùng thay đổi tên khách hàng
            function clearCustomerId() {
                const customerId = document.getElementById('customer_id');
                const customerName = document.getElementById('customer_name');

                // Nếu đã có customer_id và tên bị thay đổi, xóa customer_id
                if (customerId.value) {
                    const selectedCustomer = customers.find(c => c.id == customerId.value);
                    if (selectedCustomer && customerName.value !== selectedCustomer.name) {
                        customerId.value = '';
                        document.getElementById('customer-selected-notice').classList.add('hidden');

                        // Reset style các input
                        const inputs = ['customer_phone', 'customer_email', 'customer_address'];
                        inputs.forEach(inputId => {
                            const input = document.getElementById(inputId);
                            input.classList.remove('bg-green-50', 'border-green-300');
                        });

                        // Clear old debt display
                        document.getElementById('current_debt').value = '';
                    }
                }

                // Kiểm tra trùng tên
                checkDuplicateCustomer();
            }

            // Biến lưu khách hàng trùng tên (nếu có)
            let duplicateCustomer = null;

            // Kiểm tra xem có khách hàng trùng tên không
            function checkDuplicateCustomer() {
                const customerName = document.getElementById('customer_name').value.trim();
                const customerId = document.getElementById('customer_id').value;

                // Ẩn cảnh báo trước
                document.getElementById('customer-duplicate-warning').classList.add('hidden');
                duplicateCustomer = null;

                if (!customerName || customerId) return;

                // Tìm khách hàng trùng tên (case-insensitive)
                const matchingCustomer = customers.find(c =>
                    c.name.toLowerCase() === customerName.toLowerCase()
                );

                if (matchingCustomer) {
                    duplicateCustomer = matchingCustomer;
                    const infoText = `Đã tồn tại: "${matchingCustomer.name}" - SĐT: ${matchingCustomer.phone || 'Không có'} - Email: ${matchingCustomer.email || 'Không có'}`;
                    document.getElementById('duplicate-customer-info').textContent = infoText;
                    document.getElementById('customer-duplicate-warning').classList.remove('hidden');
                }
            }

            // Sử dụng khách hàng có sẵn (khi trùng tên)
            function useExistingCustomer() {
                if (duplicateCustomer) {
                    selectCustomer(duplicateCustomer.id);
                    document.getElementById('customer-duplicate-warning').classList.add('hidden');
                    document.getElementById('force_new_customer').value = '0';
                }
            }

            // Tạo khách hàng mới (dù trùng tên)
            function forceCreateNewCustomer() {
                document.getElementById('customer-duplicate-warning').classList.add('hidden');
                document.getElementById('force_new_customer').value = '1';
                duplicateCustomer = null;

                // Hiển thị thông báo đang tạo mới
                alert('Sẽ tạo khách hàng MỚI với tên này (có thể trùng với khách hàng đã tồn tại)');
            }

            // Reset tất cả các trường khách hàng để nhập mới
            function resetCustomerFields() {
                document.getElementById('customer_id').value = '';
                document.getElementById('customer_name').value = '';
                document.getElementById('customer_phone').value = '';
                document.getElementById('customer_email').value = '';
                document.getElementById('customer_address').value = '';
                document.getElementById('customer-selected-notice').classList.add('hidden');
                document.getElementById('customer-duplicate-warning').classList.add('hidden');
                document.getElementById('force_new_customer').value = '0';
                duplicateCustomer = null;

                // Reset style các input
                const inputs = ['customer_phone', 'customer_email', 'customer_address'];
                inputs.forEach(inputId => {
                    const input = document.getElementById(inputId);
                    input.classList.remove('bg-green-50', 'border-green-300');
                });

                // Clear debt display
                document.getElementById('current_debt').value = '';

                // Focus vào ô tên khách hàng
                document.getElementById('customer_name').focus();
            }


            // Ẩn suggestions khi click bên ngoài
            document.addEventListener('click', function (e) {
                // Hide customer suggestions
                if (!e.target.closest('#customer_name') && !e.target.closest('#customer-suggestions')) {
                    document.getElementById('customer-suggestions').classList.add('hidden');
                }

                // Hide item suggestions for all rows
                document.querySelectorAll('[id^="item-suggestions-"]').forEach(suggestion => {
                    const idx = suggestion.id.replace('item-suggestions-', '');
                    if (!e.target.closest(`#item-search-${idx}`) && !e.target.closest(`#item-suggestions-${idx}`)) {
                        suggestion.classList.add('hidden');
                    }
                });
            });

            function addItem() {
                const tbody = document.getElementById('items-body');
                const tr = document.createElement('tr');
                tr.className = 'border hover:bg-purple-50';
                tr.innerHTML = `
                                                                                                                                <td class="px-3 py-3 border">
                                                                                                                                    <img id="img-${idx}" src="/images/no-image.svg" class="w-20 h-16 object-cover rounded border shadow-sm">
                                                                                                                                </td>
                                                                                                                                <td class="px-3 py-3 border">
                                                                                                                                    <div class="relative">
                                                                                                                                        <input type="text" 
                                                                                                                                               id="item-search-${idx}"
                                                                                                                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 mb-2" 
                                                                                                                                               placeholder="Tìm tranh hoặc khung..."
                                                                                                                                               autocomplete="off"
                                                                                                                                               onkeyup="filterItems(this.value, ${idx})"
                                                                                                                                               onfocus="showItemSuggestions(${idx})">
                                                                                                                                        <input type="hidden" name="items[${idx}][painting_id]" id="painting-id-${idx}">
                                                                                                                                        <input type="hidden" name="items[${idx}][frame_id]" id="frame-id-${idx}">
                                                                                                                                        <input type="hidden" name="items[${idx}][description]" id="desc-${idx}">
                                                                                                                                        <div id="item-suggestions-${idx}" class="absolute z-20 w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-y-auto hidden shadow-lg"></div>
                                                                                                                                        <div id="item-details-${idx}" class="text-xs text-gray-600 space-y-0.5 hidden"></div>
                                                                                                                                    </div>
                                                                                                                                </td>
                                                                                                                                <td class="px-3 py-3 border">
                                                                                                                                    <input type="number" name="items[${idx}][quantity]" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-center font-medium" value="1" min="1" onchange="calc()">
                                                                                                                                </td>
                                                                                                                               <td class="px-3 py-3 border">
                                                                                                                                    <select name="items[${idx}][currency]" class="w-full px-3 py-2 border border-gray-300 rounded-lg" onchange="togCur(this, ${idx})">
                                                                                                                                        <option value="USD">USD</option>
                                                                                                                                        <option value="VND" selected>VND</option>
                                                                                                                                    </select>
                                                                                                                                </td>
                                                                                                                                <td class="px-3 py-3 border">
                                                                                                                                    <input type="text" name="items[${idx}][price_usd]" id="usd-input-${idx}" class="usd-${idx} w-full px-3 py-2 border border-gray-300 rounded-lg text-right" value="0.00" oninput="calc()" onblur="formatUSD(this)" onchange="calc()">
                                                                                                                                </td>
                                                                                                                                <td class="px-3 py-3 border">
                                                                                                                                    <input type="text" name="items[${idx}][price_vnd]" id="vnd-input-${idx}" class="vnd-${idx} w-full px-3 py-2 border border-gray-300 rounded-lg text-right" value="0" oninput="formatVND(this)" onblur="formatVND(this)" onchange="calc()">
                                                                                                                                </td>
                                                                                                                                <td class="px-3 py-3 border text-center">
                                                                                                                                    <input type="number" name="items[${idx}][discount_percent]" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-center" value="0" min="0" max="100" step="1" onchange="calc()">
                                                                                                                                </td>
                                                                                                                                <td class="px-3 py-3 border text-center">
                                                                                                                                    <div class="space-y-1">
                                                                                                                                        <input type="text" name="items[${idx}][discount_amount_usd]" id="discount-usd-${idx}" class="discount-usd-${idx} w-full px-2 py-1 text-sm border border-blue-300 rounded text-right hidden" value="0.00" oninput="calc()" onblur="formatUSD(this)" placeholder="USD">
                                                                                                                                        <input type="text" name="items[${idx}][discount_amount_vnd]" id="discount-vnd-${idx}" class="discount-vnd-${idx} w-full px-2 py-1 text-sm border border-green-300 rounded text-right" value="0" oninput="formatVND(this); calc()" onblur="formatVND(this)" placeholder="VND">
                                                                                                                                    </div>
                                                                                                                                </td>
                                                                                                                                <td class="px-3 py-3 border text-center">
                                                                                                                                    <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors" onclick="this.closest('tr').remove();calc()">
                                                                                                                                        <i class="fas fa-trash"></i>
                                                                                                                                    </button>
                                                                                                                                </td>
                                                                                                                            `;
                tbody.appendChild(tr);
                idx++;
            }

            // Helper to get selected frame IDs (excluding current row)
            function getSelectedFrameIds(currentIdx) {
                const selectedIds = [];
                // Query all frame id inputs
                const inputs = document.querySelectorAll('input[name^="items"][name$="[frame_id]"]');
                
                inputs.forEach(input => {
                    // Extract index from name: items[0][frame_id]
                    const match = input.name.match(/items\[(\d+)\]\[frame_id\]/);
                    if (match) {
                        const idx = parseInt(match[1]);
                        // Skip current row and empty values
                        if (idx !== currentIdx && input.value) {
                            selectedIds.push(parseInt(input.value));
                        }
                    }
                });
                return selectedIds;
            }

            // Search functions for paintings
            function filterPaintings(query, idx) {
                const suggestions = document.getElementById(`painting-suggestions-${idx}`);

                if (!query || query.length < 1) {
                    suggestions.classList.add('hidden');
                    return;
                }

                fetch(`{{ route('sales.api.search.paintings') }}?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(paintings => {
                        if (paintings.length > 0) {
                            suggestions.innerHTML = paintings.map(p => `
                                                                                                                                            <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b" onclick="selectPainting(${p.id}, ${idx})">
                                                                                                                                                <div class="font-medium text-sm">${p.code} - ${p.name}</div>
                                                                                                                                                <div class="text-xs text-gray-500">USD: $${p.price_usd || 0} | VND: ${(p.price_vnd || 0).toLocaleString()}đ</div>
                                                                                                                                            </div>
                                                                                                                                        `).join('');
                            suggestions.classList.remove('hidden');
                        } else {
                            suggestions.classList.add('hidden');
                        }
                    })
                    .catch(error => {
                        console.error('Error searching paintings:', error);
                        suggestions.classList.add('hidden');
                    });
            }

            function showPaintingSuggestions(idx) {
                const input = document.getElementById(`painting-search-${idx}`);
                const suggestions = document.getElementById(`painting-suggestions-${idx}`);

                if (input && input.value.length >= 1) {
                    filterPaintings(input.value, idx);
                }
            }

            function selectPainting(paintingId, idx) {
                fetch(`{{ route('sales.api.painting', '') }}/${paintingId}`)
                    .then(response => response.json())
                    .then(painting => {
                        document.getElementById(`painting-id-${idx}`).value = painting.id;
                        document.getElementById(`painting-search-${idx}`).value = `${painting.code} - ${painting.name}`;
                        document.getElementById(`desc-${idx}`).value = painting.name;

                        const usdInput = document.querySelector(`.usd-${idx}`);
                        const vndInput = document.querySelector(`.vnd-${idx}`);
                        const currencySelect = document.querySelector(`select[name="items[${idx}][currency]"]`);

                        const hasUsd = painting.price_usd && parseFloat(painting.price_usd) > 0;
                        const hasVnd = painting.price_vnd && parseFloat(painting.price_vnd) > 0;

                        // Tự động chọn loại tiền dựa vào giá sản phẩm
                        if (hasUsd && hasVnd) {
                            if (currencySelect) { currencySelect.value = 'BOTH'; togCur(currencySelect, idx); }
                            if (usdInput) usdInput.value = parseFloat(painting.price_usd).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            if (vndInput) vndInput.value = parseInt(painting.price_vnd).toLocaleString('en-US');
                        } else if (hasUsd) {
                            if (currencySelect) { currencySelect.value = 'USD'; togCur(currencySelect, idx); }
                            if (usdInput) usdInput.value = parseFloat(painting.price_usd).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            if (vndInput) vndInput.value = '0';
                        } else if (hasVnd) {
                            if (currencySelect) { currencySelect.value = 'VND'; togCur(currencySelect, idx); }
                            if (usdInput) usdInput.value = '0.00';
                            if (vndInput) vndInput.value = parseInt(painting.price_vnd).toLocaleString('en-US');
                        } else {
                            if (currencySelect) { currencySelect.value = 'VND'; togCur(currencySelect, idx); }
                            if (usdInput) usdInput.value = '0.00';
                            if (vndInput) vndInput.value = '0';
                        }

                        const imgUrl = painting.image ? `/storage/${painting.image}` : '/images/no-image.svg';
                        const imgElement = document.getElementById(`img-${idx}`);
                        imgElement.src = imgUrl;
                        imgElement.onclick = () => showImageModal(imgUrl, painting.name);
                        imgElement.classList.add('cursor-pointer', 'hover:opacity-80', 'transition-opacity');

                        // Display painting details
                        const detailsDiv = document.getElementById(`item-details-${idx}`);
                        if (detailsDiv) {
                            let detailsHTML = '';
                            if (painting.code) detailsHTML += `<div><span class="font-semibold">Mã:</span> ${painting.code}</div>`;
                            if (painting.artist) detailsHTML += `<div><span class="font-semibold">Họa sĩ:</span> ${painting.artist}</div>`;
                            if (painting.material) detailsHTML += `<div><span class="font-semibold">Chất liệu:</span> ${painting.material}</div>`;
                            if (painting.width && painting.height) detailsHTML += `<div><span class="font-semibold">Kích thước:</span> ${painting.width} x ${painting.height} cm</div>`;
                            if (painting.paint_year) detailsHTML += `<div><span class="font-semibold">Năm:</span> ${painting.paint_year}</div>`;

                            if (detailsHTML) {
                                detailsDiv.innerHTML = detailsHTML;
                                detailsDiv.classList.remove('hidden');
                            } else {
                                detailsDiv.classList.add('hidden');
                            }
                        }

                        document.getElementById(`painting-suggestions-${idx}`).classList.add('hidden');
                        calc();
                    })
                    .catch(error => {
                        console.error('Error fetching painting:', error);
                    });
            }

            // Search functions for supplies
            function filterSupplies(query, idx) {
                const suggestions = document.getElementById(`supply-suggestions-${idx}`);

                if (!query || query.length < 1) {
                    suggestions.classList.add('hidden');
                    return;
                }

                fetch(`{{ route('sales.api.search.supplies') }}?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(supplies => {
                        if (supplies.length > 0) {
                            suggestions.innerHTML = supplies.map(s => `
                                                                                                                                            <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b" onclick="selectSupply(${s.id}, ${idx})">
                                                                                                                                                <div class="font-medium text-sm">${s.name}</div>
                                                                                                                                                <div class="text-xs text-gray-500">Đơn vị: ${s.unit || 'N/A'}</div>
                                                                                                                                            </div>
                                                                                                                                        `).join('');
                            suggestions.classList.remove('hidden');
                        } else {
                            suggestions.classList.add('hidden');
                        }
                    })
                    .catch(error => {
                        console.error('Error searching supplies:', error);
                        suggestions.classList.add('hidden');
                    });
            }

            function showSupplySuggestions(idx) {
                const input = document.getElementById(`supply-search-${idx}`);
                const suggestions = document.getElementById(`supply-suggestions-${idx}`);

                if (input.value.length >= 1) {
                    filterSupplies(input.value, idx);
                }
            }

            function selectSupply(supplyId, idx) {
                fetch(`{{ route('sales.api.supply', '') }}/${supplyId}`)
                    .then(response => response.json())
                    .then(supply => {
                        document.getElementById(`supply-id-${idx}`).value = supply.id;
                        document.getElementById(`supply-search-${idx}`).value = supply.name;

                        document.getElementById(`supply-suggestions-${idx}`).classList.add('hidden');
                    })
                    .catch(error => {
                        console.error('Error fetching supply:', error);
                    });
            }

            // Search functions for frames
            function filterFrames(query, idx) {
                const suggestions = document.getElementById(`frame-suggestions-${idx}`);

                if (!query || query.length < 1) {
                    suggestions.classList.add('hidden');
                    return;
                }

                fetch(`{{ route('sales.api.search.frames') }}?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(frames => {
                        // Filter out already selected frames
                        const selectedIds = getSelectedFrameIds(idx);
                        const availableFrames = frames.filter(f => !selectedIds.includes(f.id));

                        if (availableFrames.length > 0) {
                            suggestions.innerHTML = availableFrames.map(f => `
                                                                                                                                            <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b" onclick="selectFrame(${f.id}, ${idx})">
                                                                                                                                                <div class="font-medium text-sm">${f.name}</div>
                                                                                                                                                <div class="text-xs text-gray-500">Giá: ${(f.cost_price || 0).toLocaleString()}đ</div>
                                                                                                                                            </div>
                                                                                                                                        `).join('');
                            suggestions.classList.remove('hidden');
                        } else {
                            suggestions.classList.add('hidden');
                        }
                    })
                    .catch(error => {
                        console.error('Error searching frames:', error);
                        suggestions.classList.add('hidden');
                    });
            }

            function showFrameSuggestions(idx) {
                const input = document.getElementById(`frame-search-${idx}`);
                const suggestions = document.getElementById(`frame-suggestions-${idx}`);

                if (input && input.value.length >= 1) {
                    filterFrames(input.value, idx);
                }
            }

            function selectFrame_old(frameId, idx) {
                fetch(`{{ route('frames.show', '') }}/${frameId}`)
                    .then(response => response.json())
                    .then(frame => {
                        document.getElementById(`frame-id-${idx}`).value = frame.id;
                        document.getElementById(`frame-search-${idx}`).value = frame.name;

                        document.getElementById(`frame-suggestions-${idx}`).classList.add('hidden');
                    })
                    .catch(error => {
                        console.error('Error fetching frame:', error);
                    });
            }

            // NEW: Search function for both paintings and frames
            function filterItems(query, idx) {
                const suggestions = document.getElementById(`item-suggestions-${idx}`);

                if (!query || query.length < 1) {
                    suggestions.classList.add('hidden');
                    return;
                }

                // Fetch both paintings and frames
                Promise.all([
                    fetch(`{{ route('sales.api.search.paintings') }}?q=${encodeURIComponent(query)}`).then(r => r.json()),
                    fetch(`{{ route('sales.api.search.frames') }}?q=${encodeURIComponent(query)}`).then(r => r.json())
                ])
                    .then(([paintings, frames]) => {
                        let html = '';

                        // Filter out already selected frames
                        const selectedIds = getSelectedFrameIds(idx);
                        const availableFrames = frames.filter(f => !selectedIds.includes(f.id));

                        // Add paintings section
                        if (paintings.length > 0) {
                            html += '<div class="px-3 py-1 bg-gray-100 text-xs font-bold text-gray-600">TRANH</div>';
                            html += paintings.map(p => {
                                const stock = p.quantity || 0;
                                const isOutOfStock = stock <= 0;
                                const bgClass = isOutOfStock ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-blue-50';
                                const stockColor = isOutOfStock ? 'text-red-600 font-bold' : (stock < 5 ? 'text-orange-600' : 'text-green-600');
                                const stockText = isOutOfStock ? '❌ HẾT HÀNG' : `Tồn: ${stock}`;

                                return `
                                                                                                                                            <div class="px-3 py-2 ${bgClass} cursor-pointer border-b" onclick="selectPainting(${p.id}, ${idx})">
                                                                                                                                                <div class="flex justify-between items-start">
                                                                                                                                                    <div class="font-medium text-sm">${p.code} - ${p.name}</div>
                                                                                                                                                    <span class="text-xs ${stockColor} ml-2 whitespace-nowrap">${stockText}</span>
                                                                                                                                                </div>
                                                                                                                                                <div class="text-xs text-gray-500">USD: ${p.price_usd || 0} | VND: ${(p.price_vnd || 0).toLocaleString()}đ</div>
                                                                                                                                            </div>
                                                                                                                                        `;
                            }).join('');
                        }

                        // Add frames section
                        if (availableFrames.length > 0) {
                            html += '<div class="px-3 py-1 bg-gray-100 text-xs font-bold text-gray-600">KHUNG</div>';
                            html += availableFrames.map(f => `
                                                                                                                                        <div class="px-3 py-2 hover:bg-green-50 cursor-pointer border-b" onclick="selectFrame(${f.id}, ${idx})">
                                                                                                                                            <div class="font-medium text-sm">${f.name}</div>
                                                                                                                                            <div class="text-xs text-gray-500">Giá: ${(f.cost_price || 0).toLocaleString()}đ</div>
                                                                                                                                        </div>
                                                                                                                                    `).join('');
                        }

                        if (html) {
                            suggestions.innerHTML = html;
                            suggestions.classList.remove('hidden');
                        } else {
                            suggestions.classList.add('hidden');
                        }
                    })
                    .catch(error => {
                        console.error('Error searching items:', error);
                        suggestions.classList.add('hidden');
                    });
            }

            function showItemSuggestions(idx) {
                const input = document.getElementById(`item-search-${idx}`);

                if (input && input.value.length >= 1) {
                    filterItems(input.value, idx);
                }
            }

            function selectPainting(paintingId, idx) {
                fetch(`{{ route('sales.api.painting', '') }}/${paintingId}`)
                    .then(response => response.json())
                    .then(painting => {
                        // Clear frame selection
                        document.getElementById(`frame-id-${idx}`).value = '';

                        // Set painting data
                        document.getElementById(`painting-id-${idx}`).value = painting.id;
                        document.getElementById(`item-search-${idx}`).value = `${painting.code} - ${painting.name}`;
                        document.getElementById(`desc-${idx}`).value = painting.name;

                        const usdInput = document.querySelector(`.usd-${idx}`);
                        const vndInput = document.querySelector(`.vnd-${idx}`);
                        const currencySelect = document.querySelector(`select[name="items[${idx}][currency]"]`);

                        const hasUsd = painting.price_usd && parseFloat(painting.price_usd) > 0;
                        const hasVnd = painting.price_vnd && parseFloat(painting.price_vnd) > 0;

                        // Tự động chọn loại tiền dựa vào giá sản phẩm
                        if (hasUsd && hasVnd) {
                            if (currencySelect) { currencySelect.value = 'BOTH'; togCur(currencySelect, idx); }
                            if (usdInput) usdInput.value = parseFloat(painting.price_usd).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            if (vndInput) vndInput.value = parseInt(painting.price_vnd).toLocaleString('en-US');
                        } else if (hasUsd) {
                            if (currencySelect) { currencySelect.value = 'USD'; togCur(currencySelect, idx); }
                            if (usdInput) usdInput.value = parseFloat(painting.price_usd).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            if (vndInput) vndInput.value = '0';
                        } else if (hasVnd) {
                            if (currencySelect) { currencySelect.value = 'VND'; togCur(currencySelect, idx); }
                            if (usdInput) usdInput.value = '0.00';
                            if (vndInput) vndInput.value = parseInt(painting.price_vnd).toLocaleString('en-US');
                        } else {
                            if (currencySelect) { currencySelect.value = 'VND'; togCur(currencySelect, idx); }
                            if (usdInput) usdInput.value = '0.00';
                            if (vndInput) vndInput.value = '0';
                        }
                        if (currencySelect) {
                            currencySelect.value = 'VND';
                            // Trigger the togCur function to hide/show appropriate inputs
                            togCur(currencySelect, idx);
                        }

                        const imgUrl = painting.image ? `/storage/${painting.image}` : '/images/no-image.svg';
                        const imgElement = document.getElementById(`img-${idx}`);
                        imgElement.src = imgUrl;
                        if (typeof showImageModal === 'function') {
                            imgElement.onclick = () => showImageModal(imgUrl, painting.name);
                        }
                        imgElement.classList.add('cursor-pointer', 'hover:opacity-80', 'transition-opacity');

                        // Kiểm tra tồn kho
                        const stock = painting.quantity || 0;
                        const itemSearchInput = document.getElementById(`item-search-${idx}`);

                        if (stock <= 0) {
                            // Tranh hết hàng - cảnh báo
                            itemSearchInput.classList.add('border-red-500', 'bg-red-50');
                            itemSearchInput.title = '⚠️ Tranh này đã hết hàng!';

                            if (typeof showWarning === 'function') {
                                showWarning(itemSearchInput, '❌ Tranh "' + painting.name + '" đã HẾT HÀNG! Tồn kho: 0');
                            }

                            // Thêm badge hết hàng vào input
                            itemSearchInput.value = `${painting.code} - ${painting.name} [HẾT HÀNG]`;
                        } else if (stock < 5) {
                            // Sắp hết hàng - cảnh báo nhẹ
                            itemSearchInput.classList.add('border-orange-400', 'bg-orange-50');
                            itemSearchInput.title = 'Tranh này Còn: ' + stock;

                            if (typeof showWarning === 'function') {
                                showWarning(itemSearchInput, 'Tranh "' + painting.name + '" Còn: ' + stock);
                            }
                        } else {
                            itemSearchInput.classList.remove('border-red-500', 'bg-red-50', 'border-orange-400', 'bg-orange-50');
                            itemSearchInput.title = '';
                        }

                        document.getElementById(`item-suggestions-${idx}`).classList.add('hidden');
                        calc();
                    })
                    .catch(error => {
                        console.error('Error fetching painting:', error);
                    });
            }

            function selectFrame(frameId, idx) {
                fetch(`{{ route('frames.api.frame', '') }}/${frameId}`)
                    .then(response => response.json())
                    .then(frame => {
                        // Clear painting selection
                        document.getElementById(`painting-id-${idx}`).value = '';

                        // Set frame data
                        document.getElementById(`frame-id-${idx}`).value = frame.id;
                        document.getElementById(`item-search-${idx}`).value = frame.name;
                        document.getElementById(`desc-${idx}`).value = frame.name;

                        // Set price from frame cost_price
                        const vndInput = document.querySelector(`.vnd-${idx}`);
                        if (vndInput) {
                            const vndValue = parseInt(frame.cost_price) || 0;
                            vndInput.value = vndValue.toLocaleString('en-US');
                        }

                        // Set currency dropdown to VND and hide USD input
                        const currencySelect = document.querySelector(`select[name="items[${idx}][currency]"]`);
                        if (currencySelect) {
                            currencySelect.value = 'VND';
                            // Trigger the togCur function to hide/show appropriate inputs
                            togCur(currencySelect, idx);
                        }

                        // Clear image for frame
                        const imgElement = document.getElementById(`img-${idx}`);
                        imgElement.src = '/images/frame-placeholder.svg';

                        document.getElementById(`item-suggestions-${idx}`).classList.add('hidden');
                        calc();
                    })
                    .catch(error => {
                        console.error('Error fetching frame:', error);
                    });
            }

            function togCur(sel, i) {
                const cur = sel.value;
                const usdInput = document.getElementById(`usd-input-${i}`);
                const vndInput = document.getElementById(`vnd-input-${i}`);
                const discountUsdInput = document.getElementById(`discount-usd-${i}`);
                const discountVndInput = document.getElementById(`discount-vnd-${i}`);

                if (!usdInput || !vndInput) {
                    console.error('Inputs not found!');
                    return;
                }

                if (cur === 'USD') {
                    usdInput.classList.remove('hidden');
                    vndInput.classList.add('hidden');
                    // Hiện input giảm tiền USD, ẩn VND
                    if (discountUsdInput) discountUsdInput.classList.remove('hidden');
                    if (discountVndInput) discountVndInput.classList.add('hidden');
                } else if (cur === 'VND') {
                    usdInput.classList.add('hidden');
                    vndInput.classList.remove('hidden');
                    // Ẩn input giảm tiền USD, hiện VND
                    if (discountUsdInput) discountUsdInput.classList.add('hidden');
                    if (discountVndInput) discountVndInput.classList.remove('hidden');
                } else { // BOTH
                    usdInput.classList.remove('hidden');
                    vndInput.classList.remove('hidden');
                    // Hiện cả 2 input giảm tiền
                    if (discountUsdInput) discountUsdInput.classList.remove('hidden');
                    if (discountVndInput) discountVndInput.classList.remove('hidden');
                }

                calc();
            }

            // Format payment USD (với dấu phẩy, giữ nguyên vị trí cursor)
            function formatPaymentUSD(input) {
                // Lưu vị trí cursor
                const cursorPosition = input.selectionStart;
                const oldValue = input.value;
                const oldLength = oldValue.length;

                // Loại bỏ tất cả ký tự không hợp lệ
                let value = input.value.replace(/[^\d.]/g, '');
                const parts = value.split('.');

                // Chỉ cho phép 1 dấu chấm
                if (parts.length > 2) {
                    value = parts[0] + '.' + parts.slice(1).join('');
                    parts.length = 2;
                    parts[0] = value.split('.')[0];
                    parts[1] = value.split('.').slice(1).join('');
                }

                // Giới hạn 2 chữ số thập phân
                if (parts[1] && parts[1].length > 2) {
                    parts[1] = parts[1].substring(0, 2);
                }

                // Format phần nguyên với dấu phẩy
                if (parts[0]) {
                    parts[0] = parseInt(parts[0] || 0).toLocaleString('en-US');
                }

                // Ghép lại
                const newValue = parts.length > 1 ? parts[0] + '.' + (parts[1] || '') : parts[0];
                input.value = newValue;

                // Tính toán vị trí cursor mới
                const newLength = newValue.length;
                const diff = newLength - oldLength;
                let newCursorPosition = cursorPosition + diff;

                // Đảm bảo cursor không vượt quá độ dài
                if (newCursorPosition < 0) newCursorPosition = 0;
                if (newCursorPosition > newLength) newCursorPosition = newLength;

                // Khôi phục vị trí cursor
                input.setSelectionRange(newCursorPosition, newCursorPosition);

                // Lưu giá trị số thuần vào hidden input (không có dấu phẩy)
                const rawValue = newValue.replace(/,/g, '');
                const hiddenInput = document.getElementById('paid_usd');
                if (hiddenInput) {
                    hiddenInput.value = rawValue || '0';
                }

                // Tính tổng đã trả
                calcTotalPaid();
            }

            // Format payment VND
            function formatPaymentVND(input) {
                let value = input.value.replace(/[^\d]/g, '');

                // Lưu giá trị số thuần vào hidden input
                const hiddenInput = document.getElementById('paid_vnd');
                if (hiddenInput) {
                    hiddenInput.value = value || '0';
                }

                // Format và hiển thị
                if (value) {
                    input.value = parseInt(value).toLocaleString('vi-VN');
                }

                // Tính tổng đã trả
                calcTotalPaid();
            }
            // Calculate total paid (USD + VND converted)
            function calcTotalPaid() {
                const rateEl = document.getElementById('rate');
                const paidUsdEl = document.getElementById('paid_usd');
                const paidVndEl = document.getElementById('paid_vnd');
                const totalPaidDisplayEl = document.getElementById('total_paid_display');
                const totalPaidValueEl = document.getElementById('total_paid_value');
                const totalPaidUsdDisplayEl = document.getElementById('total_paid_usd_display');
                const paymentDetailEl = document.getElementById('payment_detail_text');
                const totalUsdEl = document.getElementById('total_usd');
                const totalVndEl = document.getElementById('total_vnd');

                if (!paidUsdEl || !paidVndEl || !totalPaidDisplayEl) return;

                // Lấy số tiền trả thêm
                const paidUsd = parseFloat(paidUsdEl.value) || 0;
                const paidVnd = parseFloat(paidVndEl.value) || 0;

                // Lấy tổng tiền
                const totalUsd = totalUsdEl ? (parseFloat(totalUsdEl.value.replace(/[^\d.]/g, '')) || 0) : 0;
                let totalVnd = totalVndEl ? (parseFloat(totalVndEl.value.replace(/[^\d]/g, '')) || 0) : 0;

                // Nếu là giá trị ước tính (quy đổi hiển thị), coi như không có nghĩa vụ thanh toán VND gốc
                if (totalVndEl && totalVndEl.dataset.isEstimate === 'true') {
                    totalVnd = 0;
                }

                // Xác định loại hóa đơn
                const hasUsdTotal = totalUsd > 0;
                const hasVndTotal = totalVnd > 0;

                // Lấy tỷ giá - Xóa tất cả ký tự không phải số
                const rate = rateEl ? (parseFloat(rateEl.value.replace(/[^\d]/g, '')) || 0) : 0;

                // LOGIC MỚI: Tính theo loại hóa đơn
                if (hasUsdTotal && !hasVndTotal) {
                    // A. Chỉ có USD - Quy đổi VND → USD (thanh toán chéo)
                    let convertedUsd = (rate > 0 && paidVnd > 0 ? paidVnd / rate : 0);
                    // Smart rounding: Nếu gần số nguyên (sai số < 0.05) thì làm tròn
                    if (Math.abs(convertedUsd - Math.round(convertedUsd)) < 0.05) {
                        convertedUsd = Math.round(convertedUsd);
                    }

                    const totalPaidUsd = paidUsd + convertedUsd;
                    totalPaidDisplayEl.value = '$' + totalPaidUsd.toFixed(2);
                    if (totalPaidValueEl) totalPaidValueEl.value = totalPaidUsd.toFixed(2);

                    if (totalPaidUsdDisplayEl && paidVnd > 0 && rate > 0) {
                        totalPaidUsdDisplayEl.textContent = '(' + paidVnd.toLocaleString('vi-VN') + 'đ ÷ ' + rate.toLocaleString('vi-VN') + ')';
                    } else if (totalPaidUsdDisplayEl) {
                        totalPaidUsdDisplayEl.textContent = '';
                    }

                } else if (hasVndTotal && !hasUsdTotal) {
                    // B. Chỉ có VND - Quy đổi USD → VND (thanh toán chéo)
                    const totalPaidVnd = paidVnd + (rate > 0 && paidUsd > 0 ? paidUsd * rate : 0);
                    totalPaidDisplayEl.value = totalPaidVnd.toLocaleString('vi-VN') + 'đ';
                    if (totalPaidValueEl) totalPaidValueEl.value = Math.round(totalPaidVnd);

                    if (totalPaidUsdDisplayEl && paidUsd > 0 && rate > 0) {
                        totalPaidUsdDisplayEl.textContent = '($' + paidUsd.toFixed(2) + ' × ' + rate.toLocaleString('vi-VN') + ')';
                    } else if (totalPaidUsdDisplayEl) {
                        totalPaidUsdDisplayEl.textContent = '';
                    }

                } else if (hasUsdTotal && hasVndTotal) {
                    // C. Có cả USD và VND - KHÔNG quy đổi, hiển thị riêng
                    totalPaidDisplayEl.value = '$' + paidUsd.toFixed(2) + ' + ' + paidVnd.toLocaleString('vi-VN') + 'đ';
                    // Set payment_amount = 1 để backend biết có thanh toán (giá trị thực tế lấy từ payment_usd và payment_vnd)
                    if (totalPaidValueEl) totalPaidValueEl.value = (paidUsd > 0 || paidVnd > 0) ? 1 : 0;

                    if (totalPaidUsdDisplayEl) {
                        totalPaidUsdDisplayEl.textContent = '';
                    }
                }

                // Hiển thị chi tiết thanh toán
                if (paymentDetailEl) {
                    let detailText = '';
                    if (paidUsd > 0 && paidVnd > 0) {
                        detailText = 'USD: $' + paidUsd.toFixed(2) + ', VND: ' + paidVnd.toLocaleString('vi-VN') + 'đ';
                    } else if (paidUsd > 0) {
                        detailText = 'USD: $' + paidUsd.toFixed(2);
                    } else if (paidVnd > 0) {
                        detailText = 'VND: ' + paidVnd.toLocaleString('vi-VN') + 'đ';
                    } else {
                        detailText = 'USD: $0.00, VND: 0đ';
                    }
                    paymentDetailEl.textContent = detailText;
                }

                // CẢNH BÁO TỶ GIÁ khi thanh toán chéo
                const warningDiv = document.getElementById('payment-warning');
                const warningText = document.getElementById('payment-warning-text');

                if (warningDiv && warningText) {
                    let showWarning = false;
                    let warningMessage = '';
                    let isError = false;

                    // Trường hợp 1: Hóa đơn USD, trả VND (USD-VND)
                    if (hasUsdTotal && !hasVndTotal && paidVnd > 0) {
                        showWarning = true;
                        if (rate <= 0) {
                            isError = true;
                            warningMessage = 'Vui lòng nhập tỷ giá để quy đổi VND → USD!';
                        } else {
                            warningMessage = 'Thanh toán chéo: Hóa đơn USD, trả VND. Tỷ giá áp dụng: ' + rate.toLocaleString('vi-VN') + ' VND/USD';
                        }
                    }

                    // Trường hợp 2: Hóa đơn VND, trả USD (VND-USD)
                    if (hasVndTotal && !hasUsdTotal && paidUsd > 0) {
                        showWarning = true;
                        if (rate <= 0) {
                            isError = true;
                            warningMessage = 'Vui lòng nhập tỷ giá để quy đổi USD → VND!';
                        } else {
                            warningMessage = 'Thanh toán chéo: Hóa đơn VND, trả USD. Tỷ giá áp dụng: ' + rate.toLocaleString('vi-VN') + ' VND/USD';
                        }
                    }

                    if (showWarning) {
                        warningText.textContent = warningMessage;
                        warningDiv.classList.remove('hidden');
                        if (isError) {
                            warningDiv.classList.remove('text-red-600', 'bg-red-100');
                            warningDiv.classList.add('text-orange-600', 'bg-orange-100');
                        } else {
                            warningDiv.classList.remove('text-orange-600', 'bg-orange-100');
                            warningDiv.classList.add('text-red-600', 'bg-red-100');
                        }
                    } else {
                        warningDiv.classList.add('hidden');
                    }
                }

                // Tính nợ
                calcDebt();
            }
            function calc() {

                let totalUsd = 0;
                let totalVnd = 0;

                const rows = document.querySelectorAll('#items-body tr');
                rows.forEach(row => {
                    const qtyInput = row.querySelector('input[name*="[quantity]"]');
                    if (!qtyInput) return;

                    const qty = parseInt(qtyInput.value) || 0;
                    const currency = row.querySelector('select[name*="[currency]"]').value;
                    const discountPercent = parseFloat(row.querySelector('input[name*="[discount_percent]"]').value) || 0;

                    if (currency === 'USD') {
                        const price = parseFloat(row.querySelector('input[name*="[price_usd]"]').value.replace(/,/g, '')) || 0;
                        const discAmount = parseFloat(row.querySelector('input[name*="[discount_amount_usd]"]').value.replace(/,/g, '')) || 0;
                        let sub = price * qty;
                        sub = sub * (1 - discountPercent / 100);
                        totalUsd += Math.max(0, sub - discAmount);
                    } else {
                        const price = parseInt(row.querySelector('input[name*="[price_vnd]"]').value.replace(/,/g, '')) || 0;
                        const discAmount = parseInt(row.querySelector('input[name*="[discount_amount_vnd]"]').value.replace(/,/g, '')) || 0;
                        let sub = price * qty;
                        sub = sub * (1 - discountPercent / 100);
                        totalVnd += Math.max(0, sub - discAmount);
                    }
                });

                // Sale Discount
                const saleDiscPercent = parseFloat(document.getElementById('discount').value) || 0;
                const saleDiscUsd = parseFloat(document.getElementById('discount_amount_usd').value.replace(/,/g, '')) || 0;
                const saleDiscVnd = parseInt(document.getElementById('discount_amount_vnd').value.replace(/,/g, '')) || 0;

                // Shipping Fee
                const shippingFeeUsd = parseFloat(document.getElementById('shipping_fee_usd').value.replace(/,/g, '')) || 0;
                const shippingFeeVnd = parseInt(document.getElementById('shipping_fee_vnd').value.replace(/,/g, '')) || 0;

                totalUsd = Math.max(0, (totalUsd * (1 - saleDiscPercent / 100)) - saleDiscUsd) + shippingFeeUsd;
                totalVnd = Math.max(0, (totalVnd * (1 - saleDiscPercent / 100)) - saleDiscVnd) + shippingFeeVnd;

                // Lấy tỷ giá hiện tại
                const rateEl = document.getElementById('rate');
                const exchangeRate = parseFloat(rateEl?.value.replace(/[^\d]/g, '') || 0);

                document.getElementById('total_usd').value = '$' + totalUsd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                const totalVndEl = document.getElementById('total_vnd');
                totalVndEl.value = totalVnd.toLocaleString('vi-VN') + 'đ';

                // Clear obsolete visual cues if any
                if (totalVndEl.dataset.isEstimate) {
                    delete totalVndEl.dataset.isEstimate;
                    totalVndEl.classList.remove('text-gray-500', 'italic');
                    totalVndEl.classList.add('font-bold', 'text-green-600');
                }

                // LOGIC HIỂN THỊ TỔNG QUY ĐỔI MỚI
                const convertedWrapper = document.getElementById('converted-total-wrapper');
                const convertedValue = document.getElementById('converted_total_vnd');

                if (totalUsd > 0 && exchangeRate > 0) {
                    const totalConverted = totalVnd + (totalUsd * exchangeRate);
                    convertedValue.textContent = Math.round(totalConverted).toLocaleString('vi-VN') + 'đ';
                    // Update explanation explicitly
                    const explanation = convertedWrapper.querySelector('.italic');
                    if (explanation) {
                        explanation.textContent = `(${totalUsd.toLocaleString('en-US')} USD x ${exchangeRate.toLocaleString('vi-VN')} + ${totalVnd.toLocaleString('vi-VN')} VND)`;
                    }

                    convertedWrapper.classList.remove('hidden');
                } else {
                    if (convertedWrapper) convertedWrapper.classList.add('hidden');
                }

                calcTotalPaid();
            }

            function calcDebt() {
                const totalUsdEl = document.getElementById('total_usd');
                const totalVndEl = document.getElementById('total_vnd');
                const debtEl = document.getElementById('debt');
                const rateEl = document.getElementById('rate');
                const paidUsdEl = document.getElementById('paid_usd');
                const paidVndEl = document.getElementById('paid_vnd');

                if (!totalUsdEl || !totalVndEl || !debtEl || !rateEl) return;

                // Lấy tổng tiền
                const totalUsd = parseFloat(totalUsdEl.value.replace(/[^\d.]/g, '')) || 0;
                let totalVnd = parseFloat(totalVndEl.value.replace(/[^\d]/g, '')) || 0;

                // Xác định loại hóa đơn
                const hasUsdTotal = totalUsd > 0;
                const hasVndTotal = totalVnd > 0;

                // Lấy tỷ giá
                const rate = parseFloat(rateEl.value.replace(/[^\d]/g, '')) || 0;

                @if($sale->sale_status === 'completed')
                    // Phiếu đã duyệt: Lấy số tiền đã trả từ database
                    const currentPaidUsd = {{ $sale->paid_usd }};
                    const currentPaidVnd = {{ $sale->paid_vnd }};

                    // Lấy số tiền trả thêm
                    const additionalPaidUsd = paidUsdEl ? (parseFloat(paidUsdEl.value) || 0) : 0;
                    const additionalPaidVnd = paidVndEl ? (parseFloat(paidVndEl.value) || 0) : 0;

                    // Tính tổng đã trả
                    const totalPaidUsd = currentPaidUsd + additionalPaidUsd;
                    const totalPaidVnd = currentPaidVnd + additionalPaidVnd;

                    // Khai báo biến trước để dùng trong warning check
                    let totalPaidInUsd = 0;
                    let totalPaidInVnd = 0;

                    // LOGIC MỚI: Tính nợ theo loại hóa đơn
                    if (hasUsdTotal && !hasVndTotal) {
                        // A. Hóa đơn USD: CHỈ quy đổi tiền TRẢ THÊM (additionalPaidVnd), KHÔNG quy đổi tiền cũ
                        // Tiền cũ đã được lưu đúng theo tỷ giá lúc trả
                        let convertedAdditionalUsd = (rate > 0 && additionalPaidVnd > 0 ? additionalPaidVnd / rate : 0);
                        // Smart rounding
                        if (Math.abs(convertedAdditionalUsd - Math.round(convertedAdditionalUsd)) < 0.05) {
                            convertedAdditionalUsd = Math.round(convertedAdditionalUsd);
                        }

                        const additionalPaidInUsd = additionalPaidUsd + convertedAdditionalUsd;
                        totalPaidInUsd = currentPaidUsd + additionalPaidInUsd;
                        const debtUsd = Math.max(0, totalUsd - totalPaidInUsd);
                        debtEl.value = '$' + debtUsd.toFixed(2);

                    } else if (hasVndTotal && !hasUsdTotal) {
                        // B. Hóa đơn VND: CHỈ quy đổi tiền TRẢ THÊM (additionalPaidUsd), KHÔNG quy đổi tiền cũ
                        const additionalPaidInVnd = additionalPaidVnd + (rate > 0 && additionalPaidUsd > 0 ? additionalPaidUsd * rate : 0);
                        totalPaidInVnd = currentPaidVnd + additionalPaidInVnd;
                        const debtVnd = Math.max(0, totalVnd - totalPaidInVnd);
                        debtEl.value = Math.round(debtVnd).toLocaleString('vi-VN') + 'đ';

                    } else if (hasUsdTotal && hasVndTotal) {
                        // C. Có cả USD và VND - Tính riêng từng loại
                        const debtUsd = Math.max(0, totalUsd - totalPaidUsd);
                        const debtVnd = Math.max(0, totalVnd - totalPaidVnd);

                        if (debtUsd > 0 && debtVnd > 0) {
                            debtEl.value = '$' + debtUsd.toFixed(2) + ' + ' + Math.round(debtVnd).toLocaleString('vi-VN') + 'đ';
                        } else if (debtUsd > 0) {
                            debtEl.value = '$' + debtUsd.toFixed(2);
                        } else if (debtVnd > 0) {
                            debtEl.value = Math.round(debtVnd).toLocaleString('vi-VN') + 'đ';
                        } else {
                            debtEl.value = '$0.00';
                        }
                    }

                    // Warning if overpaid
                    const isOverpaid = (hasUsdTotal && !hasVndTotal && totalPaidInUsd > totalUsd) ||
                        (hasVndTotal && !hasUsdTotal && totalPaidInVnd > totalVnd) ||
                        (hasUsdTotal && hasVndTotal && (totalPaidUsd > totalUsd || totalPaidVnd > totalVnd));

                    if (isOverpaid && (additionalPaidUsd > 0 || additionalPaidVnd > 0)) {
                        const paidDisplay = document.getElementById('total_paid_display');
                        if (paidDisplay) {
                            paidDisplay.classList.add('border-orange-500', 'bg-orange-100');
                            paidDisplay.title = 'Số tiền trả vượt quá tổng tiền hóa đơn';
                            setTimeout(() => {
                                paidDisplay.classList.remove('border-orange-500', 'bg-orange-100');
                                paidDisplay.title = '';
                            }, 3000);
                        }
                    }
                @else
                                                                                                                                                                                    // Phiếu pending: Chỉ có số tiền trả ban đầu
                                                                                                                                                                                    const paidUsdValue = paidUsdEl ? (parseFloat(paidUsdEl.value) || 0) : 0;
                    const paidVndValue = paidVndEl ? (parseFloat(paidVndEl.value) || 0) : 0;

                    // Khai báo biến trước
                    let totalPaidInUsd = 0;
                    let totalPaidInVnd = 0;

                    // LOGIC MỚI: Tính nợ theo loại hóa đơn
                    if (hasUsdTotal && !hasVndTotal) {
                        // A. Hóa đơn USD: Quy đổi VND → USD nếu có thanh toán chéo
                        let convertedUsd = (rate > 0 && paidVndValue > 0 ? paidVndValue / rate : 0);
                        if (Math.abs(convertedUsd - Math.round(convertedUsd)) < 0.05) {
                            convertedUsd = Math.round(convertedUsd);
                        }
                        totalPaidInUsd = paidUsdValue + convertedUsd;
                        const debtUsd = Math.max(0, totalUsd - totalPaidInUsd);

                        debtEl.value = '$' + debtUsd.toFixed(2);

                    } else if (hasVndTotal && !hasUsdTotal) {
                        // B. Hóa đơn VND: Quy đổi USD → VND nếu có thanh toán chéo
                        totalPaidInVnd = paidVndValue + (rate > 0 && paidUsdValue > 0 ? paidUsdValue * rate : 0);
                        const debtVnd = Math.max(0, totalVnd - totalPaidInVnd);
                        debtEl.value = Math.round(debtVnd).toLocaleString('vi-VN') + 'đ';

                    } else if (hasUsdTotal && hasVndTotal) {
                        // C. Có cả USD và VND - Tính riêng từng loại
                        const debtUsd = Math.max(0, totalUsd - paidUsdValue);
                        const debtVnd = Math.max(0, totalVnd - paidVndValue);

                        if (debtUsd > 0 && debtVnd > 0) {
                            debtEl.value = '$' + debtUsd.toFixed(2) + ' + ' + Math.round(debtVnd).toLocaleString('vi-VN') + 'đ';
                        } else if (debtUsd > 0) {
                            debtEl.value = '$' + debtUsd.toFixed(2);
                        } else if (debtVnd > 0) {
                            debtEl.value = Math.round(debtVnd).toLocaleString('vi-VN') + 'đ';
                        } else {
                            debtEl.value = '$0.00';
                        }
                    }

                    // Warning if overpaid
                    const isOverpaid = (hasUsdTotal && !hasVndTotal && totalPaidInUsd > totalUsd) ||
                        (hasVndTotal && !hasUsdTotal && totalPaidInVnd > totalVnd) ||
                        (hasUsdTotal && hasVndTotal && (paidUsdValue > totalUsd || paidVndValue > totalVnd));

                    if (isOverpaid && (paidUsdValue > 0 || paidVndValue > 0)) {
                        const paidDisplay = document.getElementById('total_paid_display');
                        if (paidDisplay) {
                            paidDisplay.classList.add('border-orange-500', 'bg-orange-100');
                            paidDisplay.title = 'Số tiền trả vượt quá tổng tiền hóa đơn';
                            setTimeout(() => {
                                paidDisplay.classList.remove('border-orange-500', 'bg-orange-100');
                                paidDisplay.title = '';
                            }, 3000);
                        }
                    }
                @endif
                                                                                                                        }
            function loadCurrentDebt(customerId) {
                if (customerId) {
                    fetch(`/sales/api/customers/${customerId}/debt`)
                        .then(response => response.json())
                        .then(data => {
                            const debtUsd = data.total_debt_usd || 0;
                            const debtVnd = data.total_debt_vnd || 0;

                            // Hiển thị riêng USD và VND
                            let debtDisplay = '';
                            if (debtUsd > 0 && debtVnd > 0) {
                                debtDisplay = '$' + debtUsd.toLocaleString('en-US', { minimumFractionDigits: 2 }) + ' + ' + Math.round(debtVnd).toLocaleString('vi-VN') + 'đ';
                            } else if (debtUsd > 0) {
                                debtDisplay = '$' + debtUsd.toLocaleString('en-US', { minimumFractionDigits: 2 });
                            } else if (debtVnd > 0) {
                                debtDisplay = Math.round(debtVnd).toLocaleString('vi-VN') + 'đ';
                            } else {
                                debtDisplay = '0đ';
                            }

                            document.getElementById('current_debt').value = debtDisplay;
                        })
                        .catch(error => {
                            console.error('Error loading customer debt:', error);
                            document.getElementById('current_debt').value = '0đ';
                        });
                } else {
                    document.getElementById('current_debt').value = '0đ';
                }
            }

            // Generate invoice code from API
            function generateInvoiceCode() {
                const showroomSelect = document.getElementById('showroom_id');
                const showroomId = showroomSelect.value;

                if (!showroomId) {
                    alert('Vui lòng chọn showroom trước!');
                    showroomSelect.focus();
                    return;
                }

                // Show loading
                const invoiceInput = document.getElementById('invoice_code');
                invoiceInput.value = 'Đang tạo...';
                invoiceInput.disabled = true;

                // Call API to generate invoice code
                fetch(`{{ route('sales.api.generate-invoice-code') }}?showroom_id=${showroomId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            invoiceInput.value = data.invoice_code;
                        } else {
                            alert('Lỗi: ' + data.message);
                            invoiceInput.value = '';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Có lỗi xảy ra khi tạo mã hóa đơn');
                        invoiceInput.value = '';
                    })
                    .finally(() => {
                        invoiceInput.disabled = false;
                    });
            }

            // Load existing sale items
            function loadExistingItems() {
                let displayIndex = 0;
                saleItems.forEach((item, originalIndex) => {
                    // Bỏ qua sản phẩm đã trả
                    if (item.is_returned) {
                        return; // Skip this item
                    }

                    const index = displayIndex;
                    addItem();

                    // Set painting or frame
                    if (item.painting_id) {
                        document.getElementById(`painting-id-${index}`).value = item.painting_id;
                        document.getElementById(`frame-id-${index}`).value = ''; // Clear frame
                        document.getElementById(`item-search-${index}`).value = `${item.painting?.code || ''} - ${item.description}`;
                        document.getElementById(`desc-${index}`).value = item.description;

                        if (item.painting?.image) {
                            const imgElement = document.getElementById(`img-${index}`);
                            imgElement.src = `/storage/${item.painting.image}`;
                            imgElement.onclick = () => showImageModal(`/storage/${item.painting.image}`, item.description);
                            imgElement.classList.add('cursor-pointer', 'hover:opacity-80', 'transition-opacity');
                        }

                        // Display painting details
                        const detailsDiv = document.getElementById(`item-details-${index}`);
                        if (detailsDiv && item.painting) {
                            let detailsHTML = '';
                            if (item.painting.code) detailsHTML += `<div><span class="font-semibold">Mã:</span> ${item.painting.code}</div>`;
                            if (item.painting.artist) detailsHTML += `<div><span class="font-semibold">Họa sĩ:</span> ${item.painting.artist}</div>`;
                            if (item.painting.material) detailsHTML += `<div><span class="font-semibold">Chất liệu:</span> ${item.painting.material}</div>`;
                            if (item.painting.width && item.painting.height) detailsHTML += `<div><span class="font-semibold">Kích thước:</span> ${item.painting.width} x ${item.painting.height} cm</div>`;
                            if (item.painting.paint_year) detailsHTML += `<div><span class="font-semibold">Năm:</span> ${item.painting.paint_year}</div>`;

                            if (detailsHTML) {
                                detailsDiv.innerHTML = detailsHTML;
                                detailsDiv.classList.remove('hidden');
                            }
                        }
                    } else if (item.frame_id) {
                        document.getElementById(`frame-id-${index}`).value = item.frame_id;
                        document.getElementById(`painting-id-${index}`).value = ''; // Clear painting
                        document.getElementById(`item-search-${index}`).value = item.frame?.name || item.description;
                        document.getElementById(`desc-${index}`).value = item.description;
                        document.getElementById(`img-${index}`).src = '/images/frame-placeholder.svg';
                    } else {
                        document.getElementById(`item-search-${index}`).value = item.description;
                        document.getElementById(`desc-${index}`).value = item.description;
                    }

                    // Set other fields
                    document.querySelector(`[name="items[${index}][quantity]"]`).value = item.quantity;
                    document.querySelector(`[name="items[${index}][currency]"]`).value = item.currency;

                    // Format prices
                    const usdInput = document.querySelector(`[name="items[${index}][price_usd]"]`);
                    const vndInput = document.querySelector(`[name="items[${index}][price_vnd]"]`);
                    if (usdInput && vndInput) {
                        const usdVal = parseFloat(item.price_usd) || 0;
                        const vndVal = parseInt(item.price_vnd) || 0;
                        usdInput.value = usdVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        vndInput.value = vndVal.toLocaleString('en-US');
                    }

                    document.querySelector(`[name="items[${index}][discount_percent]"]`).value = Math.round(item.discount_percent || 0);

                    // Add Populate Discount Amount
                    if (document.getElementById(`discount-usd-${index}`)) {
                        document.getElementById(`discount-usd-${index}`).value = (parseFloat(item.discount_amount_usd) || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
                    }
                    if (document.getElementById(`discount-vnd-${index}`)) {
                        document.getElementById(`discount-vnd-${index}`).value = (parseInt(item.discount_amount_vnd) || 0).toLocaleString('en-US');
                    }

                    // Set currency display
                    togCur(document.querySelector(`[name="items[${index}][currency]"]`), index);

                    displayIndex++; // Tăng index cho item tiếp theo
                });
            }

            // Before form submit, validate and remove formatting
            document.getElementById('sales-form').addEventListener('submit', function (e) {
                // Allow form submission - validation is done in confirmUpdateOrder
                // This handler is kept for any direct form submissions

                // VALIDATION: Kiểm tra có ít nhất 1 sản phẩm với tranh HOẶC khung được chọn
                const rows = document.querySelectorAll('#items-body tr');
                let hasValidProduct = false;

                rows.forEach((row, index) => {
                    const paintingInput = row.querySelector('input[name*="[painting_id]"]');
                    const paintingId = paintingInput ? paintingInput.value : '';
                    const frameInput = row.querySelector('input[name*="[frame_id]"]');
                    const frameId = frameInput ? frameInput.value : '';
                    const qtyInput = row.querySelector('input[name*="[quantity]"]');
                    const qty = qtyInput ? (parseInt(qtyInput.value) || 0) : 0;

                    // Hợp lệ nếu có tranh HOẶC có khung, và số lượng > 0
                    if ((paintingId || frameId) && qty > 0) {
                        hasValidProduct = true;
                    }
                });

                if (!hasValidProduct) {
                    e.preventDefault();
                    showNotification('Vui lòng chọn ít nhất 1 sản phẩm (tranh/khung) trước khi lưu!', 'error');
                    document.querySelector('#items-body')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
            });

            document.addEventListener('DOMContentLoaded', () => {
                loadExistingItems();
                calc();
                calcTotalPaid(); // Tính tổng đã trả
                calcDebt(); // Tính còn nợ ngay khi load trang

                // Load công nợ hiện tại của khách hàng
                const customerId = document.getElementById('customer_id').value;
                if (customerId) {
                    loadCurrentDebt(customerId);
                }

                // Auto-generate invoice code when showroom changes
                const showroomSelect = document.getElementById('showroom_id');
                if (showroomSelect) {
                    showroomSelect.addEventListener('change', function () {
                        if (this.value) {
                            generateInvoiceCode();
                        }
                    });
                }

                // Form validation và unformat trước khi submit
                const form = document.getElementById('sales-form');
                if (form) {
                    form.addEventListener('submit', function (e) {
                        // Unformat tất cả giá USD và VND của items trước khi submit
                        const priceUsdInputs = document.querySelectorAll('input[name*="[price_usd]"]');
                        const priceVndInputs = document.querySelectorAll('input[name*="[price_vnd]"]');

                        priceUsdInputs.forEach(input => {
                            input.value = unformatNumber(input.value);
                        });

                        priceVndInputs.forEach(input => {
                            input.value = unformatNumber(input.value);
                        });

                        document.querySelectorAll('[name*="shipping_fee_"]').forEach(input => {
                            input.value = unformatNumber(input.value);
                        });

                        // Validation - LOGIC MỚI
                        const totalUsdEl = document.getElementById('total_usd');
                        const totalVndEl = document.getElementById('total_vnd');
                        const paidUsdHiddenEl = document.getElementById('paid_usd');
                        const paidVndHiddenEl = document.getElementById('paid_vnd');

                        if (!totalUsdEl || !totalVndEl || !paidUsdHiddenEl || !paidVndHiddenEl) return;

                        // Lấy tổng tiền
                        const totalUsd = parseFloat(totalUsdEl.value.replace(/[^\d.]/g, '')) || 0;
                        const totalVnd = parseFloat(totalVndEl.value.replace(/[^\d]/g, '')) || 0;

                        // Lấy số tiền trả
                        const paidUsd = parseFloat(paidUsdHiddenEl.value) || 0;
                        const paidVnd = parseFloat(paidVndHiddenEl.value) || 0;

                        // Xác định loại hóa đơn
                        const hasUsdTotal = totalUsd > 0;
                        const hasVndTotal = totalVnd > 0;

                        // Lấy tỷ giá
                        const rateEl = document.getElementById('rate');
                        const rate = rateEl ? (parseFloat(rateEl.value.replace(/[^\d]/g, '')) || 0) : 0;

                        // Validation: CHỈ áp dụng cho phiếu có CẢ USD VÀ VND
                        if (hasUsdTotal && hasVndTotal) {
                            @if($sale->sale_status === 'completed')
                                // Phiếu đã duyệt: kiểm tra trả thêm không vượt quá nợ còn lại
                                const currentPaidUsd = {{ $sale->paid_usd }};
                                const currentPaidVnd = {{ $sale->paid_vnd }};
                                const debtUsd = totalUsd - currentPaidUsd;
                                const debtVnd = totalVnd - currentPaidVnd;

                                const tolerance = 0.01; // Sai số USD

                                if (paidUsd > debtUsd + tolerance) {
                                    e.preventDefault();
                                    alert('Số tiền USD trả thêm ($' + paidUsd.toFixed(2) + ') vượt quá nợ USD còn lại ($' + debtUsd.toFixed(2) + ')');
                                    return false;
                                }

                                if (paidVnd > debtVnd + 1) { // Tolerance 1 VND
                                    e.preventDefault();
                                    alert('Số tiền VND trả thêm (' + paidVnd.toLocaleString('vi-VN') + 'đ) vượt quá nợ VND còn lại (' + debtVnd.toLocaleString('vi-VN') + 'đ)');
                                    return false;
                                }
                            @else
                                                                                                                                                                                            // Phiếu pending: kiểm tra không vượt quá tổng
                                                                                                                                                                                            const tolerance = 0.01; // Sai số USD

                                if (paidUsd > totalUsd + tolerance) {
                                    e.preventDefault();
                                    alert('Số tiền USD thanh toán ($' + paidUsd.toFixed(2) + ') vượt quá tổng USD ($' + totalUsd.toFixed(2) + ')');
                                    return false;
                                }

                                if (paidVnd > totalVnd + 1) { // Tolerance 1 VND
                                    e.preventDefault();
                                    alert('Số tiền VND thanh toán (' + paidVnd.toLocaleString('vi-VN') + 'đ) vượt quá tổng VND (' + totalVnd.toLocaleString('vi-VN') + 'đ)');
                                    return false;
                                }
                            @endif
                                                                                                                                    }
                    });
                }
            });
            // Show exchange rate info modal
            function showExchangeRateInfo() {
                const modal = document.getElementById('exchangeRateInfoModal');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeExchangeRateInfo() {
                const modal = document.getElementById('exchangeRateInfoModal');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        </script>

        <!-- Exchange Rate Info Modal -->
        <div id="exchangeRateInfoModal" class="hidden fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50"
            onclick="closeExchangeRateInfo()">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 overflow-hidden" onclick="event.stopPropagation()">
                <!-- Header -->
                <div class="bg-blue-600 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="bg-white bg-opacity-20 rounded-full p-3 mr-3">
                                <i class="fas fa-info-circle text-2xl"></i>
                            </div>
                            <h3 class="text-xl font-bold">Lưu ý về Tỷ giá</h3>
                        </div>
                        <button onclick="closeExchangeRateInfo()"
                            class="text-white hover:bg-white hover:bg-opacity-20 rounded-full p-2 transition-all">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-3">
                    <!-- USD Payment -->
                    <div class="bg-blue-50 rounded-lg p-4 border-l-4 border-blue-500">
                        <div class="flex items-start">
                            <div
                                class="bg-blue-500 text-white rounded-full w-8 h-8 flex items-center justify-center mr-3 mt-0.5 flex-shrink-0">
                                <i class="fas fa-dollar-sign text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800 mb-1">Khi trả USD</h4>
                                <p class="text-sm text-gray-600">Không áp dụng tỷ giá, trừ trực tiếp vào công nợ USD, giá VND
                                    hiện tại là giá trị áp dụng với tỉ giá cũ nên không được chính xác (có sai số)</p>
                            </div>
                        </div>
                    </div>

                    <!-- VND Payment -->
                    <div class="bg-blue-50 rounded-lg p-4 border-l-4 border-blue-500">
                        <div class="flex items-start">
                            <div
                                class="bg-blue-500 text-white rounded-full w-8 h-8 flex items-center justify-center mr-3 mt-0.5 flex-shrink-0">
                                <i class="fas fa-dong-sign text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800 mb-1">Khi trả VND</h4>
                                <p class="text-sm text-gray-600">Áp dụng tỷ giá hiện tại để quy đổi sang USD</p>
                            </div>
                        </div>
                    </div>

                    <!-- Important Note -->
                    <div class="bg-amber-50 rounded-lg p-4 border-l-4 border-amber-500">
                        <div class="flex items-start">
                            <div
                                class="bg-amber-500 text-white rounded-full w-8 h-8 flex items-center justify-center mr-3 mt-0.5 flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800 mb-1">Lưu ý quan trọng</h4>
                                <p class="text-sm text-gray-600">Nếu bạn trả USD thì số tiền VND hiển thị chỉ là <span
                                        class="font-semibold">tham khảo</span> (dùng tỷ giá gốc), không phải số tiền thực tế
                                    thanh toán.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 flex justify-end">
                    <button onclick="closeExchangeRateInfo()"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-all">
                        <i class="fas fa-check mr-2"></i>Đã hiểu
                    </button>
                </div>
            </div>
        </div>

        <script>
            // Show notification function
            function showNotification(message, type = 'info') {
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transform transition-all duration-300 ${type === 'error' ? 'bg-red-500 text-white' :
                    type === 'success' ? 'bg-green-500 text-white' :
                        type === 'warning' ? 'bg-yellow-500 text-white' :
                            'bg-blue-500 text-white'
                    }`;
                notification.innerHTML = `
                                                                                                                                <div class="flex items-center">
                                                                                                                                    <i class="fas ${type === 'error' ? 'fa-exclamation-circle' :
                        type === 'success' ? 'fa-check-circle' :
                            type === 'warning' ? 'fa-exclamation-triangle' :
                                'fa-info-circle'
                    } mr-2"></i>
                                                                                                                                    <span>${message}</span>
                                                                                                                                </div>
                                                                                                                            `;
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.classList.add('opacity-0', 'translate-x-full');
                    setTimeout(() => notification.remove(), 300);
                }, 4000);
            }

            // Confirm update order function
            function confirmUpdateOrder() {
                const form = document.getElementById('sales-form');

                // VALIDATION: Kiểm tra có ít nhất 1 sản phẩm với tranh HOẶC khung được chọn
                const rows = document.querySelectorAll('#items-body tr');
                let hasValidProduct = false;
                let productCount = 0;

                rows.forEach((row, index) => {
                    const paintingInput = row.querySelector('input[name*="[painting_id]"]');
                    const paintingId = paintingInput ? paintingInput.value : '';
                    const frameInput = row.querySelector('input[name*="[frame_id]"]');
                    const frameId = frameInput ? frameInput.value : '';
                    const qtyInput = row.querySelector('input[name*="[quantity]"]');
                    const qty = qtyInput ? (parseInt(qtyInput.value) || 0) : 0;

                    if ((paintingId || frameId) && qty > 0) {
                        hasValidProduct = true;
                        productCount++;
                    }
                });

                if (!hasValidProduct) {
                    showNotification('Vui lòng chọn ít nhất 1 sản phẩm (tranh/khung) trước khi lưu!', 'error');
                    document.querySelector('#items-body')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }

                // Kiểm tra các trường bắt buộc khác
                const showroomEl = document.getElementById('showroom_id');
                if (showroomEl && !showroomEl.value) {
                    showNotification('Vui lòng chọn Showroom!', 'error');
                    showroomEl.focus();
                    return false;
                }

                const customerNameEl = document.getElementById('customer_name');
                if (customerNameEl && !customerNameEl.value.trim()) {
                    showNotification('Vui lòng nhập tên khách hàng!', 'error');
                    customerNameEl.focus();
                    return false;
                }

                // Build order summary for confirmation
                const customerName = customerNameEl.value;
                const invoiceCode = document.getElementById('invoice_code').value;
                const totalUsdEl = document.getElementById('total_usd');
                const totalVndEl = document.getElementById('total_vnd');
                const totalUsd = totalUsdEl ? totalUsdEl.value : '0';
                const totalVnd = totalVndEl ? totalVndEl.value : '0đ';

                let summaryHtml = `
                                                                                                                                <div class="space-y-2">
                                                                                                                                    <div class="flex justify-between">
                                                                                                                                        <span class="text-gray-600">Số hóa đơn:</span>
                                                                                                                                        <span class="font-medium text-blue-600">${invoiceCode}</span>
                                                                                                                                    </div>
                                                                                                                                    <div class="flex justify-between">
                                                                                                                                        <span class="text-gray-600">Khách hàng:</span>
                                                                                                                                        <span class="font-medium">${customerName}</span>
                                                                                                                                    </div>
                                                                                                                                    <div class="flex justify-between">
                                                                                                                                        <span class="text-gray-600">Số sản phẩm:</span>
                                                                                                                                        <span class="font-medium">${productCount} sản phẩm</span>
                                                                                                                                    </div>
                                                                                                                                    <div class="border-t pt-2 mt-2">
                                                                                                                                        <div class="flex justify-between text-blue-600">
                                                                                                                                            <span>Tổng USD:</span>
                                                                                                                                            <span class="font-bold">${totalUsd}</span>
                                                                                                                                        </div>
                                                                                                                                        <div class="flex justify-between text-green-600">
                                                                                                                                            <span>Tổng VND:</span>
                                                                                                                                            <span class="font-bold">${totalVnd}</span>
                                                                                                                                        </div>
                                                                                                                                    </div>
                                                                                                                                </div>
                                                                                                                            `;

                document.getElementById('confirm-edit-summary').innerHTML = summaryHtml;

                // Show confirmation modal
                showConfirmModal('confirm-edit-modal', {
                    title: 'Xác nhận cập nhật hóa đơn',
                    message: 'Vui lòng kiểm tra thông tin hóa đơn trước khi cập nhật:',
                    onConfirm: function () {
                        submitEditForm();
                    }
                });
            }

            // Submit form after confirmation
            function submitEditForm() {
                const form = document.getElementById('sales-form');

                // Unformat all values before submit
                document.getElementById('rate').value = unformatNumber(document.getElementById('rate').value);

                const paidEl = document.getElementById('paid');
                if (paidEl) paidEl.value = unformatNumber(paidEl.value);

                document.querySelectorAll('[name*="[price_usd]"]').forEach(input => {
                    input.value = unformatNumber(input.value);
                });
                document.querySelectorAll('[name*="[price_vnd]"]').forEach(input => {
                    input.value = unformatNumber(input.value);
                });

                // Convert BOTH to USD or VND
                document.querySelectorAll('[name*="[currency]"]').forEach(select => {
                    if (select.value === 'BOTH') {
                        const row = select.closest('tr');
                        const usdVal = parseFloat(unformatNumber(row.querySelector('[name*="[price_usd]"]').value)) || 0;
                        select.value = (usdVal > 0) ? 'USD' : 'VND';
                    }
                });

                form.submit();
            }
        </script>

    @endpush
@endsection