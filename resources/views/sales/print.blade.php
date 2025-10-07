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
                    <p class="text-lg text-gray-600">Mã HD: <span class="font-semibold text-blue-600">{{ $sale['id'] }}</span></p>
                    <p class="text-sm text-gray-600">Ngày: {{ $sale['date'] }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="font-bold text-lg">Bến Thành Art Gallery</p>
                <p class="text-sm text-gray-600">Địa chỉ: 123 Lê Lợi, Q.1, TP.HCM</p>
                <p class="text-sm text-gray-600">Hotline: 0987 654 321</p>
                <p class="text-sm text-gray-600">Email: info@benthanhart.com</p>
            </div>
        </div>

        <!-- Customer Info -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-semibold text-lg mb-3 text-gray-800">Thông tin khách hàng</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Tên khách hàng:</p>
                    <p class="font-medium">{{ $sale['customer_name'] }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Số điện thoại:</p>
                    <p class="font-medium">{{ $sale['customer_phone'] }}</p>
                </div>
                <div class="col-span-2">
                    <p class="text-sm text-gray-600">Địa chỉ:</p>
                    <p class="font-medium">{{ $sale['customer_address'] }}</p>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="mb-6">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-100 border-b-2 border-gray-300">
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">#</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Hình ảnh</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Sản phẩm</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">SL</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Đơn giá</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale['items'] as $index => $item)
                    <tr class="border-b border-gray-200">
                        <td class="px-4 py-3 text-sm">{{ $index + 1 }}</td>
                        <td class="px-4 py-3">
                            <img src="{{ $item['image'] ?? 'https://bizweb.dktcdn.net/100/372/422/products/tranh-son-dau-dep-da-nang-4-3.jpg?v=1679906135817' }}" 
                                 alt="img" class="w-20 h-16 object-cover rounded border" />
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $item['name'] }}</td>
                        <td class="px-4 py-3 text-sm text-center">{{ $item['quantity'] }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($item['price']) }}đ</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold">{{ number_format($item['total']) }}đ</td>
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
                        <span class="font-medium">{{ number_format($sale['subtotal']) }}đ</span>
                    </div>
                    @if($sale['discount'] > 0)
                    <div class="flex justify-between text-sm py-2 border-b">
                        <span class="text-gray-600">Giảm giá:</span>
                        <span class="font-medium text-red-600">-{{ number_format($sale['discount']) }}đ</span>
                    </div>
                    @endif
                    <div class="flex justify-between text-lg font-bold py-3 border-t-2 border-gray-300">
                        <span>Tổng cộng:</span>
                        <span class="text-green-600">{{ number_format($sale['total']) }}đ</span>
                    </div>
                    <div class="flex justify-between text-sm py-2">
                        <span class="text-gray-600">Đã thanh toán:</span>
                        <span class="font-medium text-blue-600">{{ number_format($sale['paid']) }}đ</span>
                    </div>
                    @if($sale['debt'] > 0)
                    <div class="flex justify-between text-sm py-2 bg-red-50 px-3 rounded">
                        <span class="text-red-700 font-medium">Còn nợ:</span>
                        <span class="font-bold text-red-600">{{ number_format($sale['debt']) }}đ</span>
                    </div>
                    @endif
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
