@extends('layouts.app')

@section('title', 'Quản lý nhân viên')
@section('page-title', 'Quản lý nhân viên')
@section('page-description', 'Danh sách nhân viên và tài khoản đăng nhập')

@section('header-actions')
<div class="flex gap-2">
    <div class="relative">
        <button onclick="toggleExportDropdown()" type="button" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg transition-colors flex items-center">
            <i class="fas fa-download mr-2"></i>Xuất file
            <i class="fas fa-chevron-down ml-2 text-xs"></i>
        </button>
        <div id="exportDropdown" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl z-50 border border-gray-200">
            <!-- Excel Export -->
            <div class="py-2 border-b border-gray-200">
                <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Excel</div>
                <a href="{{ route('employees.export.excel', array_merge(request()->query(), ['scope' => 'current'])) }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 transition-colors">
                    <i class="fas fa-file-excel text-green-600 mr-2"></i>Trang hiện tại
                </a>
                <a href="{{ route('employees.export.excel', array_merge(request()->query(), ['scope' => 'all'])) }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 transition-colors">
                    <i class="fas fa-file-excel text-green-600 mr-2"></i>Tất cả kết quả
                </a>
            </div>
            <!-- PDF Export -->
            <div class="py-2">
                <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">PDF</div>
                <a href="{{ route('employees.export.pdf', array_merge(request()->query(), ['scope' => 'current'])) }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-red-50 transition-colors">
                    <i class="fas fa-file-pdf text-red-600 mr-2"></i>Trang hiện tại
                </a>
                <a href="{{ route('employees.export.pdf', array_merge(request()->query(), ['scope' => 'all'])) }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-red-50 transition-colors">
                    <i class="fas fa-file-pdf text-red-600 mr-2"></i>Tất cả kết quả
                </a>
            </div>
        </div>
    </div>

    <a href="{{ route('employees.create') }}"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
        <i class="fas fa-plus mr-2"></i>Thêm nhân viên
    </a>
</div>
@endsection

@section('content')
<!-- Success Message -->
@if (session('success'))
<div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center">
    <i class="fas fa-check-circle mr-2"></i>
    {{ session('success') }}
</div>
@endif

<!-- Error Message -->
@if (session('error'))
<div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center">
    <i class="fas fa-exclamation-circle mr-2"></i>
    {{ session('error') }}
</div>
@endif

<div class="bg-white rounded-xl shadow-lg p-6 glass-effect"></div>
<!-- Search and Filter -->
<div class="bg-gray-50 p-4 rounded-lg mb-6">
    <form method="GET" action="{{ route('employees.index') }}">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Tìm kiếm</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Tìm theo tên, email, số điện thoại...">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái</label>
                <select name="status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Tất cả</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Hoạt động</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Ngừng hoạt động</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Từ ngày</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
        </div>
        <div class="flex justify-between items-center mt-4">
            <button type="submit"
                class="bg-blue-600 text-white py-2 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-filter mr-2"></i>Lọc
            </button>
            <a href="{{ route('employees.index') }}"
                class="bg-gray-500 text-white py-2 px-6 rounded-lg hover:bg-gray-600 transition-colors">
                <i class="fas fa-times mr-2"></i>Xóa lọc
            </a>
        </div>
    </form>
</div>

<!-- Employees Table -->
<div class="overflow-x-auto">
    <table class="w-full">
        <thead class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white">
            <tr>
                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider">STT</th>
                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider">Ảnh</th>
                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider">Tên nhân viên</th>
                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider">Email</th>
                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider">Số điện thoại</th>
                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider">Trạng thái</th>
                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider">Ngày tạo</th>
                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider">Thao tác</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($employees as $index => $employee)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ ($employees->currentPage() - 1) * $employees->perPage() + $index + 1 }}
                </td>
                <td class="px-4 py-4 whitespace-nowrap">
                    @if($employee->avatar)
                    <img src="{{ asset('storage/' . $employee->avatar) }}"
                        alt="{{ $employee->name }}"
                        class="w-10 h-10 rounded-full object-cover">
                    @else
                    <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold">
                        {{ strtoupper(substr($employee->name, 0, 1)) }}
                    </div>
                    @endif
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $employee->name }}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $employee->email }}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $employee->phone ?? '-' }}</td>
                <td class="px-4 py-4 whitespace-nowrap">
                    @if($employee->is_active)
                    <span class="px-2 py-1 text-xs font-semibold rounded-lg bg-green-100 text-green-800">
                        Hoạt động
                    </span>
                    @else
                    <span class="px-2 py-1 text-xs font-semibold rounded-lg bg-red-100 text-red-800">
                        Ngừng hoạt động
                    </span>
                    @endif
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ $employee->created_at->format('d/m/Y') }}
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm">
                    <a href="{{ route('employees.show', $employee->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-2" title="Xem chi tiết">
                        <i class="fas fa-eye px-3 py-2 rounded-lg bg-blue-100 text-blue-600"></i>
                    </a>
                    <a href="{{ route('employees.edit', $employee->id) }}" class="text-yellow-600 hover:text-yellow-900 mr-2" title="Chỉnh sửa">
                        <i class="fas fa-edit px-3 py-2 rounded-lg bg-yellow-100 text-yellow-600"></i>
                    </a>

                    @if(auth()->check() && $employee->id !== auth()->user()->id)
                    <form action="{{ route('employees.toggle-status', $employee->id) }}" method="POST" class="inline">
                        @csrf
                        @if($employee->is_active)
                        <button type="submit" class="text-orange-600 hover:text-orange-900 mr-2" title="Vô hiệu hóa tài khoản" onclick="return confirm('Vô hiệu hóa tài khoản này?')">
                            <i class="fas fa-lock px-3 py-2 rounded-lg bg-orange-100 text-orange-600"></i>
                        </button>
                        @else
                        <button type="submit" class="text-green-600 hover:text-green-900 mr-2" title="Kích hoạt tài khoản" onclick="return confirm('Kích hoạt tài khoản này?')">
                            <i class="fas fa-unlock px-3 py-2 rounded-lg bg-green-100 text-green-600"></i>
                        </button>
                        @endif
                    </form>
                    @endif

                    <form action="{{ route('employees.destroy', $employee->id) }}" method="POST" class="inline" onsubmit="return confirm('Xóa nhân viên này?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-900" title="Xóa">
                            <i class="fas fa-trash px-3 py-2 rounded-lg bg-red-100 text-red-400"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                    <i class="fas fa-users text-4xl mb-2"></i>
                    <p>Không có dữ liệu</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">
    {{ $employees->links() }}
</div>
</div>
@endsection

@push('scripts')
<script>
    function toggleExportDropdown() {
        const dropdown = document.getElementById('exportDropdown');
        dropdown.classList.toggle('hidden');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('exportDropdown');
        const button = event.target.closest('[onclick="toggleExportDropdown()"]');

        if (dropdown && !dropdown.contains(event.target) && !button) {
            dropdown.classList.add('hidden');
        }
    });
</script>
@endpush