@extends('layouts.app')

@section('title', 'Phân quyền')
@section('page-title', 'Phân quyền')
@section('page-description', 'Quản lý vai trò và quyền truy cập')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Role Selection -->
    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
        <h4 class="font-semibold mb-4">Chọn vai trò</h4>
        <select id="role-select" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent mb-4">
            @foreach($roles as $role)
                <option value="{{ $role['name'] }}">{{ $role['name'] }}</option>
            @endforeach
        </select>
        
        <div class="mt-6">
            <h5 class="font-medium mb-3">Tạo vai trò mới</h5>
            <form action="{{ route('permissions.roles.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Tên vai trò...">
                </div>
                <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Tạo vai trò
                </button>
            </form>
        </div>
    </div>

    <!-- Permissions -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-6 glass-effect">
        <h4 class="font-semibold mb-4">Quyền truy cập</h4>
        <form id="permissions-form" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-3 mb-6" id="permissions-list">
                @foreach($modules as $key => $label)
                    <label class="inline-flex items-center space-x-2 p-3 bg-gray-50 border rounded-lg hover:bg-gray-100 cursor-pointer">
                        <input type="checkbox" name="permissions[]" value="{{ $key }}" class="permission-checkbox">
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-save mr-2"></i>Lưu quyền
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const rolesData = @json($roles);

document.getElementById('role-select').addEventListener('change', function() {
    const roleName = this.value;
    const role = rolesData.find(r => r.name === roleName);
    
    // Update form action
    document.getElementById('permissions-form').action = `/permissions/roles/${encodeURIComponent(roleName)}`;
    
    // Update checkboxes
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = role && role.permissions.includes(checkbox.value);
    });
});

// Trigger initial load
document.getElementById('role-select').dispatchEvent(new Event('change'));
</script>
@endpush
