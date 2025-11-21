@extends('layouts.app')

@section('title', 'Đổi/Trả hàng')
@section('page-title', 'Đổi/Trả hàng')
@section('page-description', 'Quản lý các giao dịch đổi/trả hàng')

@section('header-actions')
<div class="flex gap-2">
    @hasPermission('returns', 'can_create')
    <a href="{{ route('returns.create') }}" class="bg-blue-600 text-white px-3 py-1.5 text-sm rounded-lg hover:bg-blue-700 transition-colors">
        <i class="fas fa-plus mr-1"></i>Tạo phiếu
    </a>
    @endhasPermission
    
    
</div>
@endsection

@section('content')
<x-alert />

<div class="bg-white rounded-xl shadow-lg p-4 glass-effect">
    <!-- Search and Filter -->
    <div class="bg-gray-50 p-3 rounded-lg mb-4">
        <form method="GET" action="{{ route('returns.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tìm kiếm</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}" class="w-full pl-8 pr-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Mã phiếu, mã HD, KH...">
                        <i class="fas fa-search absolute left-2 top-2 text-xs text-gray-400"></i>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Loại</label>
                    <select name="type" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">Tất cả</option>
                        <option value="return" {{ request('type') == 'return' ? 'selected' : '' }}>Trả</option>
                        <option value="exchange" {{ request('type') == 'exchange' ? 'selected' : '' }}>Đổi</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Trạng thái</label>
                    <select name="status" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">Tất cả</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã Hủy</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Từ ngày</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Đến ngày</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
            </div>
            <div class="flex justify-between items-center mt-3">
                <button type="submit" class="bg-blue-600 text-white py-1.5 px-4 text-sm rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-filter mr-1"></i>Lọc
                </button>
                <a href="{{ route('returns.index') }}" class="bg-gray-500 text-white py-1.5 px-4 text-sm rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-1"></i>Xóa
                </a>
            </div>
        </form>
    </div>
    
    <!-- Returns Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white">
                <tr>
                    <th class="px-2 py-2 text-center text-xs">STT</th>
                    <th class="px-2 py-2 text-left text-xs whitespace-nowrap">Mã phiếu</th>
                    <th class="px-2 py-2 text-left text-xs">Loại</th>
                    <th class="px-2 py-2 text-left text-xs whitespace-nowrap">Mã HD</th>
                    <th class="px-2 py-2 text-left text-xs">Ngày</th>
                    <th class="px-2 py-2 text-left text-xs">Khách hàng</th>
                    <th class="px-2 py-2 text-left text-xs">Sản phẩm</th>
                    <th class="px-2 py-2 text-right text-xs">SL</th>
                    <th class="px-2 py-2 text-right text-xs whitespace-nowrap">Tiền hoàn</th>
                    <th class="px-2 py-2 text-right text-xs whitespace-nowrap">Đã trả</th>
                    <th class="px-2 py-2 text-left text-xs">TT</th>
                    <th class="px-2 py-2 text-center text-xs">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($returns as $index => $return)
                <tr class="hover:bg-gray-50">
                    <td class="px-2 py-2 text-center text-xs text-gray-600">
                        {{ ($returns->currentPage() - 1) * $returns->perPage() + $index + 1 }}
                    </td>
                    <td class="px-2 py-2 whitespace-nowrap text-xs font-medium text-indigo-600">
                        {{ $return->return_code }}
                    </td>
                    <td class="px-2 py-2 whitespace-nowrap">
                        @if($return->type == 'exchange')
                            <span class="px-1.5 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                Đổi hàng
                            </span>
                        @else
                            <span class="px-1.5 py-0.5 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                                Trả hàng
                            </span>
                        @endif
                    </td>
                    <td class="px-2 py-2 whitespace-nowrap text-xs text-blue-600">
                        <a href="{{ route('sales.show', $return->sale_id) }}" class="hover:underline">
                            {{ $return->sale->invoice_code ?? 'N/A' }}
                        </a>
                    </td>
                    <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">
                        {{ $return->return_date->format('d/m/Y') }}
                    </td>
                    <td class="px-2 py-2">
                        <div class="text-xs font-medium text-gray-900 truncate max-w-[120px]">{{ $return->customer->name ?? 'N/A' }}</div>
                        <div class="text-xs text-gray-500">{{ $return->customer->phone ?? '' }}</div>
                    </td>
                    <td class="px-2 py-2 text-xs text-gray-900 truncate max-w-[150px]">
                        @php
                            $itemNames = $return->items->map(function($item) {
                                if ($item->item_type === 'painting') {
                                    return $item->painting->name ?? 'N/A';
                                } else {
                                    return $item->supply->name ?? 'N/A';
                                }
                            })->take(2)->implode(', ');
                            $moreCount = $return->items->count() - 2;
                        @endphp
                        {{ $itemNames }}
                        @if($moreCount > 0)
                            <span class="text-gray-500">+{{ $moreCount }}</span>
                        @endif
                    </td>
                    <td class="px-2 py-2 whitespace-nowrap text-xs text-right text-gray-900">
                        {{ $return->items->sum('quantity') }}
                    </td>
                    <td class="px-2 py-2 whitespace-nowrap text-xs text-right">
                        @php
                            $exchangeRate = $return->exchange_rate ?? 25000;
                            // Determine primary currency based on stored USD values
                            $isUsdPrimary = ($return->total_refund_usd > 0 || ($return->exchange_amount_usd ?? 0) != 0);
                        @endphp

                        @if($return->type == 'exchange')
                            @php
                                $amountVnd = $return->exchange_amount;
                                $amountUsd = $return->exchange_amount_usd ?? ($amountVnd / $exchangeRate);
                                $colorClass = $amountVnd > 0 ? 'text-red-600' : ($amountVnd < 0 ? 'text-green-600' : 'text-gray-600');
                                $sign = $amountVnd > 0 ? '+' : '';
                            @endphp
                            
                            @if($amountVnd == 0)
                                <span class="text-gray-600">0đ</span>
                            @else
                                @if($isUsdPrimary)
                                    <div class="font-bold {{ $colorClass }}">{{ $sign }}${{ number_format($amountUsd, 2) }}</div>
                                    <div class="text-[10px] text-gray-500">≈ {{ number_format($amountVnd, 0, ',', '.') }}đ</div>
                                @else
                                    <div class="font-bold {{ $colorClass }}">{{ $sign }}{{ number_format($amountVnd, 0, ',', '.') }}đ</div>
                                    <div class="text-[10px] text-gray-500">≈ ${{ number_format($amountUsd, 2) }}</div>
                                @endif
                            @endif
                        @else
                            @php
                                $amountVnd = $return->total_refund;
                                $amountUsd = $return->total_refund_usd ?? ($amountVnd / $exchangeRate);
                            @endphp
                            @if($isUsdPrimary)
                                <div class="font-bold text-red-600">${{ number_format($amountUsd, 2) }}</div>
                                <div class="text-[10px] text-gray-500">≈ {{ number_format($amountVnd, 0, ',', '.') }}đ</div>
                            @else
                                <div class="font-bold text-red-600">{{ number_format($amountVnd, 0, ',', '.') }}đ</div>
                                <div class="text-[10px] text-gray-500">≈ ${{ number_format($amountUsd, 2) }}</div>
                            @endif
                        @endif
                    </td>
                    <td class="px-2 py-2 whitespace-nowrap text-xs text-right">
                        @if($return->type == 'exchange' && $return->exchange_amount > 0)
                            @php
                                $exchangePayments = $return->sale->payments()
                                    ->where('transaction_type', 'exchange_payment')
                                    ->where('notes', 'like', "%{$return->return_code}%")
                                    ->sum('amount');
                                $exchangePaymentsUsd = $exchangePayments / $exchangeRate;
                            @endphp
                            @if($exchangePayments > 0)
                                @if($isUsdPrimary)
                                    <div class="font-bold text-green-600">${{ number_format($exchangePaymentsUsd, 2) }}</div>
                                    <div class="text-[10px] text-gray-500">≈ {{ number_format($exchangePayments, 0, ',', '.') }}đ</div>
                                @else
                                    <div class="font-bold text-green-600">{{ number_format($exchangePayments, 0, ',', '.') }}đ</div>
                                    <div class="text-[10px] text-gray-500">≈ ${{ number_format($exchangePaymentsUsd, 2) }}</div>
                                @endif
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-2 py-2 whitespace-nowrap">
                        @if($return->status == 'completed')
                            <span class="px-1.5 py-0.5 text-xs font-bold rounded bg-green-100 text-green-800">Xong</span>
                        @elseif($return->status == 'approved')
                            <span class="px-1.5 py-0.5 text-xs font-bold rounded bg-green-100 text-green-800">Duyệt</span>
                        @elseif($return->status == 'pending')
                            <span class="px-1.5 py-0.5 text-xs font-bold rounded bg-yellow-100 text-yellow-800">Chờ</span>
                        @else
                            <span class="px-1.5 py-0.5 text-xs font-bold rounded bg-red-100 text-red-800">Hủy</span>
                        @endif
                    </td>
                    <td class="px-2 py-2 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-1">
                            <!-- View -->
                            @hasPermission('returns', 'can_view')
                            <a href="{{ route('returns.show', $return->id) }}" 
                               class="w-7 h-7 flex items-center justify-center rounded bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors" 
                               title="Xem">
                                <i class="fas fa-eye text-xs"></i>
                            </a>
                            @endhasPermission
                            
                            <!-- Edit -->
                            @hasPermission('returns', 'can_edit')
                            @if($return->status == 'pending')
                            <a href="{{ route('returns.edit', $return->id) }}" 
                               class="w-7 h-7 flex items-center justify-center rounded bg-yellow-100 text-yellow-600 hover:bg-yellow-200 transition-colors" 
                               title="Sửa">
                                <i class="fas fa-edit text-xs"></i>
                            </a>
                            @endif
                            @endhasPermission
                            
                            <!-- Approve -->
                            @hasPermission('returns', 'can_approve')
                            @if($return->status == 'pending')
                            <form action="{{ route('returns.approve', $return->id) }}" method="POST" class="inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" 
                                        class="w-7 h-7 flex items-center justify-center rounded bg-green-100 text-green-600 hover:bg-green-200 transition-colors" 
                                        title="Duyệt">
                                    <i class="fas fa-check text-xs"></i>
                                </button>
                            </form>
                            @endif
                            @endhasPermission
                            
                            <!-- Complete -->
                            @hasPermission('returns', 'can_edit')
                            @if($return->status == 'approved')
                            <form action="{{ route('returns.complete', $return->id) }}" method="POST" class="inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" 
                                        class="w-7 h-7 flex items-center justify-center rounded bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors" 
                                        title="Xong">
                                    <i class="fas fa-check-double text-xs"></i>
                                </button>
                            </form>
                            @endif
                            @endhasPermission
                            
                            <!-- Print -->
                            @hasPermission('returns', 'can_print')
                            <a href="{{ route('returns.show', $return->id) }}" 
                               class="w-7 h-7 flex items-center justify-center rounded bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" 
                               title="In">
                                <i class="fas fa-print text-xs"></i>
                            </a>
                            @endhasPermission
                            
                            <!-- Cancel -->
                            @hasPermission('returns', 'can_cancel')
                            @if($return->status != 'cancelled' && $return->status != 'completed')
                            <form action="{{ route('returns.cancel', $return->id) }}" method="POST" class="inline" onsubmit="return confirm('Hủy phiếu?')">
                                @csrf
                                @method('PUT')
                                <button type="submit" 
                                        class="w-7 h-7 flex items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200 transition-colors" 
                                        title="Hủy">
                                    <i class="fas fa-ban text-xs"></i>
                                </button>
                            </form>
                            @endif
                            @endhasPermission
                            
                            <!-- Delete -->
                            @hasPermission('returns', 'can_delete')
                            @if($return->status == 'cancelled')
                            <form action="{{ route('returns.destroy', $return->id) }}" method="POST" class="inline" onsubmit="return confirm('Xóa phiếu?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="w-7 h-7 flex items-center justify-center rounded bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" 
                                        title="Xóa">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </form>
                            @endif
                            @endhasPermission
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Chưa có dữ liệu đổi/trả hàng</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="mt-4">
        {{ $returns->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
function recalculateSaleTotals() {
    if (confirm('Bạn có chắc chắn muốn sửa lại tổng hóa đơn cho tất cả hóa đơn có phiếu trả? Hành động này không thể hoàn tác.')) {
        fetch('{{ route("returns.recalculateSaleTotals") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi xử lý yêu cầu');
        });
    }
}
</script>
@endpush
