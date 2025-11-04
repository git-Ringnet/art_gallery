<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn {{ $sale['id'] }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .field-hidden {
            display: none !important;
        }
    </style>
</head>
<body class="bg-white">
    <!-- Print Buttons -->
    <div class="no-print fixed top-4 right-4 space-x-2 z-50">
        <button onclick="openCustomizeModal()" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
            <i class="fas fa-cog mr-2"></i>Tùy chỉnh
        </button>
        <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-print mr-2"></i>In hóa đơn
        </button>
        <button onclick="window.close()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
            <i class="fas fa-times mr-2"></i>Đóng
        </button>
    </div>

    <!-- Customize Modal -->
    <div id="customizeModal" class="no-print hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-cog mr-2 text-purple-600"></i>Tùy chỉnh hiển thị hóa đơn
                </h2>
                <button onclick="closeCustomizeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="p-6">
                <!-- Tabs -->
                <div class="flex border-b mb-4">
                    <button onclick="switchTab('visibility')" id="tab-visibility" class="px-4 py-2 font-medium text-purple-600 border-b-2 border-purple-600">
                        <i class="fas fa-eye mr-2"></i>Ẩn/Hiện
                    </button>
                    <button onclick="switchTab('content')" id="tab-content" class="px-4 py-2 font-medium text-gray-500 hover:text-gray-700">
                        <i class="fas fa-edit mr-2"></i>Chỉnh sửa nội dung
                    </button>
                </div>

                <!-- Visibility Tab -->
                <div id="visibility-tab" class="space-y-3">
                    <p class="text-sm text-gray-600 mb-4">Chọn các trường bạn muốn hiển thị trên hóa đơn khi in:</p>
                    <!-- Header Section -->
                    <div class="border-b pb-3">
                        <h3 class="font-semibold text-gray-700 mb-2">Phần đầu hóa đơn</h3>
                        <label class="flex items-center space-x-3 py-2 hover:bg-gray-50 px-2 rounded cursor-pointer">
                            <input type="checkbox" id="field-logo" class="w-4 h-4 text-purple-600" checked>
                            <span class="text-sm">Logo công ty</span>
                        </label>
                        <label class="flex items-center space-x-3 py-2 hover:bg-gray-50 px-2 rounded cursor-pointer">
                            <input type="checkbox" id="field-showroom-info" class="w-4 h-4 text-purple-600" checked>
                            <span class="text-sm">Thông tin showroom (địa chỉ, SĐT)</span>
                        </label>
                        <label class="flex items-center space-x-3 py-2 hover:bg-gray-50 px-2 rounded cursor-pointer">
                            <input type="checkbox" id="field-employee" class="w-4 h-4 text-purple-600" checked>
                            <span class="text-sm">Nhân viên bán hàng</span>
                        </label>
                        <label class="flex items-center space-x-3 py-2 hover:bg-gray-50 px-2 rounded cursor-pointer">
                            <input type="checkbox" id="field-payment-status" class="w-4 h-4 text-purple-600" checked>
                            <span class="text-sm">Trạng thái thanh toán</span>
                        </label>
                    </div>

                    <!-- Customer Section -->
                    <div class="border-b pb-3">
                        <h3 class="font-semibold text-gray-700 mb-2">Thông tin khách hàng</h3>
                        <label class="flex items-center space-x-3 py-2 hover:bg-gray-50 px-2 rounded cursor-pointer">
                            <input type="checkbox" id="field-customer-email" class="w-4 h-4 text-purple-600" checked>
                            <span class="text-sm">Email khách hàng</span>
                        </label>
                        <label class="flex items-center space-x-3 py-2 hover:bg-gray-50 px-2 rounded cursor-pointer">
                            <input type="checkbox" id="field-customer-address" class="w-4 h-4 text-purple-600" checked>
                            <span class="text-sm">Địa chỉ khách hàng</span>
                        </label>
                    </div>

                    <!-- Items Table Section -->
                    <div class="border-b pb-3">
                        <h3 class="font-semibold text-gray-700 mb-2">Bảng sản phẩm</h3>
                        <label class="flex items-center space-x-3 py-2 hover:bg-gray-50 px-2 rounded cursor-pointer">
                            <input type="checkbox" id="field-item-discount" class="w-4 h-4 text-purple-600" checked>
                            <span class="text-sm">Cột giảm giá sản phẩm</span>
                        </label>
                        <label class="flex items-center space-x-3 py-2 hover:bg-gray-50 px-2 rounded cursor-pointer">
                            <input type="checkbox" id="field-item-details" class="w-4 h-4 text-purple-600" checked>
                            <span class="text-sm">Chi tiết sản phẩm (mã tranh, vật tư)</span>
                        </label>
                    </div>

                    <!-- Totals Section -->
                    <div class="border-b pb-3">
                        <h3 class="font-semibold text-gray-700 mb-2">Phần tổng tiền</h3>
                        <label class="flex items-center space-x-3 py-2 hover:bg-gray-50 px-2 rounded cursor-pointer">
                            <input type="checkbox" id="field-total-discount" class="w-4 h-4 text-purple-600" checked>
                            <span class="text-sm">Giảm giá tổng đơn</span>
                        </label>
                        <label class="flex items-center space-x-3 py-2 hover:bg-gray-50 px-2 rounded cursor-pointer">
                            <input type="checkbox" id="field-exchange-rate" class="w-4 h-4 text-purple-600" checked>
                            <span class="text-sm">Tỷ giá USD/VND</span>
                        </label>
                        <label class="flex items-center space-x-3 py-2 hover:bg-gray-50 px-2 rounded cursor-pointer">
                            <input type="checkbox" id="field-debt-amount" class="w-4 h-4 text-purple-600" checked>
                            <span class="text-sm">Số tiền còn nợ</span>
                        </label>
                    </div>

                    <!-- Footer Section -->
                    <div class="pb-3">
                        <h3 class="font-semibold text-gray-700 mb-2">Phần cuối hóa đơn</h3>
                        <label class="flex items-center space-x-3 py-2 hover:bg-gray-50 px-2 rounded cursor-pointer">
                            <input type="checkbox" id="field-signatures" class="w-4 h-4 text-purple-600" checked>
                            <span class="text-sm">Chữ ký (Người bán & Khách hàng)</span>
                        </label>
                        <label class="flex items-center space-x-3 py-2 hover:bg-gray-50 px-2 rounded cursor-pointer">
                            <input type="checkbox" id="field-footer" class="w-4 h-4 text-purple-600" checked>
                            <span class="text-sm">Footer (Hotline, Email, Ngân hàng)</span>
                        </label>
                    </div>
                </div>

                <!-- Content Edit Tab -->
                <div id="content-tab" class="hidden space-y-4">
                    <p class="text-sm text-gray-600 mb-4">Chỉnh sửa nội dung các trường trên hóa đơn:</p>
                    
                    <div class="space-y-4">
                        <!-- Company Info -->
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <h3 class="font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-building mr-2 text-purple-600"></i>Thông tin công ty
                            </h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Logo công ty</label>
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0">
                                            <img id="logo-preview" src="https://via.placeholder.com/80x80/4F46E5/FFFFFF?text=Logo" alt="Logo preview" class="w-20 h-20 rounded-lg border-2 border-gray-300 object-cover">
                                        </div>
                                        <div class="flex-1">
                                            <input type="file" id="edit-logo-file" accept="image/*" class="hidden">
                                            <button onclick="document.getElementById('edit-logo-file').click()" type="button" class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 flex items-center justify-center">
                                                <i class="fas fa-upload mr-2"></i>Chọn ảnh từ máy
                                            </button>
                                            <p class="text-xs text-gray-500 mt-1">Hoặc nhập URL:</p>
                                            <input type="text" id="edit-logo-url" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mt-1" placeholder="https://example.com/logo.png">
                                            <button onclick="clearLogo()" type="button" class="text-xs text-red-600 hover:text-red-700 mt-1">
                                                <i class="fas fa-times mr-1"></i>Xóa logo
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề hóa đơn</label>
                                    <input type="text" id="edit-invoice-title" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="HÓA ĐƠN BÁN HÀNG">
                                </div>
                            </div>
                        </div>

                        <!-- Footer Info -->
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <h3 class="font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-info-circle mr-2 text-purple-600"></i>Thông tin liên hệ (Footer)
                            </h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Hotline</label>
                                    <input type="text" id="edit-hotline" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="0987 654 321">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input type="email" id="edit-email" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="info@benthanhart.com">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Thông tin ngân hàng</label>
                                    <input type="text" id="edit-bank-info" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Vietcombank 0123456789">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Chi nhánh & Chủ tài khoản</label>
                                    <input type="text" id="edit-bank-branch" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="CN Sài Gòn - Chủ TK: Công ty TNHH ABC">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Lời cảm ơn</label>
                                    <input type="text" id="edit-thank-you" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Cảm ơn quý khách đã mua hàng!">
                                </div>
                            </div>
                        </div>

                        <!-- Signature Labels -->
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <h3 class="font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-signature mr-2 text-purple-600"></i>Nhãn chữ ký
                            </h3>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Người bán</label>
                                    <input type="text" id="edit-seller-label" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Người bán hàng">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Khách hàng</label>
                                    <input type="text" id="edit-customer-label" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Khách hàng">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sticky bottom-0 bg-gray-50 px-6 py-4 flex justify-between items-center border-t">
                <button onclick="resetToDefault()" class="text-sm text-gray-600 hover:text-gray-800">
                    <i class="fas fa-undo mr-1"></i>Đặt lại mặc định
                </button>
                <div class="space-x-2">
                    <button onclick="closeCustomizeModal()" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Hủy
                    </button>
                    <button onclick="applyCustomization()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                        <i class="fas fa-check mr-2"></i>Áp dụng
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Content -->
    <div class="print-area max-w-4xl mx-auto bg-white p-8">
        <!-- Header -->
        <div class="flex justify-between items-start mb-8">
            <div class="flex items-center space-x-4">
                <img id="invoice-logo" src="https://via.placeholder.com/80x80/4F46E5/FFFFFF?text=Logo" alt="logo" class="w-20 h-20 rounded-lg field-logo" data-field="logo" />
                <div>
                    <h1 id="invoice-title" class="text-3xl font-bold text-gray-800">HÓA ĐƠN BÁN HÀNG</h1>
                    <p class="text-lg text-gray-600">Mã HD: <span class="font-semibold text-blue-600">{{ $sale->invoice_code }}</span></p>
                    <p class="text-sm text-gray-600">Ngày: {{ $sale->sale_date->format('d/m/Y') }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="font-bold text-lg">{{ $sale->showroom->name }}</p>
                <div class="field-showroom-info" data-field="showroom-info">
                    <p class="text-sm text-gray-600">{{ $sale->showroom->address }}</p>
                    <p class="text-sm text-gray-600">{{ $sale->showroom->phone }}</p>
                </div>
                <div class="mt-2 text-xs text-gray-500">
                    <p class="field-employee" data-field="employee">Nhân viên: {{ $sale->user ? $sale->user->name : 'Chưa xác định' }}</p>
                    <p class="field-payment-status" data-field="payment-status">Trạng thái: 
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
                <div class="field-customer-email" data-field="customer-email">
                    <p class="text-sm text-gray-600">Email:</p>
                    <p class="font-medium">{{ $sale->customer->email }}</p>
                </div>
                @endif
                @if($sale->customer->address)
                <div class="field-customer-address" data-field="customer-address">
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
                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 field-item-discount" data-field="item-discount">Giảm giá</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @php $displayIndex = 0; @endphp
                    @foreach($sale->saleItems as $item)
                        @if(!($item->is_returned ?? false))
                            @php $displayIndex++; @endphp
                            <tr class="border-b border-gray-200">
                                <td class="px-4 py-3 text-sm">{{ $displayIndex }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-medium">{{ $item->description }}</div>
                                    <div class="field-item-details" data-field="item-details">
                                        @if($item->painting)
                                            <div class="text-xs text-gray-500">Tranh: {{ $item->painting->code }}</div>
                                        @endif
                                        @if($item->frame)
                                            <div class="text-xs text-blue-600">Khung: {{ $item->frame->name }}</div>
                                        @endif
                                    </div>
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
                                <td class="px-4 py-3 text-sm text-right field-item-discount" data-field="item-discount">
                                    @if($item->discount_percent > 0)
                                        <span class="text-red-600">{{ number_format($item->discount_percent, 0) }}%</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-right font-semibold">
                                    <div>${{ number_format($item->total_usd, 2) }}</div>
                                    <div class="text-xs text-gray-500">{{ number_format($item->total_vnd) }}đ</div>
                                </td>
                            </tr>
                        @endif
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
                    <div class="flex justify-between text-sm py-2 border-b field-total-discount" data-field="total-discount">
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
                    <div class="flex justify-between text-sm py-2 bg-red-50 px-3 rounded field-debt-amount" data-field="debt-amount">
                        <span class="text-red-700 font-medium">Còn nợ:</span>
                        <span class="font-bold text-red-600">{{ number_format($sale->debt_amount) }}đ</span>
                    </div>
                    @endif
                    <div class="text-xs text-gray-500 text-right mt-2 field-exchange-rate" data-field="exchange-rate">
                        Tỷ giá: 1 USD = {{ number_format($sale->exchange_rate) }} VND
                    </div>
                </div>
            </div>
        </div>

        <!-- Signatures -->
        <div class="grid grid-cols-2 gap-8 mt-12 mb-8 field-signatures" data-field="signatures">
            <div class="text-center">
                <p id="seller-label" class="font-semibold mb-16">Người bán hàng</p>
                <p class="text-sm text-gray-500">(Ký và ghi rõ họ tên)</p>
            </div>
            <div class="text-center">
                <p id="customer-signature-label" class="font-semibold mb-16">Khách hàng</p>
                <p class="text-sm text-gray-500">(Ký và ghi rõ họ tên)</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="border-t pt-4 mt-8 field-footer" data-field="footer">
            <div class="flex justify-between text-xs text-gray-600">
                <div>
                    <p id="footer-hotline">Hotline: 0987 654 321</p>
                    <p id="footer-email">Email: info@benthanhart.com</p>
                </div>
                <div class="text-right">
                    <p id="footer-bank-info">Ngân hàng: Vietcombank 0123456789</p>
                    <p id="footer-bank-branch">CN Sài Gòn - Chủ TK: Công ty TNHH ABC</p>
                </div>
            </div>
            <p id="footer-thank-you" class="text-center text-xs text-gray-500 mt-4">Cảm ơn quý khách đã mua hàng!</p>
        </div>
    </div>

    <script>
        // Configuration storage keys
        const STORAGE_KEY = 'invoice_print_config';
        const CONTENT_STORAGE_KEY = 'invoice_content_config';

        // Default configuration
        const defaultConfig = {
            'logo': true,
            'showroom-info': true,
            'employee': true,
            'payment-status': true,
            'customer-email': true,
            'customer-address': true,
            'item-discount': true,
            'item-details': true,
            'total-discount': true,
            'exchange-rate': true,
            'debt-amount': true,
            'signatures': true,
            'footer': true
        };

        // Default content configuration
        const defaultContentConfig = {
            'logoUrl': 'https://via.placeholder.com/80x80/4F46E5/FFFFFF?text=Logo',
            'invoiceTitle': 'HÓA ĐƠN BÁN HÀNG',
            'hotline': 'Hotline: 0987 654 321',
            'email': 'Email: info@benthanhart.com',
            'bankInfo': 'Ngân hàng: Vietcombank 0123456789',
            'bankBranch': 'CN Sài Gòn - Chủ TK: Công ty TNHH ABC',
            'thankYou': 'Cảm ơn quý khách đã mua hàng!',
            'sellerLabel': 'Người bán hàng',
            'customerLabel': 'Khách hàng'
        };

        // Load saved configuration or use default
        function loadConfig() {
            try {
                const saved = localStorage.getItem(STORAGE_KEY);
                return saved ? JSON.parse(saved) : defaultConfig;
            } catch (e) {
                console.error('Error loading config:', e);
                return defaultConfig;
            }
        }

        // Save configuration
        function saveConfig(config) {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(config));
            } catch (e) {
                console.error('Error saving config:', e);
            }
        }

        // Load saved content configuration
        function loadContentConfig() {
            try {
                const saved = localStorage.getItem(CONTENT_STORAGE_KEY);
                return saved ? JSON.parse(saved) : defaultContentConfig;
            } catch (e) {
                console.error('Error loading content config:', e);
                return defaultContentConfig;
            }
        }

        // Save content configuration
        function saveContentConfig(config) {
            try {
                localStorage.setItem(CONTENT_STORAGE_KEY, JSON.stringify(config));
            } catch (e) {
                console.error('Error saving content config:', e);
            }
        }

        // Apply content configuration to page
        function applyContentToPage(config) {
            const logo = document.getElementById('invoice-logo');
            if (logo && config.logoUrl) logo.src = config.logoUrl;
            
            const title = document.getElementById('invoice-title');
            if (title && config.invoiceTitle) title.textContent = config.invoiceTitle;
            
            const hotline = document.getElementById('footer-hotline');
            if (hotline && config.hotline) hotline.textContent = config.hotline;
            
            const email = document.getElementById('footer-email');
            if (email && config.email) email.textContent = config.email;
            
            const bankInfo = document.getElementById('footer-bank-info');
            if (bankInfo && config.bankInfo) bankInfo.textContent = config.bankInfo;
            
            const bankBranch = document.getElementById('footer-bank-branch');
            if (bankBranch && config.bankBranch) bankBranch.textContent = config.bankBranch;
            
            const thankYou = document.getElementById('footer-thank-you');
            if (thankYou && config.thankYou) thankYou.textContent = config.thankYou;
            
            const sellerLabel = document.getElementById('seller-label');
            if (sellerLabel && config.sellerLabel) sellerLabel.textContent = config.sellerLabel;
            
            const customerLabel = document.getElementById('customer-signature-label');
            if (customerLabel && config.customerLabel) customerLabel.textContent = config.customerLabel;
        }

        // Switch between tabs
        function switchTab(tabName) {
            const visibilityTab = document.getElementById('visibility-tab');
            const contentTab = document.getElementById('content-tab');
            const visibilityBtn = document.getElementById('tab-visibility');
            const contentBtn = document.getElementById('tab-content');
            
            if (tabName === 'visibility') {
                visibilityTab.classList.remove('hidden');
                contentTab.classList.add('hidden');
                visibilityBtn.classList.add('text-purple-600', 'border-b-2', 'border-purple-600');
                visibilityBtn.classList.remove('text-gray-500');
                contentBtn.classList.remove('text-purple-600', 'border-b-2', 'border-purple-600');
                contentBtn.classList.add('text-gray-500');
            } else {
                visibilityTab.classList.add('hidden');
                contentTab.classList.remove('hidden');
                contentBtn.classList.add('text-purple-600', 'border-b-2', 'border-purple-600');
                contentBtn.classList.remove('text-gray-500');
                visibilityBtn.classList.remove('text-purple-600', 'border-b-2', 'border-purple-600');
                visibilityBtn.classList.add('text-gray-500');
            }
        }

        // Apply configuration to page
        function applyConfigToPage(config) {
            Object.keys(config).forEach(field => {
                const elements = document.querySelectorAll(`[data-field="${field}"]`);
                elements.forEach(el => {
                    if (config[field]) {
                        el.classList.remove('field-hidden');
                    } else {
                        el.classList.add('field-hidden');
                    }
                });
            });
        }

        // Handle logo file upload
        function handleLogoUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            // Validate file type
            if (!file.type.startsWith('image/')) {
                showNotification('Vui lòng chọn file ảnh!', 'error');
                return;
            }
            
            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                showNotification('Kích thước ảnh không được vượt quá 2MB!', 'error');
                return;
            }
            
            // Convert to base64
            const reader = new FileReader();
            reader.onload = function(e) {
                const base64 = e.target.result;
                document.getElementById('edit-logo-url').value = base64;
                document.getElementById('logo-preview').src = base64;
                showNotification('Đã tải ảnh lên thành công!', 'success');
            };
            reader.onerror = function() {
                showNotification('Lỗi khi đọc file ảnh!', 'error');
            };
            reader.readAsDataURL(file);
        }

        // Handle logo URL input
        function handleLogoUrlInput(event) {
            const url = event.target.value;
            if (url) {
                document.getElementById('logo-preview').src = url;
            }
        }

        // Clear logo
        function clearLogo() {
            document.getElementById('edit-logo-url').value = defaultContentConfig.logoUrl;
            document.getElementById('logo-preview').src = defaultContentConfig.logoUrl;
            document.getElementById('edit-logo-file').value = '';
            showNotification('Đã xóa logo!', 'success');
        }

        // Open customize modal
        function openCustomizeModal() {
            const modal = document.getElementById('customizeModal');
            const config = loadConfig();
            const contentConfig = loadContentConfig();
            
            // Set checkbox states
            Object.keys(config).forEach(field => {
                const checkbox = document.getElementById(`field-${field}`);
                if (checkbox) {
                    checkbox.checked = config[field];
                }
            });
            
            // Set content input values
            const logoUrl = contentConfig.logoUrl || defaultContentConfig.logoUrl;
            document.getElementById('edit-logo-url').value = logoUrl;
            document.getElementById('logo-preview').src = logoUrl;
            document.getElementById('edit-invoice-title').value = contentConfig.invoiceTitle || '';
            document.getElementById('edit-hotline').value = contentConfig.hotline || '';
            document.getElementById('edit-email').value = contentConfig.email || '';
            document.getElementById('edit-bank-info').value = contentConfig.bankInfo || '';
            document.getElementById('edit-bank-branch').value = contentConfig.bankBranch || '';
            document.getElementById('edit-thank-you').value = contentConfig.thankYou || '';
            document.getElementById('edit-seller-label').value = contentConfig.sellerLabel || '';
            document.getElementById('edit-customer-label').value = contentConfig.customerLabel || '';
            
            modal.classList.remove('hidden');
        }

        // Close customize modal
        function closeCustomizeModal() {
            const modal = document.getElementById('customizeModal');
            modal.classList.add('hidden');
        }

        // Apply customization
        function applyCustomization() {
            const config = {};
            
            // Get all checkbox values
            Object.keys(defaultConfig).forEach(field => {
                const checkbox = document.getElementById(`field-${field}`);
                if (checkbox) {
                    config[field] = checkbox.checked;
                }
            });
            
            // Get all content values
            const contentConfig = {
                logoUrl: document.getElementById('edit-logo-url').value || defaultContentConfig.logoUrl,
                invoiceTitle: document.getElementById('edit-invoice-title').value || defaultContentConfig.invoiceTitle,
                hotline: document.getElementById('edit-hotline').value || defaultContentConfig.hotline,
                email: document.getElementById('edit-email').value || defaultContentConfig.email,
                bankInfo: document.getElementById('edit-bank-info').value || defaultContentConfig.bankInfo,
                bankBranch: document.getElementById('edit-bank-branch').value || defaultContentConfig.bankBranch,
                thankYou: document.getElementById('edit-thank-you').value || defaultContentConfig.thankYou,
                sellerLabel: document.getElementById('edit-seller-label').value || defaultContentConfig.sellerLabel,
                customerLabel: document.getElementById('edit-customer-label').value || defaultContentConfig.customerLabel
            };
            
            // Save and apply
            saveConfig(config);
            saveContentConfig(contentConfig);
            applyConfigToPage(config);
            applyContentToPage(contentConfig);
            closeCustomizeModal();
            
            // Show success message
            showNotification('Đã áp dụng cấu hình thành công!');
        }

        // Reset to default
        function resetToDefault() {
            // Set all checkboxes to checked
            Object.keys(defaultConfig).forEach(field => {
                const checkbox = document.getElementById(`field-${field}`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
            
            // Reset content inputs
            document.getElementById('edit-logo-url').value = defaultContentConfig.logoUrl;
            document.getElementById('edit-invoice-title').value = defaultContentConfig.invoiceTitle;
            document.getElementById('edit-hotline').value = defaultContentConfig.hotline;
            document.getElementById('edit-email').value = defaultContentConfig.email;
            document.getElementById('edit-bank-info').value = defaultContentConfig.bankInfo;
            document.getElementById('edit-bank-branch').value = defaultContentConfig.bankBranch;
            document.getElementById('edit-thank-you').value = defaultContentConfig.thankYou;
            document.getElementById('edit-seller-label').value = defaultContentConfig.sellerLabel;
            document.getElementById('edit-customer-label').value = defaultContentConfig.customerLabel;
            
            showNotification('Đã đặt lại về mặc định!');
        }

        // Show notification
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            const bgColor = type === 'error' ? 'bg-red-500' : 'bg-green-500';
            const icon = type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle';
            notification.className = `no-print fixed top-20 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in`;
            notification.innerHTML = `<i class="fas ${icon} mr-2"></i>${message}`;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.3s';
                setTimeout(() => notification.remove(), 300);
            }, 2000);
        }

        // Close modal when clicking outside
        document.getElementById('customizeModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeCustomizeModal();
            }
        });

        // Initialize on page load
        window.onload = function() {
            const config = loadConfig();
            const contentConfig = loadContentConfig();
            applyConfigToPage(config);
            applyContentToPage(contentConfig);
            
            // Add event listeners
            const logoFileInput = document.getElementById('edit-logo-file');
            if (logoFileInput) {
                logoFileInput.addEventListener('change', handleLogoUpload);
            }
            
            const logoUrlInput = document.getElementById('edit-logo-url');
            if (logoUrlInput) {
                logoUrlInput.addEventListener('input', handleLogoUrlInput);
            }
            
            // Auto print when page loads (optional)
            // window.print();
        };
    </script>
</body>
</html>
