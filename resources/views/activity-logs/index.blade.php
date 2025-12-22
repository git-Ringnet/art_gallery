@extends('layouts.app')

@section('title', 'Nhật ký hoạt động')
@section('page-title', 'Nhật ký hoạt động')
@section('page-description', 'Theo dõi các hoạt động của người dùng trong hệ thống')

@section('content')
<x-alert />

<div class="bg-white rounded-xl shadow-lg p-4 fade-in">
    <!-- Export Buttons -->
    <div class="mb-4 flex justify-end space-x-2">
        <a href="{{ route('activity-logs.export.excel', request()->all()) }}" 
            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 text-sm rounded-lg transition-colors">
            <i class="fas fa-file-excel mr-1"></i>Xuất Excel
        </a>
        <a href="{{ route('activity-logs.export.pdf', request()->all()) }}" 
            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 text-sm rounded-lg transition-colors">
            <i class="fas fa-file-pdf mr-1"></i>Xuất PDF
        </a>
    </div>

    <!-- Search & Filter -->
    <form method="GET" class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    <i class="fas fa-search mr-1"></i>Tìm kiếm
                </label>
                <input type="text" name="search" value="{{ request('search') }}" 
                    placeholder="Mô tả hoạt động..." 
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    <i class="fas fa-user mr-1"></i>Người dùng
                </label>
                <select name="user_id" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Tất cả</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

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
                    <i class="fas fa-cube mr-1"></i>Module
                </label>
                <select name="module" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Tất cả</option>
                    @foreach($modules as $key => $label)
                        <option value="{{ $key }}" {{ request('module') == $key ? 'selected' : '' }}>
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

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    <i class="fas fa-network-wired mr-1"></i>IP Address
                </label>
                <input type="text" name="ip_address" value="{{ request('ip_address') }}" 
                    placeholder="192.168.1.1" 
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-1.5 text-sm rounded-lg transition-colors">
                    <i class="fas fa-search mr-1"></i>Lọc
                </button>
                <a href="{{ route('activity-logs.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-1.5 text-sm rounded-lg transition-colors">
                    <i class="fas fa-redo mr-1"></i>Làm mới
                </a>
            </div>
        </div>

        <div class="mt-2">
            <label class="inline-flex items-center">
                <input type="checkbox" name="is_suspicious" value="1" {{ request('is_suspicious') == '1' ? 'checked' : '' }} 
                    class="rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                <span class="ml-2 text-sm text-gray-700">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-1"></i>Chỉ hiển thị hoạt động đáng ngờ
                </span>
            </label>
        </div>
    </form>

    <!-- Activity Logs Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white">
                <tr>
                    <th class="px-2 py-2 text-center text-xs">STT</th>
                    <th class="px-2 py-2 text-left text-xs">Thời gian</th>
                    <th class="px-2 py-2 text-left text-xs">Người dùng</th>
                    <th class="px-2 py-2 text-left text-xs">Loại</th>
                    <th class="px-2 py-2 text-left text-xs">Module</th>
                    <th class="px-2 py-2 text-left text-xs">Mô tả</th>
                    <th class="px-2 py-2 text-left text-xs">IP Address</th>
                    <th class="px-2 py-2 text-center text-xs">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($logs as $index => $log)
                <tr class="hover:bg-gray-50 transition-colors {{ $log->is_suspicious ? 'bg-red-50' : '' }}">
                    <td class="px-2 py-2 text-center text-xs text-gray-600">
                        {{ ($logs->currentPage() - 1) * $logs->perPage() + $index + 1 }}
                    </td>
                    <td class="px-2 py-2 text-xs">
                        <div class="font-medium text-gray-900">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                        <div class="text-gray-500 text-xs">{{ $log->created_at->diffForHumans() }}</div>
                    </td>
                    <td class="px-2 py-2 text-xs">
                        @if($log->user)
                            <div class="font-medium text-gray-900">{{ $log->user->name }}</div>
                            <div class="text-gray-500 text-xs">{{ $log->user->email }}</div>
                        @else
                            <span class="text-gray-400">Hệ thống</span>
                        @endif
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
                        {{ Str::limit($log->description, 50) }}
                        @if($log->is_suspicious)
                            <i class="fas fa-exclamation-triangle text-red-500 ml-1" title="Hoạt động đáng ngờ"></i>
                        @endif
                    </td>
                    <td class="px-2 py-2 text-xs text-gray-600">
                        {{ $log->ip_address }}
                    </td>
                    <td class="px-2 py-2 text-center">
                        <a href="{{ route('activity-logs.show', $log->id) }}" 
                            class="text-blue-600 hover:text-blue-800 text-xs">
                            <i class="fas fa-eye"></i> Chi tiết
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Không có nhật ký hoạt động nào</p>
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
