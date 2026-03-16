@extends('layouts.app')

@section('title', 'Chi tiết Hàng Gia Công')
@section('page-title', 'Chi tiết: ' . $processedItem->name)

@section('header-actions')
    <div class="flex gap-2">
        <a href="{{ route('inventory.processed-items.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors text-sm">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
        @if($processedItem->saleItems->isEmpty())
            <a href="{{ route('inventory.processed-items.edit', $processedItem->id) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors text-sm">
                <i class="fas fa-edit mr-2"></i>Chỉnh sửa
            </a>
        @endif
    </div>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Main Information Card -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Status & Identification -->
                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2">Thông tin chung</h3>
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Mã sản phẩm</span>
                        <p class="text-sm font-bold text-indigo-600">{{ $processedItem->code }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Tên sản phẩm</span>
                        <p class="text-sm text-gray-900">{{ $processedItem->name }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</span>
                        <p class="text-sm text-gray-900">
                             <span class="px-2 py-0.5 text-xs font-semibold rounded bg-orange-100 text-orange-800">Hàng gia công</span>
                        </p>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</span>
                        <p class="text-sm mt-1">
                            @if ($processedItem->quantity > 0)
                                <span class="px-2 py-0.5 text-xs font-semibold rounded bg-green-100 text-green-800">Còn hàng</span>
                            @elseif ($processedItem->quantity < 0)
                                <span class="px-2 py-0.5 text-xs font-semibold rounded bg-red-100 text-red-800">Âm kho</span>
                            @else
                                <span class="px-2 py-0.5 text-xs font-semibold rounded bg-gray-200 text-gray-800">Hết hàng</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Quantities & Prices -->
                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2">Tồn kho & Giá</h3>
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Số lượng tồn</span>
                        <p class="text-xl font-bold text-gray-900">
                            {{ rtrim(rtrim(number_format($processedItem->quantity, 2), '0'), '.') }} {{ $processedItem->unit }}
                        </p>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Giá bán (VND)</span>
                        <p class="text-sm font-medium text-gray-900">{{ number_format($processedItem->price_vnd) }} đ</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Giá bán (USD)</span>
                        <p class="text-sm font-medium text-gray-900">${{ number_format($processedItem->price_usd, 2) }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày tạo hồ sơ</span>
                        <p class="text-sm text-gray-900">{{ $processedItem->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>

                <!-- Notes -->
                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2">Ghi chú</h3>
                    <div class="bg-gray-50 p-4 rounded-lg min-h-[100px]">
                        @if($processedItem->notes)
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $processedItem->notes }}</p>
                        @else
                            <p class="text-sm text-gray-400 italic">Không có ghi chú</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Transaction History -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-800">Lịch sử giao dịch kho</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số lượng</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($processedItem->inventoryTransactions->sortByDesc('transaction_date') as $transaction)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600">
                                        {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs">
                                        @if($transaction->transaction_type == 'import')
                                            <span class="px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-medium">Nhập kho</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-medium">Xuất kho</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs font-bold {{ $transaction->transaction_type == 'import' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $transaction->transaction_type == 'import' ? '+' : '-' }}{{ number_format($transaction->quantity, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ $transaction->notes }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-400 italic">Không có dữ liệu giao dịch</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Associated Sales -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800">Liên kết hóa đơn bán hàng</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số hóa đơn</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Khách hàng</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày bán</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số lượng</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($processedItem->saleItems as $saleItem)
                                @if($sale = $saleItem->sale)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <a href="{{ route('sales.show', $sale->id) }}" class="text-blue-600 font-bold hover:underline">
                                                {{ $sale->invoice_code }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600">
                                            {{ $sale->customer_name }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600">
                                            {{ $sale->sale_date->format('d/m/Y') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs font-bold text-gray-900">
                                            {{ number_format($saleItem->quantity, 2) }}
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-400 italic">Chưa có liên kết hóa đơn</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
