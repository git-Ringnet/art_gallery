@extends('layouts.app')

@section('title', 'Chi tiết khung tranh')
@section('page-title', 'Chi tiết khung tranh')

@section('content')
    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
        <div class="flex items-center justify-between mb-6">
            <h4 class="font-medium text-lg">Thông tin khung tranh</h4>
            <div class="flex gap-2">
                <a href="{{ route('frames.edit', $frame) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-edit mr-2"></i>Sửa
                </a>
                <a href="{{ route('frames.index') }}" class="border border-gray-300 hover:bg-gray-50 px-4 py-2 rounded-lg">
                    <i class="fas fa-arrow-left mr-2"></i>Quay lại
                </a>
            </div>
        </div>

        <!-- Thông tin cơ bản -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Tên khung</label>
                <p class="text-lg font-semibold">{{ $frame->name }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Giá nhập khung</label>
                <p class="text-lg font-semibold text-green-600">{{ number_format($frame->cost_price, 0) }} VNĐ</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Tổng số loại cây</label>
                <p class="text-lg font-semibold text-blue-600">{{ $frame->items->count() }} loại</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Tổng số cây sử dụng</label>
                <p class="text-lg font-semibold text-blue-600">{{ $frame->total_trees }} cây</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Tổng chiều dài</label>
                <p class="text-lg font-semibold text-blue-600">{{ number_format($frame->total_length, 2) }} cm</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Ngày tạo</label>
                <p class="text-lg">{{ $frame->created_at->format('d/m/Y H:i') }}</p>
            </div>

            @if($frame->notes)
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-500 mb-1">Ghi chú</label>
                <p class="text-gray-700 bg-gray-50 p-3 rounded-lg">{{ $frame->notes }}</p>
            </div>
            @endif
        </div>

        <!-- Chi tiết cây gỗ sử dụng -->
        <div class="border-t pt-6">
            <h5 class="font-medium text-gray-700 mb-4">Chi tiết cây gỗ sử dụng</h5>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên cây</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số lượng cây</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Chiều dài/cây</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tổng chiều dài</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dùng nguyên cây</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($frame->items as $index => $item)
                            <tr>
                                <td class="px-4 py-3 text-sm">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 text-sm font-medium">{{ $item->supply->name }}</td>
                                <td class="px-4 py-3 text-sm">{{ $item->tree_quantity }} cây</td>
                                <td class="px-4 py-3 text-sm">{{ number_format($item->length_per_tree, 2) }} {{ $item->supply->unit }}</td>
                                <td class="px-4 py-3 text-sm font-semibold">{{ number_format($item->total_length, 2) }} {{ $item->supply->unit }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if($item->use_whole_trees)
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Có</span>
                                    @else
                                        <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">Không</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6 pt-6 border-t">
            <form action="{{ route('frames.destroy', $frame) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa khung này? Vật tư sẽ được hoàn trả về kho.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-trash mr-2"></i>Xóa khung
                </button>
            </form>
        </div>
    </div>
@endsection
