@extends('layouts.app')

@section('title', 'Chi tiết nhân viên')
@section('page-title', 'Chi tiết nhân viên')
@section('page-description', 'Thông tin chi tiết nhân viên')

@section('header-actions')
    <div class="flex gap-2">
        <a href="{{ route('employees.edit', $employee->id) }}"
            class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
            <i class="fas fa-edit mr-2"></i>Chỉnh sửa
        </a>
        <a href="{{ route('employees.index') }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
    </div>
@endsection

@section('content')
    <div class="bg-white rounded-xl shadow-lg p-4 glass-effect">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Avatar Section -->
            <div class="md:col-span-1 flex flex-col items-center">
                @if($employee->avatar)
                    <img src="{{ asset('storage/' . $employee->avatar) }}" 
                         alt="{{ $employee->name }}" 
                         class="w-32 h-32 rounded-full object-cover shadow-lg mb-3">
                @else
                    <div class="w-32 h-32 rounded-full bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center text-white text-4xl font-bold shadow-lg mb-3">
                        {{ strtoupper(substr($employee->name, 0, 1)) }}
                    </div>
                @endif
                
                <div class="text-center">
                    @if($employee->is_active)
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>Hoạt động
                        </span>
                    @else
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i>Ngừng hoạt động
                        </span>
                    @endif
                </div>
            </div>

            <!-- Information Section -->
            <div class="md:col-span-2">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Thông tin cá nhân</h3>
                
                <div class="space-y-3">
                    <div class="flex border-b border-gray-200 pb-2">
                        <div class="w-1/3 text-xs text-gray-600 font-medium">
                            <i class="fas fa-user mr-1 text-blue-500"></i>Tên nhân viên:
                        </div>
                        <div class="w-2/3 text-xs text-gray-900 font-semibold">{{ $employee->name }}</div>
                    </div>

                    <div class="flex border-b border-gray-200 pb-2">
                        <div class="w-1/3 text-xs text-gray-600 font-medium">
                            <i class="fas fa-envelope mr-1 text-blue-500"></i>Email:
                        </div>
                        <div class="w-2/3 text-xs text-gray-900">{{ $employee->email }}</div>
                    </div>

                    <div class="flex border-b border-gray-200 pb-2">
                        <div class="w-1/3 text-xs text-gray-600 font-medium">
                            <i class="fas fa-phone mr-1 text-blue-500"></i>Số điện thoại:
                        </div>
                        <div class="w-2/3 text-xs text-gray-900">{{ $employee->phone ?? '-' }}</div>
                    </div>

                    <div class="flex border-b border-gray-200 pb-2">
                        <div class="w-1/3 text-xs text-gray-600 font-medium">
                            <i class="fas fa-calendar-plus mr-1 text-blue-500"></i>Ngày tạo:
                        </div>
                        <div class="w-2/3 text-xs text-gray-900">{{ $employee->created_at->format('d/m/Y H:i') }}</div>
                    </div>

                    <div class="flex border-b border-gray-200 pb-2">
                        <div class="w-1/3 text-xs text-gray-600 font-medium">
                            <i class="fas fa-clock mr-1 text-blue-500"></i>Cập nhật lần cuối:
                        </div>
                        <div class="w-2/3 text-xs text-gray-900">{{ $employee->updated_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-4 pt-4 border-t border-gray-200 flex justify-end gap-2">
            <form action="{{ route('employees.destroy', $employee->id) }}" method="POST" 
                  onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhân viên này?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="bg-red-600 text-white px-4 py-1.5 text-sm rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-trash mr-1"></i>Xóa nhân viên
                </button>
            </form>
        </div>
    </div>
@endsection
