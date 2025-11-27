@extends('layouts.app')

@section('title', 'Quản lý khung tranh')
@section('page-title', 'Quản lý khung tranh')
@section('page-description', 'Danh sách khung tranh đã làm')

@section('header-actions')
    <a href="{{ route('frames.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm">
        <i class="fas fa-plus mr-2"></i>Tạo khung mới
    </a>
@endsection

@section('content')
    <div class="bg-white rounded-xl shadow-lg p-4 glass-effect">

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <!-- Search and Filter -->
        <div class="bg-gray-50 p-3 rounded-lg mb-4">
            <form method="GET" action="{{ route('frames.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @hasPermission('frames', 'can_search')
                    <div class="md:col-span-1">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Tìm kiếm</label>
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}"
                                class="w-full pl-8 pr-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Tìm theo tên khung...">
                            <i class="fas fa-search absolute left-2 top-2 text-xs text-gray-400"></i>
                        </div>
                    </div>
                    @endhasPermission
                    
                    @hasPermission('frames', 'can_filter_by_status')
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Trạng thái</label>
                        <select name="status"
                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Tất cả</option>
                            <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Còn hàng</option>
                            <option value="sold" {{ request('status') == 'sold' ? 'selected' : '' }}>Đã bán</option>
                        </select>
                    </div>
                    @endhasPermission
                </div>
                <div class="flex justify-between items-center mt-3">
                    <button type="submit"
                        class="bg-blue-600 text-white py-1.5 px-4 text-sm rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-filter mr-1"></i>Lọc
                    </button>
                    <a href="{{ route('frames.index') }}"
                        class="bg-gray-500 text-white py-1.5 px-4 text-sm rounded-lg hover:bg-gray-600 transition-colors">
                        <i class="fas fa-times mr-1"></i>Xóa lọc
                    </a>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white">
                    <tr>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">STT</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Tên khung</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Kích thước</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Chu vi</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Số loại cây</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Tổng số cây</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Giá nhập (VND/USD)</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Ngày tạo</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Trạng thái</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($frames as $index => $frame)
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">
                                {{ ($frames->currentPage() - 1) * $frames->perPage() + $index + 1 }}
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs font-medium text-gray-900">{{ $frame->name }}</td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">
                                @if($frame->frame_length && $frame->frame_width)
                                    {{ number_format($frame->frame_length, 0) }}×{{ number_format($frame->frame_width, 0) }} cm
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">
                                @if($frame->perimeter)
                                    {{ number_format($frame->perimeter, 2) }} cm
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">{{ $frame->items->count() }} loại</td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">{{ $frame->total_trees }} cây</td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">
                                <div>{{ number_format($frame->cost_price, 0) }} VNĐ</div>
                                @if($frame->cost_price_usd > 0)
                                    <div class="text-gray-500">{{ number_format($frame->cost_price_usd, 2) }} USD</div>
                                @endif
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">{{ $frame->created_at->format('d/m/Y') }}</td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                @if($frame->status == 'available')
                                    <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-green-100 text-green-800">
                                        Còn hàng
                                    </span>
                                @else
                                    <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-gray-200 text-gray-800">
                                        Đã bán
                                    </span>
                                @endif
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs">
                                <a href="{{ route('frames.show', $frame) }}" class="text-indigo-600 hover:text-indigo-900 mr-2" title="Xem chi tiết">
                                    <i class="fas fa-eye px-2 py-1.5 rounded bg-blue-100 text-blue-600 text-xs"></i>
                                </a>
                                @if($frame->status == 'available')
                                <form action="{{ route('frames.destroy', $frame) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa khung này? Vật tư sẽ được hoàn trả về kho.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Xóa khung">
                                        <i class="fas fa-trash px-2 py-1.5 rounded bg-red-100 text-red-400 text-xs"></i>
                                    </button>
                                </form>
                                @else
                                <span class="text-gray-400" title="Không thể xóa khung đã bán">
                                    <i class="fas fa-trash px-2 py-1.5 rounded bg-gray-100 text-gray-400 text-xs cursor-not-allowed"></i>
                                </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-2 py-6 text-center text-gray-500">
                                <i class="fas fa-inbox text-3xl mb-2"></i>
                                <p class="text-sm">Chưa có khung tranh nào</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $frames->links() }}
        </div>
    </div>
@endsection
