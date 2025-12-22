@extends('layouts.app')

@section('title', 'Lịch sử hoạt động của tôi')
@section('page-title', 'Lịch sử hoạt động của tôi')
@section('page-description', 'Xem lại các hoạt động bạn đã thực hiện')

@section('content')
<x-alert />

<div class="bg-white rounded-xl shadow-lg p-4 fade-in">
    <!-- Filter -->
    <form method="GET" class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    <i class="fas fa-tag mr-1"></i>Loại hoạt động
                </label>
                <select name="activity_type" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Tất cả</option>
                    @foreach($activityTypes as $key => $label)
                        <option value="{{ $key }}" {{ request('activity_type') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    <i class="fas fa-calendar mr-1"></i>Từ ngày
                </label>
                <input type="date" name="from_date" value="{{ request('from_date') }}" 
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    <i class="fas fa-calendar mr-1"></i>Đến ngày
                </label>
                <input type="date" name="to_date" value="{{ request('to_date') }}" 
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-1.5 text-sm rounded-lg transition-colors">
                    <i class="fas fa-search mr-1"></i>Lọc
                </button>
                <a href="{{ route('activity-logs.my-activity') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-1.5 text-sm rounded-lg transition-colors">
                    <i class="fas fa-redo mr-1"></i>Làm mới
                </a>
            </div>
        </div>
    </form>

    <!-- Activity Logs Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white">
                <tr>
                    <th class="px-2 py-2 text-center text-xs">STT</th>
                    <th class="px-2 py-2 text-left text-xs">Thời gian</th>
                    <th class="px-2 py-2 text-left text-xs">Loại</th>
                    <th class="px-2 py-2 text-left text-xs">Module</th>
                    <th class="px-2 py-2 text-left text-xs">Mô tả</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($logs as $index => $log)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-2 py-2 text-center text-xs text-gray-600">
                        {{ ($logs->currentPage() - 1) * $logs->perPage() + $index + 1 }}
                    </td>
                    <td class="px-2 py-2 text-xs">
                        <div class="font-medium text-gray-900">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                        <div class="text-gray-500 text-xs">{{ $log->created_at->diffForHumans() }}</div>
                    </td>
                    <td class="px-2 py-2 text-xs">
                        <span class="px-2 py-1 rounded-full text-xs font-medium
                            @if($log->activity_type == 'login') bg-green-100 text-green-800
                            @elseif($log->activity_type == 'logout') bg-gray-100 text-gray-800
                            @elseif($log->activity_type == 'create') bg-blue-100 text-blue-800
                            @elseif($log->activity_type == 'update') bg-yellow-100 text-yellow-800
                            @elseif($log->activity_type == 'delete') bg-red-100 text-red-800
                            @elseif($log->activity_type == 'approve') bg-purple-100 text-purple-800
                            @elseif($log->activity_type == 'cancel') bg-orange-100 text-orange-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ $log->getActivityTypeLabel() }}
                        </span>
                    </td>
                    <td class="px-2 py-2 text-xs">
                        <span class="text-gray-700">{{ $log->getModuleLabel() }}</span>
                    </td>
                    <td class="px-2 py-2 text-xs text-gray-600">
                        {{ $log->description }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Bạn chưa có hoạt động nào</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($logs->hasPages())
    <div class="mt-4">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
