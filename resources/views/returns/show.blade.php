@extends('layouts.app')

@section('title', 'Chi tiết phiếu trả hàng')
@section('page-title', 'Chi tiết phiếu trả hàng')
@section('page-description', 'Thông tin chi tiết giao dịch trả hàng')

@section('content')
<x-alert />

<div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
    <!-- Header Actions -->
    <div class="flex justify-between items-center mb-6">
        <a href="{{ route('returns.index') }}?tab=list" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại danh sách
        </a>
        <div class="flex space-x-2">
            @if($return->status == 'pending')
            <a href="{{ route('returns.edit', $return->id) }}" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
                <i class="fas fa-edit mr-2"></i>Chỉnh sửa
            </a>
            <form action="{{ route('returns.approve', $return->id) }}" method="POST" class="inline">
                @csrf
                @method('PUT')
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-check mr-2"></i>Duyệt phiếu
                </button>
            </form>
            @endif
            @if($return->status == 'approved')
            <form action="{{ route('returns.complete', $return->id) }}" method="POST" class="inline">
                @csrf
                @method('PUT')
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-check-double mr-2"></i>Hoàn thành
                </button>
            </form>
            @endif
            @if($return->status != 'cancelled' && $return->status != 'completed')
            <form action="{{ route('returns.cancel', $return->id) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn hủy phiếu này?')">
                @csrf
                @method('PUT')
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-ban mr-2"></i>Hủy phiếu
                </button>
            </form>
            @endif
            <button onclick="window.print()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-print mr-2"></i>In phiếu
            </button>
        </div>
    </div>

    <!-- Return Info -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Left Column -->
        <div class="space-y-4">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold text-lg mb-3">Thông tin phiếu</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Mã phiếu:</span>
                        <span class="font-medium">{{ $return->return_code }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Loại:</span>
                        <span>
                            @if($return->type == 'exchange')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <i class="fas fa-exchange-alt mr-1"></i>Đổi hàng
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                                    <i class="fas fa-undo mr-1"></i>Trả hàng
                                </span>
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Mã hóa đơn gốc:</span>
                        <a href="{{ route('sales.show', $return->sale_id) }}" class="font-medium text-blue-600 hover:underline">
                            {{ $return->sale->invoice_code ?? 'N/A' }}
                        </a>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Ngày:</span>
                        <span class="font-medium">{{ $return->return_date->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Trạng thái:</span>
                        <span>
                            @if($return->status == 'completed')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Hoàn thành</span>
                            @elseif($return->status == 'approved')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Đã duyệt</span>
                            @elseif($return->status == 'pending')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Chờ duyệt</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Đã hủy</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Người xử lý:</span>
                        <span class="font-medium">{{ $return->processedBy->name ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-4">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold text-lg mb-3">Thông tin khách hàng</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tên khách hàng:</span>
                        <span class="font-medium">{{ $return->customer->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Số điện thoại:</span>
                        <span class="font-medium">{{ $return->customer->phone ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Email:</span>
                        <span class="font-medium">{{ $return->customer->email ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Địa chỉ:</span>
                        <span class="font-medium text-right">{{ $return->customer->address ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Return Items -->
    <div class="mb-6">
        <h3 class="font-semibold text-lg mb-3">
            @if($return->type == 'exchange')
                Sản phẩm đổi/trả
            @else
                Sản phẩm trả lại
            @endif
        </h3>
        
        @if($return->type == 'exchange')
        <!-- Exchange View with Arrows -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Returned Items -->
            <div class="border rounded-lg p-4 bg-red-50">
                <h4 class="font-medium mb-3 text-red-800">
                    <i class="fas fa-arrow-left mr-2"></i>Sản phẩm trả lại
                </h4>
                <div class="space-y-2">
                    @foreach($return->items as $item)
                    <div class="bg-white p-3 rounded border">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="font-medium text-sm">
                                    @if($item->item_type === 'painting')
                                        {{ $item->painting->name ?? 'N/A' }}
                                    @else
                                        {{ $item->supply->name ?? 'N/A' }}
                                    @endif
                                </p>
                                <p class="text-xs text-gray-600">
                                    @if($item->item_type === 'painting')
                                        <span class="px-2 py-0.5 rounded-full bg-purple-100 text-purple-800">Tranh</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">Vật tư</span>
                                    @endif
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium">SL: {{ $item->quantity }}</p>
                                <p class="text-xs text-gray-600">{{ number_format($item->unit_price, 0, ',', '.') }}đ</p>
                                <p class="text-sm font-semibold text-red-600">{{ number_format($item->subtotal, 0, ',', '.') }}đ</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="mt-3 pt-3 border-t">
                    <div class="flex justify-between font-semibold">
                        <span>Tổng trả:</span>
                        <span class="text-red-600">{{ number_format($return->total_refund, 0, ',', '.') }}đ</span>
                    </div>
                </div>
            </div>
            
            <!-- Exchange Arrow -->
            <div class="hidden lg:flex items-center justify-center absolute left-1/2 transform -translate-x-1/2 z-10">
                <div class="bg-blue-600 text-white rounded-full p-4 shadow-lg">
                    <i class="fas fa-exchange-alt text-2xl"></i>
                </div>
            </div>
            
            <!-- Exchange Items -->
            <div class="border rounded-lg p-4 bg-green-50">
                <h4 class="font-medium mb-3 text-green-800">
                    <i class="fas fa-arrow-right mr-2"></i>Sản phẩm đổi mới
                </h4>
                @if($return->exchangeItems->count() > 0)
                <div class="space-y-2">
                    @foreach($return->exchangeItems as $item)
                    <div class="bg-white p-3 rounded border">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="font-medium text-sm">
                                    @if($item->item_type === 'painting')
                                        {{ $item->painting->name ?? 'N/A' }}
                                    @else
                                        {{ $item->supply->name ?? 'N/A' }}
                                    @endif
                                </p>
                                <p class="text-xs text-gray-600">
                                    @if($item->item_type === 'painting')
                                        <span class="px-2 py-0.5 rounded-full bg-purple-100 text-purple-800">Tranh</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">Vật tư</span>
                                    @endif
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium">SL: {{ $item->quantity }}</p>
                                <p class="text-xs text-gray-600">{{ number_format($item->unit_price, 0, ',', '.') }}đ</p>
                                <p class="text-sm font-semibold text-green-600">{{ number_format($item->subtotal, 0, ',', '.') }}đ</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="mt-3 pt-3 border-t">
                    <div class="flex justify-between font-semibold">
                        <span>Tổng đổi:</span>
                        <span class="text-green-600">{{ number_format($return->exchangeItems->sum('subtotal'), 0, ',', '.') }}đ</span>
                    </div>
                </div>
                @else
                <div class="text-center text-gray-500 py-4">
                    <p class="text-sm">Chưa có sản phẩm đổi</p>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Exchange Summary -->
        @if($return->exchange_amount != 0)
        <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div class="flex justify-between items-center">
                <span class="font-semibold text-lg">Chênh lệch:</span>
                <span class="font-bold text-xl">
                    @if($return->exchange_amount > 0)
                        <span class="text-red-600">+{{ number_format($return->exchange_amount, 0, ',', '.') }}đ (Khách trả thêm)</span>
                    @else
                        <span class="text-green-600">{{ number_format($return->exchange_amount, 0, ',', '.') }}đ (Hoàn lại)</span>
                    @endif
                </span>
            </div>
        </div>
        @endif
        
        @else
        <!-- Regular Return View -->
        <div class="overflow-x-auto">
            <table class="w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loại</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Đơn giá</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thành tiền</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lý do</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($return->items as $index => $item)
                    <tr>
                        <td class="px-4 py-3 text-sm">{{ $index + 1 }}</td>
                        <td class="px-4 py-3 text-sm font-medium">
                            @if($item->item_type === 'painting')
                                {{ $item->painting->name ?? 'N/A' }}
                            @else
                                {{ $item->supply->name ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($item->item_type === 'painting')
                                <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">Tranh</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Vật tư</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right">{{ $item->quantity }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($item->unit_price, 0, ',', '.') }}đ</td>
                        <td class="px-4 py-3 text-sm text-right font-medium">{{ number_format($item->subtotal, 0, ',', '.') }}đ</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $item->reason ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-right font-semibold">Tổng cộng:</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ $return->items->sum('quantity') }}</td>
                        <td colspan="3" class="px-4 py-3 text-right font-semibold text-red-600 text-lg">
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
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @if($return->reason)
        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
            <h4 class="font-semibold mb-2">Lý do trả hàng:</h4>
            <p class="text-gray-700">{{ $return->reason }}</p>
        </div>
        @endif
        
        @if($return->notes)
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
            <h4 class="font-semibold mb-2">Ghi chú:</h4>
            <p class="text-gray-700">{{ $return->notes }}</p>
        </div>
        @endif
    </div>
    @endif
</div>
@endsection
