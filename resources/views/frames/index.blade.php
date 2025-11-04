@extends('layouts.app')

@section('title', 'Quản lý khung tranh')
@section('page-title', 'Quản lý khung tranh')
@section('page-description', 'Danh sách khung tranh đã làm')

@section('content')
    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
        <div class="flex items-center justify-between mb-6">
            <h4 class="font-medium text-lg">Danh sách khung tranh</h4>
            <a href="{{ route('frames.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                <i class="fas fa-plus mr-2"></i>Tạo khung mới
            </a>
        </div>

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

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên khung</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số loại cây</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tổng số cây</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tổng chiều dài</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Giá nhập</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày tạo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($frames as $frame)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $frame->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ $frame->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $frame->items->count() }} loại</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $frame->total_trees }} cây</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ number_format($frame->total_length, 2) }} cm</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ number_format($frame->cost_price, 0) }} VNĐ</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $frame->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('frames.show', $frame) }}" class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-eye px-3 py-2 rounded-lg bg-blue-100 text-blue-600"></i>
                                </a>
                                <a href="{{ route('frames.edit', $frame) }}" class="text-yellow-600 hover:text-yellow-900 mr-3">
                                    <i class="fas fa-edit px-3 py-2 rounded-lg bg-yellow-100 text-yellow-600"></i>
                                </a>
                                <form action="{{ route('frames.destroy', $frame) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa khung này? Vật tư sẽ được hoàn trả về kho.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash px-3 py-2 rounded-lg bg-red-100 text-red-400"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">Chưa có khung tranh nào</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $frames->links() }}
        </div>
    </div>
@endsection
