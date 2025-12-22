@extends('layouts.app')

@section('title', 'Chi tiết nhật ký hoạt động')
@section('page-title', 'Chi tiết nhật ký hoạt động')

@section('header-actions')
<a href="{{ route('activity-logs.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1.5 text-sm rounded-lg transition-colors flex items-center space-x-1">
    <i class="fas fa-arrow-left"></i>
    <span>Quay lại</span>
</a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6 fade-in">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Basic Information -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                <i class="fas fa-info-circle text-blue-500 mr-2"></i>Thông tin cơ bản
            </h3>
            
            <div class="space-y-3">
                <div>
                    <label class="text-sm font-medium text-gray-600">Thời gian:</label>
                    <p class="text-sm text-gray-900">{{ $log->created_at->format('d/m/Y H:i:s') }}</p>
                    <p class="text-xs text-gray-500">{{ $log->created_at->diffForHumans() }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Người dùng:</label>
                    @if($log->user)
                        <p class="text-sm text-gray-900">{{ $log->user->name }}</p>
                        <p class="text-xs text-gray-500">{{ $log->user->email }}</p>
                    @else
                        <p class="text-sm text-gray-500">Hệ thống</p>
                    @endif
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Loại hoạt động:</label>
                    <p class="text-sm">
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
                    </p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Module:</label>
                    <p class="text-sm text-gray-900">{{ $log->getModuleLabel() }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Mô tả:</label>
                    <p class="text-sm text-gray-900">{{ $log->description }}</p>
                </div>

                @if($log->is_suspicious)
                <div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Hoạt động đáng ngờ
                    </span>
                </div>
                @endif

                @if($log->is_important)
                <div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                        <i class="fas fa-star mr-2"></i>
                        Hoạt động quan trọng
                    </span>
                </div>
                @endif
            </div>
        </div>

        <!-- Technical Information -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                <i class="fas fa-server text-blue-500 mr-2"></i>Thông tin kỹ thuật
            </h3>
            
            <div class="space-y-3">
                <div>
                    <label class="text-sm font-medium text-gray-600">IP Address:</label>
                    <p class="text-sm text-gray-900 font-mono">{{ $log->ip_address }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">User Agent:</label>
                    <p class="text-sm text-gray-900 break-all">{{ $log->user_agent }}</p>
                </div>

                @if($log->subject_type && $log->subject_id)
                <div>
                    <label class="text-sm font-medium text-gray-600">Đối tượng:</label>
                    <p class="text-sm text-gray-900">{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</p>
                    @if($log->subject)
                        <p class="text-xs text-blue-600 mt-1">
                            <i class="fas fa-link mr-1"></i>Đối tượng vẫn tồn tại
                        </p>
                    @else
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-unlink mr-1"></i>Đối tượng đã bị xóa
                        </p>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Changes (for update operations) -->
    @if($log->changes && count($log->changes) > 0)
    <div class="mt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
            <i class="fas fa-exchange-alt text-blue-500 mr-2"></i>Thay đổi
        </h3>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Trường</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Giá trị cũ</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Giá trị mới</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($log->changes as $field => $change)
                    <tr>
                        <td class="px-4 py-2 font-medium text-gray-900">{{ $field }}</td>
                        <td class="px-4 py-2 text-gray-600">
                            <code class="bg-red-50 px-2 py-1 rounded text-xs">
                                {{ is_array($change['old']) ? json_encode($change['old']) : $change['old'] }}
                            </code>
                        </td>
                        <td class="px-4 py-2 text-gray-600">
                            <code class="bg-green-50 px-2 py-1 rounded text-xs">
                                {{ is_array($change['new']) ? json_encode($change['new']) : $change['new'] }}
                            </code>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Properties (additional data) -->
    @if($log->properties && count($log->properties) > 0)
    <div class="mt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
            <i class="fas fa-database text-blue-500 mr-2"></i>Dữ liệu bổ sung
        </h3>
        
        <div class="bg-gray-50 p-4 rounded-lg">
            <pre class="text-xs text-gray-700 overflow-x-auto">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>
    @endif
</div>
@endsection
