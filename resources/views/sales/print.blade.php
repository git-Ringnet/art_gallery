<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn {{ $sale['id'] }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            .print-area { width: 100%; }
            body { margin: 0; padding: 20px; }
        }
        @page {
            size: A4;
            margin: 1cm;
        }
    </style>
</head>
<body class="bg-white">
    <!-- Print Buttons -->
    <div class="no-print fixed top-4 right-4 space-x-2 z-50">
        <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-print mr-2"></i>In hóa đơn
        </button>
        <button onclick="window.close()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
            <i class="fas fa-times mr-2"></i>Đóng
        </button>
    </div>

    <!-- Invoice Content -->
    <div class="print-area max-w-4xl mx-auto bg-white p-8">
        <!-- Header -->
        <div class="flex justify-between items-start mb-8">
            <div class="flex items-center space-x-4">
                <img src="https://via.placeholder.com/80x80/4F46E5/FFFFFF?text=Logo" alt="logo" class="w-20 h-20 rounded-lg" />
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">HÓA ĐƠN BÁN HÀNG</h1>
                    <p class="text-lg text-gray-600">Mã HD: <span class="font-semibold text-blue-600">{{ $sale->invoice_code }}</span></p>
                    <p class="text-sm text-gray-600">Ngày: {{ $sale->sale_date->format('d/m/Y') }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="font-bold text-lg">{{ $sale->showroom->name }}</p>
                <p class="text-sm text-gray-600">{{ $sale->showroom->address }}</p>
                <p class="text-sm text-gray-600">{{ $sale->showroom->phone }}</p>
                <div class="mt-2 text-xs text-gray-500">
                    <p>Nhân viên: {{ $sale->user ? $sale->user->name : 'Chưa xác định' }}</p>
                    <p>Trạng thái: 
                        @if($sale->payment_status == 'paid')
                            <span class="text-green-600 font-semibold">Đã thanh toán</span>
                        @elseif($sale->payment_status == 'partial')
                            <span class="text-yellow-600 font-semibold">Thanh toán một phần</span>
                        @else
                            <span class="text-red-600 font-semibold">Chưa thanh toán</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Customer Info -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-semibold text-lg mb-3 text-gray-800">Thông tin khách hàng</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Tên khách hàng:</p>
                    <p class="font-medium">{{ $sale->customer->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Số điện thoại:</p>
                    <p class="font-medium">{{ $sale->customer->phone }}</p>
                </div>
                @if($sale->customer->email)
                <div>
                    <p class="text-sm text-gray-600">Email:</p>
                    <p class="font-medium">{{ $sale->customer->email }}</p>
                </div>
                @endif
                @if($sale->customer->address)
                <div>
                    <p class="text-sm text-gray-600">Địa chỉ:</p>
                    <p class="font-medium">{{ $sale->customer->address }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Items Table -->
        <div class="mb-6">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-100 border-b-2 border-gray-300">
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">#</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Sản phẩm</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">SL</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Đơn giá</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->saleItems as $index => $item)
                    <tr class="border-b border-gray-200">
                        <td class="px-4 py-3 text-sm">{{ $index + 1 }}</td>
                        <td class="px-4 py-3 text-sm">
                            <div class="font-medium">{{ $item->description }}</div>
                            @if($item->painting)
                                <div class="text-xs text-gray-500">Tranh: {{ $item->painting->code }}</div>
                            @endif
                            @if($item->supply)
                                <div class="text-xs text-gray-500">Vật tư: {{ $item->supply->name }} ({{ $item->supply_length }}m)</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-center">{{ $item->quantity }}</td>
                        <td class="px-4 py-3 text-sm text-right">
                            @if($item->currency == 'USD')
                                <div>${{ number_format($item->price_usd, 2) }}</div>
                                <div class="text-xs text-gray-500">{{ number_format($item->price_vnd) }}đ</div>
                            @else
                                <div>{{ number_format($item->price_vnd) }}đ</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-semibold">
                            <div>${{ number_format($item->total_usd, 2) }}</div>
                            <div class="text-xs text-gray-500">{{ number_format($item->total_vnd) }}đ</div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="flex justify-end mb-8">
            <div class="w-full md:w-1/2">
                <div class="space-y-2">
                    <div class="flex justify-between text-sm py-2 border-b">
                        <span class="text-gray-600">Tạm tính:</span>
                        <div class="text-right">
                            <div class="font-medium">${{ number_format($sale->subtotal_usd, 2) }}</div>
                            <div class="text-xs text-gray-500">{{ number_format($sale->subtotal_vnd) }}đ</div>
                        </div>
                    </div>
                    @if($sale->discount_percent > 0)
                    <div class="flex justify-between text-sm py-2 border-b">
                        <span class="text-gray-600">Giảm giá ({{ $sale->discount_percent }}%):</span>
                        <div class="text-right">
                            <div class="font-medium text-red-600">-${{ number_format($sale->discount_usd, 2) }}</div>
                            <div class="text-xs text-red-500">-{{ number_format($sale->discount_vnd) }}đ</div>
                        </div>
                    </div>
                    @endif
                    <div class="flex justify-between text-lg font-bold py-3 border-t-2 border-gray-300">
                        <span>Tổng cộng:</span>
                        <div class="text-right">
                            <div class="text-green-600">${{ number_format($sale->total_usd, 2) }}</div>
                            <div class="text-sm text-green-600">{{ number_format($sale->total_vnd) }}đ</div>
                        </div>
                    </div>
                    <div class="flex justify-between text-sm py-2">
                        <span class="text-gray-600">Đã thanh toán:</span>
                        <span class="font-medium text-blue-600">{{ number_format($sale->paid_amount) }}đ</span>
                    </div>
                    @if($sale->debt_amount > 0)
                    <div class="flex justify-between text-sm py-2 bg-red-50 px-3 rounded">
                        <span class="text-red-700 font-medium">Còn nợ:</span>
                        <span class="font-bold text-red-600">{{ number_format($sale->debt_amount) }}đ</span>
                    </div>
                    @endif
                    <div class="text-xs text-gray-500 text-right mt-2">
                        Tỷ giá: 1 USD = {{ number_format($sale->exchange_rate) }} VND
                    </div>
                </div>
            </div>
        </div>

        <!-- Signatures -->
        <div class="grid grid-cols-2 gap-8 mt-12 mb-8">
            <div class="text-center">
                <p class="font-semibold mb-16">Người bán hàng</p>
                <p class="text-sm text-gray-500">(Ký và ghi rõ họ tên)</p>
            </div>
            <div class="text-center">
                <p class="font-semibold mb-16">Khách hàng</p>
                <p class="text-sm text-gray-500">(Ký và ghi rõ họ tên)</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="border-t pt-4 mt-8">
            <div class="flex justify-between text-xs text-gray-600">
                <div>
                    <p>Hotline: 0987 654 321</p>
                    <p>Email: info@benthanhart.com</p>
                </div>
                <div class="text-right">
                    <p>Ngân hàng: Vietcombank 0123456789</p>
                    <p>CN Sài Gòn - Chủ TK: Công ty TNHH ABC</p>
                </div>
            </div>
            <p class="text-center text-xs text-gray-500 mt-4">Cảm ơn quý khách đã mua hàng!</p>
        </div>
    </div>

    <script>
        // Auto print when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
