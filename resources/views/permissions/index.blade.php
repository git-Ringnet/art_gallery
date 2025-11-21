@extends('layouts.app')
@section('title', 'Phân quyền')
@section('page-title', 'Phân quyền')
@section('page-description', 'Quản lý vai trò và quyền truy cập chi tiết')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/permissions.css') }}">

@endpush

@section('content')
    @if(session('success'))
        <div class="alert-success p-3 text-xs">
            <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert-error p-3 text-xs">
            <i class="fas fa-exclamation-circle mr-1"></i>{{ session('error') }}
        </div>
    @endif

    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-lg">
            <div class="border-b-2 border-gray-200">
                <nav class="flex flex-wrap -mb-px">
                    <button onclick="switchTab('roles')" id="tab-roles"
                        class="tab-button active px-4 py-2 text-sm font-bold border-b-2 border-blue-600 text-blue-700 flex items-center gap-1.5">
                        <i class="fas fa-user-tag text-sm"></i>
                        <span>Quản lý vai trò</span>
                    </button>
                    <button onclick="switchTab('permissions')" id="tab-permissions"
                        class="tab-button px-4 py-2 text-sm font-bold border-b-2 border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300 flex items-center gap-1.5">
                        <i class="fas fa-shield-alt text-sm"></i>
                        <span>Phân quyền chi tiết</span>
                    </button>
                    <button onclick="switchTab('users')" id="tab-users"
                        class="tab-button px-4 py-2 text-sm font-bold border-b-2 border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300 flex items-center gap-1.5">
                        <i class="fas fa-users text-sm"></i>
                        <span>Gán vai trò người dùng</span>
                    </button>
                </nav>
            </div>
        </div>

        <!-- Tạo vai trò -->
        <div id="content-roles" class="tab-content">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl shadow-lg p-4">
                    <h4 class="font-semibold mb-3 text-sm flex items-center gap-1.5">
                        <i class="fas fa-plus-circle text-green-600 text-sm"></i>
                        <span>Tạo vai trò mới</span>
                    </h4>
                    <form action="{{ route('permissions.roles.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Tên vai trò</label>
                            <input type="text" name="name" required
                                class="px-2 py-1.5 text-sm w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="VD: Nhân viên bán hàng">
                        </div>
                        <div class="mb-3">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Mô tả</label>
                            <textarea name="description" rows="3"
                                class="px-2 py-1.5 text-sm w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Mô tả vai trò..."></textarea>
                        </div>
                        <button type="submit"
                            class="px-3 py-1.5 text-xs w-full bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold flex items-center justify-center gap-1.5">
                            <i class="fas fa-plus"></i>
                            <span>Tạo vai trò</span>
                        </button>
                    </form>
                </div>

                <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-semibold text-sm flex items-center gap-1.5">
                            <i class="fas fa-list text-blue-600 text-sm"></i>
                            <span>Danh sách vai trò</span>
                        </h4>
                        <div class="relative w-48">
                            <input type="text" id="search-roles" placeholder="Tìm kiếm vai trò..."
                                class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pl-7"
                                onkeyup="filterRoles()">
                            <i class="fas fa-search absolute left-2 top-2 text-gray-400 text-xs"></i>
                        </div>
                    </div>
                    <div class="space-y-3" id="roles-list">
                        @forelse($roles as $role)
                            <div class="role-item role-card border border-gray-200 rounded-lg hover:shadow-lg transition-shadow"
                                data-name="{{ strtolower($role->name) }}"
                                data-description="{{ strtolower($role->description) }}">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1 p-3">
                                        <h5 class="font-bold text-gray-900 text-sm mb-1">{{ $role->name }}</h5>
                                        <p class="text-xs text-gray-600 mb-2">{{ $role->description }}</p>
                                        <div class="flex flex-wrap gap-1.5">
                                            @php
                                                // Lấy danh sách modules từ rolePermissions (có ít nhất 1 quyền = true)
                                                $activeModules = $role->rolePermissions->filter(function ($rp) {
                                                    return $rp->can_view || $rp->can_create || $rp->can_edit ||
                                                        $rp->can_delete || $rp->can_export || $rp->can_import ||
                                                        $rp->can_print || $rp->can_approve || $rp->can_cancel;
                                                })->pluck('permission');
                                            @endphp
                                            @foreach($activeModules as $permission)
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                                    {{ $modules[$permission->module] ?? $permission->module }}
                                                </span>
                                            @endforeach
                                            @if($activeModules->isEmpty())
                                                <span class="text-xs text-gray-500 italic">Chưa có quyền nào</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex gap-1.5 ml-3">
                                        <button onclick="editRole({{ $role->id }})"
                                            class="text-blue-600 hover:text-blue-800 p-1 rounded-lg transition-colors">
                                            <i class="fas fa-edit px-2 py-1.5 rounded-lg bg-yellow-100 text-yellow-600"></i>
                                        </button>
                                        <form action="{{ route('permissions.roles.delete', $role->id) }}" method="POST"
                                            class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa vai trò này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-red-600 hover:text-red-800 p-1 rounded-lg transition-colors">
                                                <i class="fas fa-trash px-2 py-1.5 rounded-lg bg-red-100 text-red-400"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 text-center py-6 text-sm">Chưa có vai trò nào</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <!-- Phân quyền chi tiết -->
        <div id="content-permissions" class="tab-content hidden">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow-lg p-4">
                    <h4 class="font-semibold mb-3 text-sm flex items-center gap-1.5">
                        <i class="fas fa-user-tag text-purple-600 text-sm"></i>
                        <span>Chọn vai trò</span>
                    </h4>
                    <select id="role-select-permissions" onchange="loadRolePermissions(this.value)"
                        class="px-2 py-1.5 text-sm w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Chọn vai trò --</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-3 bg-white rounded-xl shadow-lg p-4">
                    <h4 class="font-semibold mb-3 text-sm flex items-center gap-1.5">
                        <i class="fas fa-shield-alt text-blue-600 text-sm"></i>
                        <span>Phân quyền chi tiết</span>
                    </h4>
                    <div id="permissions-container" class="text-gray-500 text-center py-6 text-sm">
                        Vui lòng chọn vai trò để phân quyền
                    </div>
                </div>
            </div>
        </div>
        <!-- Gán quyền -->
        <div id="content-users" class="tab-content hidden">
            <div class="bg-white rounded-xl shadow-lg p-4">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="font-semibold text-sm flex items-center gap-1.5">
                        <i class="fas fa-users text-green-600 text-sm"></i>
                        <span>Gán vai trò cho người dùng</span>
                    </h4>
                    <div class="flex gap-2">
                        <div class="relative w-48">
                            <input type="text" id="search-users" placeholder="Tìm kiếm người dùng..."
                                class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pl-7"
                                onkeyup="filterUsers()">
                            <i class="fas fa-search absolute left-2 top-2 text-gray-400 text-xs"></i>
                        </div>
                        <select id="filter-role"
                            class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            onchange="filterUsers()">
                            <option value="">Tất cả vai trò</option>
                            <option value="null">Chưa gán</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="overflow-x-auto"></div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase">Tên</th>
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase">Email</th>
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase">Vai trò hiện tại</th>
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase">Gán vai trò</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($users as $user)
                            @if($user->email === 'admin@example.com')
                                @continue
                            @endif
                            <tr class="user-row hover:bg-gray-50" data-name="{{ strtolower($user->name) }}"
                                data-email="{{ strtolower($user->email) }}" data-role="{{ $user->role_id ?? 'null' }}"></tr>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            @if($user->avatar)
                                                <img class="h-8 w-8 rounded-full" src="{{ asset('storage/' . $user->avatar) }}"
                                                    alt="">
                                            @else
                                                <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                                    <i class="fas fa-user text-gray-600 text-xs"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="text-xs font-semibold text-gray-900">{{ $user->name }}</div>
                                    </div>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-600">
                                    {{ $user->email }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    @if($user->role)
                                        <span
                                            class="px-2 py-1 inline-flex text-xs font-bold rounded-full bg-green-100 text-green-800">
                                            {{ $user->role->name }}
                                        </span>
                                    @else
                                        <span
                                            class="px-2 py-1 inline-flex text-xs font-bold rounded-full bg-gray-100 text-gray-800">
                                            Chưa gán
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <select onchange="assignRole({{ $user->id }}, this.value)"
                                        class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="" {{ !$user->role_id ? 'selected' : '' }}>-- Chưa gán vai trò --</option>
                                        <option value="remove" {{ $user->role_id ? '' : 'disabled' }}>Hủy gán vai trò</option>
                                        <option disabled>──────────</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>
                                                {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-gray-500 text-sm">Chưa có người dùng nào</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div id="editRoleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-4 border w-full max-w-lg shadow-2xl rounded-xl bg-white">
                <div class="mt-2">
                    <h3 class="text-sm font-bold leading-6 text-gray-900 mb-3">Chỉnh sửa vai trò</h3>
                    <form id="editRoleForm" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Tên vai trò</label>
                            <input type="text" name="name" id="edit-role-name" required
                                class="px-2 py-1.5 text-sm w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="mb-3">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Mô tả</label>
                            <textarea name="description" id="edit-role-description" rows="3"
                                class="px-2 py-1.5 text-sm w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                class="px-3 py-1.5 text-xs flex-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold flex items-center justify-center gap-1.5">
                                <i class="fas fa-save"></i>
                                <span>Lưu</span>
                            </button>
                            <button type="button" onclick="closeEditModal()"
                                class="px-3 py-1.5 text-xs flex-1 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors font-semibold">
                                Hủy
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Cấu Hình Phạm Vi Dữ Liệu -->
        <div id="dataScopeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-6 border w-full max-w-2xl shadow-2xl rounded-xl bg-white">
                <div class="mt-2">
                    <h3 class="text-lg font-bold leading-6 text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-shield-alt text-purple-600"></i>
                        <span>Cấu hình phạm vi dữ liệu: <span id="modal-module-name"></span></span>
                    </h3>
                    
                    <form id="dataScopeForm">
                        <input type="hidden" id="modal-module-key" name="module">
                        
                        <!-- Phạm vi dữ liệu -->
                        <div id="data-scope-section" class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-database mr-1"></i>Phạm vi dữ liệu
                            </label>
                            <select id="data-scope-select" name="data_scope" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                    onchange="toggleShowroomSelector()">
                                <option value="all">Xem tất cả dữ liệu</option>
                                <option value="own">Chỉ xem dữ liệu của chính mình</option>
                                <option value="showroom">Xem theo showroom được phép</option>
                                <option value="none">Không xem được gì</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-info-circle"></i> 
                                Kiểm soát người dùng có thể xem dữ liệu nào
                            </p>
                        </div>
                        
                        <!-- Chọn showroom (hiện khi chọn 'showroom') -->
                        <div id="showroom-selector" class="mb-4 hidden">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-store mr-1"></i>Chọn showroom được phép
                            </label>
                            <select id="allowed-showrooms-select" name="allowed_showrooms" multiple 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                    size="5">
                                @foreach(\App\Models\Showroom::all() as $showroom)
                                    <option value="{{ $showroom->id }}">{{ $showroom->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-info-circle"></i> 
                                Giữ Ctrl (hoặc Cmd) để chọn nhiều showroom
                            </p>
                        </div>
                        
                        <!-- Quyền lọc/tìm kiếm -->
                        <div id="filter-permissions-section" class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-filter mr-1"></i>Quyền lọc và tìm kiếm
                            </label>
                            <div class="space-y-2 bg-gray-50 p-3 rounded-lg">
                                <label id="can-search-wrapper" class="flex items-center gap-2 cursor-pointer hover:bg-gray-100 p-2 rounded">
                                    <input type="checkbox" id="can-search" name="can_search" class="w-4 h-4">
                                    <span class="text-sm">
                                        Được phép tìm kiếm
                                    </span>
                                </label>
                                <label id="can-filter-showroom-wrapper" class="flex items-center gap-2 cursor-pointer hover:bg-gray-100 p-2 rounded">
                                    <input type="checkbox" id="can-filter-showroom" name="can_filter_by_showroom" class="w-4 h-4">
                                    <span class="text-sm">
                                        Được phép lọc theo showroom
                                    </span>
                                </label>
                                <label id="can-filter-user-wrapper" class="flex items-center gap-2 cursor-pointer hover:bg-gray-100 p-2 rounded">
                                    <input type="checkbox" id="can-filter-user" name="can_filter_by_user" class="w-4 h-4">
                                    <span class="text-sm">
                                        Được phép lọc theo nhân viên
                                    </span>
                                </label>
                                <label id="can-filter-date-wrapper" class="flex items-center gap-2 cursor-pointer hover:bg-gray-100 p-2 rounded">
                                    <input type="checkbox" id="can-filter-date" name="can_filter_by_date" class="w-4 h-4">
                                    <span class="text-sm">
                                        Được phép lọc theo ngày
                                    </span>
                                </label>
                                <label id="can-filter-status-wrapper" class="flex items-center gap-2 cursor-pointer hover:bg-gray-100 p-2 rounded">
                                    <input type="checkbox" id="can-filter-status" name="can_filter_by_status" class="w-4 h-4">
                                    <span class="text-sm">
                                        Được phép lọc theo trạng thái
                                    </span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Buttons -->
                        <div class="flex gap-2 mt-6">
                            <button type="button" onclick="saveDataScope()" 
                                    class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-semibold flex items-center justify-center gap-2">
                                <i class="fas fa-save"></i>
                                <span>Lưu cấu hình</span>
                            </button>
                            <button type="button" onclick="closeDataScopeModal()" 
                                    class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors font-semibold">
                                Hủy
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div id="addFieldModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-4 border w-full max-w-2xl shadow-2xl rounded-xl bg-white">
                <div class="mt-2">
                    <h3 class="text-sm font-bold leading-6 text-gray-900 mb-3">Thêm trường tùy chỉnh</h3>
                    <form id="addFieldForm" onsubmit="addCustomField(event)">
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Module</label>
                                <select name="module" id="add-field-module" required
                                    class="px-2 py-1.5 text-sm w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">-- Chọn module --</option>
                                    @foreach($modules as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Tên trường (key)</label>
                                <input type="text" name="field_name" required
                                    class="px-2 py-1.5 text-sm w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="VD: custom_warranty">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Nhãn hiển thị</label>
                                <input type="text" name="field_label" required
                                    class="px-2 py-1.5 text-sm w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="VD: Thời gian bảo hành">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Loại trường</label>
                                <select name="field_type" required
                                    class="px-2 py-1.5 text-sm w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="text">Text</option>
                                    <option value="number">Number</option>
                                    <option value="date">Date</option>
                                    <option value="textarea">Textarea</option>
                                    <option value="select">Select</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Tùy chọn (cho Select, phân cách
                                bằng
                                dấu phẩy)</label>
                            <input type="text" name="field_options"
                                class="px-2 py-1.5 text-sm w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="VD: 6 tháng,12 tháng,24 tháng">
                        </div>
                        <div class="mb-3">
                            <label class="inline-flex items-center gap-1.5">
                                <input type="checkbox" name="is_required" class="w-3.5 h-3.5">
                                <span class="text-xs font-semibold text-gray-700">Bắt buộc nhập</span>
                            </label>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                class="px-3 py-1.5 text-xs flex-1 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold flex items-center justify-center gap-1.5">
                                <i class="fas fa-plus"></i>
                                <span>Thêm trường</span>
                            </button>
                            <button type="button" onclick="closeAddFieldModal()"
                                class="px-3 py-1.5 text-xs flex-1 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors font-semibold">
                                Hủy
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

@endsection

    @push('scripts')
        <script>
            window.permissionsModules = {!! json_encode($modules) !!};
        </script>
        <script src="{{ asset('js/permissions.js') }}"></script>
    @endpush