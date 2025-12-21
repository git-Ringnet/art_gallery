// Permissions Management JavaScript

let currentRoleId = null;
let currentPermissions = {};
let currentFieldPermissions = {};
let allModuleFields = {};

// Định nghĩa các tính năng lọc/tìm kiếm cho từng module
const moduleFilterFeatures = {
    'dashboard': {
        hasSearch: false,
        hasShowroomFilter: false,
        hasUserFilter: false,
        hasDateFilter: true,
        hasStatusFilter: false,
        hasDataScope: false, // Dashboard không cần phạm vi dữ liệu
    },
    'sales': {
        hasSearch: true,
        hasShowroomFilter: true,
        hasUserFilter: true,
        hasDateFilter: true,
        hasStatusFilter: true,
        hasDataScope: true,
    },
    'debt': {
        hasSearch: true,
        hasShowroomFilter: false,
        hasUserFilter: false,
        hasDateFilter: true,
        hasStatusFilter: true,
        hasDataScope: true,
    },
    'returns': {
        hasSearch: true,
        hasShowroomFilter: true,
        hasUserFilter: true,
        hasDateFilter: true,
        hasStatusFilter: true,
        hasDataScope: true,
    },
    'inventory': {
        hasSearch: true,
        hasShowroomFilter: false,
        hasUserFilter: false,
        hasDateFilter: true,
        hasStatusFilter: false,
        hasDataScope: false,
    },
    'frames': {
        hasSearch: true,
        hasShowroomFilter: false,
        hasUserFilter: false,
        hasDateFilter: false,
        hasStatusFilter: true,
        hasDataScope: false,
    },
    'showrooms': {
        hasSearch: true,
        hasShowroomFilter: false,
        hasUserFilter: false,
        hasDateFilter: false,
        hasStatusFilter: true,
        hasDataScope: false,
    },
    'customers': {
        hasSearch: true,
        hasShowroomFilter: false,
        hasUserFilter: false,
        hasDateFilter: true,
        hasStatusFilter: false,
        hasDataScope: false,
    },
    'employees': {
        hasSearch: true,
        hasShowroomFilter: false,
        hasUserFilter: false,
        hasDateFilter: false,
        hasStatusFilter: true,
        hasDataScope: false,
    },
    'permissions': {
        hasSearch: false,
        hasShowroomFilter: false,
        hasUserFilter: false,
        hasDateFilter: false,
        hasStatusFilter: false,
        hasDataScope: false,
    },
    'reports': {
        hasSearch: false,           // Báo cáo không có tìm kiếm
        hasShowroomFilter: true,
        hasUserFilter: true,
        hasDateFilter: true,
        hasStatusFilter: false,     // Báo cáo không có lọc theo trạng thái
        hasDataScope: true,         // Báo cáo CẦN phạm vi dữ liệu
    },
    'year_database': {
        hasSearch: false,
        hasShowroomFilter: false,
        hasUserFilter: false,
        hasDateFilter: false,
        hasStatusFilter: false,
        hasDataScope: false,
    },
};

// Switch tabs
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-button').forEach(el => {
        el.classList.remove('active', 'border-blue-600', 'text-blue-700');
        el.classList.add('border-transparent', 'text-gray-600');
    });
    
    document.getElementById('content-' + tab).classList.remove('hidden');
    const tabButton = document.getElementById('tab-' + tab);
    tabButton.classList.add('active', 'border-blue-600', 'text-blue-700');
    tabButton.classList.remove('border-transparent', 'text-gray-600');
}

// Load role permissions
async function loadRolePermissions(roleId) {
    if (!roleId) {
        document.getElementById('permissions-container').innerHTML = '<p class="text-gray-500 text-center py-12 extra-large-text">Vui lòng chọn vai trò để phân quyền</p>';
        return;
    }
    
    currentRoleId = roleId;
    
    try {
        const response = await fetch('/permissions/roles/' + roleId);
        const data = await response.json();
        currentPermissions = data.permissions || {};
        currentFieldPermissions = data.fieldPermissions || {};
        
        // Debug: Log permissions từ server
        console.log('Loaded permissions from server:', data.permissions);
        if (data.permissions.sales) {
            console.log('Sales permissions from server:', data.permissions.sales);
        }
        
        await renderPermissions();
    } catch (error) {
        console.error('Error loading permissions:', error);
        alert('Có lỗi khi tải quyền');
    }
}

// Render permissions UI
async function renderPermissions() {
    const container = document.getElementById('permissions-container');
    const modules = window.permissionsModules || {};
    
    // Check if modules exist
    if (Object.keys(modules).length === 0) {
        container.innerHTML = '<div class="text-center py-12"><p class="text-gray-500 text-lg">Không có module nào để phân quyền</p></div>';
        return;
    }
    
    let html = '<div class="space-y-8">';
    
    // Module permissions table
    html += '<div>';
    html += '<div class="flex justify-between items-center mb-4">';
    html += '<h5 class="font-bold text-lg text-gray-800">Quyền truy cập module</h5>';
    html += '<div class="relative w-64">';
    html += '<input type="text" id="search-modules" placeholder="Tìm module..." class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 pl-10" onkeyup="filterPermissionModules(this.value)">';
    html += '<i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>';
    html += '</div></div>';
    // Định nghĩa quyền cho từng module
    const modulePermissions = {
        // Dashboard - KHÔNG có export/import (chỉ xem)
        'dashboard': ['can_view'],
        
        // Sales - KHÔNG có export/import
        'sales': ['can_view', 'can_create', 'can_edit', 'can_delete', 'can_print', 'can_approve', 'can_cancel'],
        
        // Debt - CÓ export (can_edit = thu tiền công nợ)
        'debt': ['can_view', 'can_edit', 'can_export'],
        
        // Returns - KHÔNG có export/import
        'returns': ['can_view', 'can_create', 'can_edit', 'can_delete', 'can_print', 'can_approve', 'can_cancel'],
        
        // Inventory - CÓ export (can_create = thêm/nhập tranh vào kho)
        'inventory': ['can_view', 'can_create', 'can_edit', 'can_delete', 'can_export'],
        
        // Frames - Khung tranh (chỉ xem, thêm, xóa)
        'frames': ['can_view', 'can_create', 'can_delete'],
        
        // Showrooms - KHÔNG có export/import
        'showrooms': ['can_view', 'can_create', 'can_edit', 'can_delete'],
        
        // Customers - KHÔNG có export/import
        'customers': ['can_view', 'can_create', 'can_edit', 'can_delete'],
        
        // Employees - CÓ export
        'employees': ['can_view', 'can_create', 'can_edit', 'can_delete', 'can_export'],
        
        // Permissions - KHÔNG có export/import
        'permissions': ['can_view', 'can_create', 'can_edit', 'can_delete'],
        
        // Reports - Báo cáo (chỉ xem và in, không có xuất)
        'reports': ['can_view', 'can_print'],
        
        // Year Database - CÓ export + import (export/import database SQL)
        'year_database': ['can_view', 'can_export', 'can_import', 'can_delete']
    };
    
    html += '<div class="overflow-x-auto"><table class="min-w-full divide-y-2 divide-gray-200 border-2 border-gray-200 rounded-lg">';
    html += '<thead class="bg-gray-100"><tr>';
    html += '<th class="px-6 py-4 text-left large-text font-bold text-gray-700 uppercase">Module<br><input type="checkbox" class="mt-2 large-checkbox" id="select-all-modules" onclick="toggleAllModules()" title="Chọn tất cả module"></th>';
    html += '<th class="px-6 py-4 text-center large-text font-bold text-gray-700 uppercase">Xem<br><input type="checkbox" class="mt-2 large-checkbox select-all-action" data-action="can_view" onclick="toggleAllAction(\'can_view\')" title="Chọn tất cả quyền Xem"></th>';
    html += '<th class="px-6 py-4 text-center large-text font-bold text-gray-700 uppercase">Thêm<br><input type="checkbox" class="mt-2 large-checkbox select-all-action" data-action="can_create" onclick="toggleAllAction(\'can_create\')" title="Chọn tất cả quyền Thêm"></th>';
    html += '<th class="px-6 py-4 text-center large-text font-bold text-gray-700 uppercase">Sửa<br><input type="checkbox" class="mt-2 large-checkbox select-all-action" data-action="can_edit" onclick="toggleAllAction(\'can_edit\')" title="Chọn tất cả quyền Sửa"></th>';
    html += '<th class="px-6 py-4 text-center large-text font-bold text-gray-700 uppercase">Xóa<br><input type="checkbox" class="mt-2 large-checkbox select-all-action" data-action="can_delete" onclick="toggleAllAction(\'can_delete\')" title="Chọn tất cả quyền Xóa"></th>';
    html += '<th class="px-6 py-4 text-center large-text font-bold text-gray-700 uppercase">Xuất<br><input type="checkbox" class="mt-2 large-checkbox select-all-action" data-action="can_export" onclick="toggleAllAction(\'can_export\')" title="Chọn tất cả quyền Xuất"></th>';
    html += '<th class="px-6 py-4 text-center large-text font-bold text-gray-700 uppercase">Nhập<br><input type="checkbox" class="mt-2 large-checkbox select-all-action" data-action="can_import" onclick="toggleAllAction(\'can_import\')" title="Chọn tất cả quyền Nhập"></th>';
    html += '<th class="px-6 py-4 text-center large-text font-bold text-gray-700 uppercase">In<br><input type="checkbox" class="mt-2 large-checkbox select-all-action" data-action="can_print" onclick="toggleAllAction(\'can_print\')" title="Chọn tất cả quyền In"></th>';
    html += '<th class="px-6 py-4 text-center large-text font-bold text-gray-700 uppercase">Duyệt<br><input type="checkbox" class="mt-2 large-checkbox select-all-action" data-action="can_approve" onclick="toggleAllAction(\'can_approve\')" title="Chọn tất cả quyền Duyệt"></th>';
    html += '<th class="px-6 py-4 text-center large-text font-bold text-gray-700 uppercase">Hủy<br><input type="checkbox" class="mt-2 large-checkbox select-all-action" data-action="can_cancel" onclick="toggleAllAction(\'can_cancel\')" title="Chọn tất cả quyền Hủy"></th>';
    html += '<th class="px-6 py-4 text-center large-text font-bold text-gray-700 uppercase bg-purple-50">Phạm vi & Lọc</th>';
    html += '</tr></thead><tbody class="bg-white divide-y-2 divide-gray-200">';
    
    for (const [key, label] of Object.entries(modules)) {
        const perms = currentPermissions[key] || {};
        const allowedPerms = modulePermissions[key] || ['can_view'];
        
        // Debug: Log permissions của sales
        if (key === 'sales') {
            console.log('Rendering sales permissions:', perms);
        }
        
        html += '<tr class="hover:bg-gray-50 module-row" data-module-name="' + label.toLowerCase() + '" data-module-key="' + key + '">';
        html += '<td class="px-6 py-4 large-text font-semibold text-gray-900">';
        html += '<div class="flex items-center gap-3">';
        html += '<input type="checkbox" class="large-checkbox select-module-all" data-module="' + key + '" onclick="toggleModuleAll(\'' + key + '\')" title="Chọn tất cả quyền của ' + label + '">';
        html += '<span>' + label + '</span>';
        html += '</div></td>';
        
        // Xem
        html += '<td class="px-6 py-4 text-center">';
        if (allowedPerms.includes('can_view')) {
            html += '<input type="checkbox" class="perm-checkbox large-checkbox" data-module="' + key + '" data-action="can_view" ' + (perms.can_view ? 'checked' : '') + '>';
        }
        html += '</td>';
        
        // Thêm
        html += '<td class="px-6 py-4 text-center">';
        if (allowedPerms.includes('can_create')) {
            html += '<input type="checkbox" class="perm-checkbox large-checkbox" data-module="' + key + '" data-action="can_create" ' + (perms.can_create ? 'checked' : '') + '>';
        }
        html += '</td>';
        
        // Sửa
        html += '<td class="px-6 py-4 text-center">';
        if (allowedPerms.includes('can_edit')) {
            html += '<input type="checkbox" class="perm-checkbox large-checkbox" data-module="' + key + '" data-action="can_edit" ' + (perms.can_edit ? 'checked' : '') + '>';
        }
        html += '</td>';
        
        // Xóa
        html += '<td class="px-6 py-4 text-center">';
        if (allowedPerms.includes('can_delete')) {
            html += '<input type="checkbox" class="perm-checkbox large-checkbox" data-module="' + key + '" data-action="can_delete" ' + (perms.can_delete ? 'checked' : '') + '>';
        }
        html += '</td>';
        
        // Xuất
        html += '<td class="px-6 py-4 text-center">';
        if (allowedPerms.includes('can_export')) {
            html += '<input type="checkbox" class="perm-checkbox large-checkbox" data-module="' + key + '" data-action="can_export" ' + (perms.can_export ? 'checked' : '') + '>';
        }
        html += '</td>';
        
        // Nhập
        html += '<td class="px-6 py-4 text-center">';
        if (allowedPerms.includes('can_import')) {
            html += '<input type="checkbox" class="perm-checkbox large-checkbox" data-module="' + key + '" data-action="can_import" ' + (perms.can_import ? 'checked' : '') + '>';
        }
        html += '</td>';
        
        // In
        html += '<td class="px-6 py-4 text-center">';
        if (allowedPerms.includes('can_print')) {
            html += '<input type="checkbox" class="perm-checkbox large-checkbox" data-module="' + key + '" data-action="can_print" ' + (perms.can_print ? 'checked' : '') + '>';
        }
        html += '</td>';
        
        // Duyệt
        html += '<td class="px-6 py-4 text-center">';
        if (allowedPerms.includes('can_approve')) {
            html += '<input type="checkbox" class="perm-checkbox large-checkbox" data-module="' + key + '" data-action="can_approve" ' + (perms.can_approve ? 'checked' : '') + '>';
        }
        html += '</td>';
        
        // Hủy
        html += '<td class="px-6 py-4 text-center">';
        if (allowedPerms.includes('can_cancel')) {
            html += '<input type="checkbox" class="perm-checkbox large-checkbox" data-module="' + key + '" data-action="can_cancel" ' + (perms.can_cancel ? 'checked' : '') + '>';
        }
        html += '</td>';
        
        // Phạm vi & Lọc - Nút cấu hình
        html += '<td class="px-6 py-4 text-center bg-purple-50">';
        
        // Kiểm tra xem module có tính năng lọc/phạm vi không
        const features = moduleFilterFeatures[key] || {};
        const hasAnyFeature = features.hasDataScope || features.hasSearch || features.hasShowroomFilter || 
                             features.hasUserFilter || features.hasDateFilter || features.hasStatusFilter;
        
        if (hasAnyFeature) {
            html += '<button onclick="showDataScopeModal(\'' + key + '\')" class="px-3 py-1.5 text-xs bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-semibold transition-colors">';
            html += '<i class="fas fa-cog mr-1"></i>Cấu hình';
            html += '</button>';
            
            // Hiển thị trạng thái hiện tại (chỉ khi có data scope)
            if (features.hasDataScope) {
                const dataScope = perms.data_scope || 'all';
                const dataScopeLabels = {
                    'all': 'Tất cả',
                    'own': 'Của mình',
                    'showroom': 'Theo SR',
                    'none': 'Không xem'
                };
                html += '<div class="text-xs text-gray-600 mt-1">' + dataScopeLabels[dataScope] + '</div>';
            } else {
                // Hiển thị số lượng quyền lọc đang bật
                let enabledFilters = 0;
                if (perms.can_search !== false && features.hasSearch) enabledFilters++;
                if (perms.can_filter_by_showroom !== false && features.hasShowroomFilter) enabledFilters++;
                if (perms.can_filter_by_user !== false && features.hasUserFilter) enabledFilters++;
                if (perms.can_filter_by_date !== false && features.hasDateFilter) enabledFilters++;
                if (perms.can_filter_by_status !== false && features.hasStatusFilter) enabledFilters++;
                
                const totalFilters = (features.hasSearch ? 1 : 0) + 
                                   (features.hasShowroomFilter ? 1 : 0) + 
                                   (features.hasUserFilter ? 1 : 0) + 
                                   (features.hasDateFilter ? 1 : 0) + 
                                   (features.hasStatusFilter ? 1 : 0);
                
                html += '<div class="text-xs text-gray-600 mt-1">' + enabledFilters + '/' + totalFilters + ' quyền</div>';
            }
        } else {
            html += '<span class="text-xs text-gray-400 italic">Không có</span>';
        }
        
        html += '</td>';
        
        html += '</tr>';
    }
    
    html += '</tbody></table></div></div>';
    
    // Field permissions
    html += '<div class="hidden">';
    html += '<div class="flex items-center justify-between mb-4">';
    html += '<h5 class="font-bold text-lg text-gray-800">Quyền trường dữ liệu</h5>';
    html += '<div class="flex gap-2">';
    html += '<div class="relative w-64">';
    html += '<input type="text" id="search-fields" placeholder="Tìm trường..." class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 pl-10" onkeyup="filterFields()">';
    html += '<i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>';
    html += '</div>';
    html += '<button onclick="showAddFieldModal()" class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold flex items-center gap-2">';
    html += '<i class="fas fa-plus"></i><span>Thêm trường</span></button>';
    html += '</div></div>';
    html += '<div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="field-permissions-container">';
    html += '<div class="col-span-2 text-center py-4"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>';
    html += '</div></div>';
    
    // Save button
    html += '<div class="flex justify-end pt-4">';
    html += '<button onclick="savePermissions()" class="p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold flex items-center gap-2">';
    html += '<i class="fas fa-save"></i><span>Lưu tất cả quyền</span>';
    html += '</button></div>';
    
    html += '</div>';
    
    container.innerHTML = html;
    
    // Load field permissions for all modules
    await loadAllFieldPermissions();
}

// Load all field permissions
async function loadAllFieldPermissions() {
    const modules = window.permissionsModules || {};
    const container = document.getElementById('field-permissions-container');
    let html = '';
    
    for (const [moduleKey, moduleName] of Object.entries(modules)) {
        // Bỏ qua module "debt" (Lịch sử công nợ) - không cần phân quyền trường
        if (moduleKey === 'debt') {
            continue;
        }
        try {
            const response = await fetch('/permissions/modules/' + moduleKey + '/fields');
            const data = await response.json();
            
            if (data.success && data.fields) {
                allModuleFields[moduleKey] = data.fields;
                
                // Store field IDs for custom fields
                if (!window.customFieldIds) {
                    window.customFieldIds = {};
                }
                if (!window.customFieldIds[moduleKey]) {
                    window.customFieldIds[moduleKey] = {};
                }
                
                if (Object.keys(data.fields).length > 0) {
                    html += '<div class="field-module-card border-2 border-gray-200 rounded-lg p-6" data-module="' + moduleKey.toLowerCase() + '" data-module-name="' + moduleName.toLowerCase() + '">';
                    html += '<h6 class="font-bold text-gray-800 mb-4 text-sm flex items-center justify-between">';
                    html += '<span>' + moduleName + '</span>';
                    html += '<button onclick="showAddFieldModal(\'' + moduleKey + '\')" class="text-sm bg-green-100 text-green-700 px-3 py-1 rounded hover:bg-green-200">';
                    html += '<i class="fas fa-plus mr-1"></i>Thêm trường';
                    html += '</button></h6>';
                    html += '<div class="space-y-3">';
                    
                    for (const [fieldKey, fieldData] of Object.entries(data.fields)) {
                        const fp = currentFieldPermissions[moduleKey]?.[fieldKey] || {};
                        const isCustom = fieldData.type === 'custom';
                        
                        // Store field ID for custom fields
                        if (isCustom && fieldData.id) {
                            if (!window.customFieldIds) window.customFieldIds = {};
                            if (!window.customFieldIds[moduleKey]) window.customFieldIds[moduleKey] = {};
                            window.customFieldIds[moduleKey][fieldKey] = fieldData.id;
                        }
                        
                        html += '<div class="field-item flex items-center justify-between text-sm py-2 border-b border-gray-100" data-field-name="' + fieldData.label.toLowerCase() + '" data-field-key="' + fieldKey.toLowerCase() + '">';
                        html += '<span class="text-gray-700 font-medium flex items-center gap-2">';
                        html += fieldData.label;
                        if (isCustom) {
                            html += '<span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded">Tùy chỉnh</span>';
                        }
                        html += '</span>';
                        html += '<div class="flex gap-4 items-center">';
                        html += '<label class="inline-flex items-center gap-2">';
                        html += '<input type="checkbox" class="field-checkbox w-4 h-4" data-module="' + moduleKey + '" data-field="' + fieldKey + '" data-type="is_hidden" ' + (fp.is_hidden ? 'checked' : '') + '>';
                        html += '<span class="text-gray-700">Ẩn</span>';
                        html += '</label>';
                        html += '<label class="inline-flex items-center gap-2">';
                        html += '<input type="checkbox" class="field-checkbox w-4 h-4" data-module="' + moduleKey + '" data-field="' + fieldKey + '" data-type="is_readonly" ' + (fp.is_readonly ? 'checked' : '') + '>';
                        html += '<span class="text-gray-700">Chỉ đọc</span>';
                        html += '</label>';
                        if (isCustom) {
                            html += '<button onclick="deleteCustomField(\'' + moduleKey + '\', \'' + fieldKey + '\')" class="text-red-600 hover:text-red-800 p-1 hover:bg-red-50 rounded" title="Xóa trường tùy chỉnh">';
                            html += '<i class="fas fa-trash"></i>';
                            html += '</button>';
                        }
                        html += '</div></div>';
                    }
                    
                    html += '</div></div>';
                }
            }
        } catch (error) {
            console.error('Error loading fields for module ' + moduleKey, error);
        }
    }
    
    if (html === '') {
        html = '<div class="col-span-2 text-center py-8 text-gray-500 large-text">Không có trường nào</div>';
    }
    
    container.innerHTML = html;
}

// Save permissions
async function savePermissions() {
    if (!currentRoleId) return;
    
    const permissions = [];
    document.querySelectorAll('.perm-checkbox').forEach(checkbox => {
        const module = checkbox.dataset.module;
        const action = checkbox.dataset.action;
        
        let perm = permissions.find(p => p.module === module);
        if (!perm) {
            perm = { 
                module: module, 
                can_view: false, 
                can_create: false, 
                can_edit: false, 
                can_delete: false, 
                can_export: false, 
                can_import: false, 
                can_print: false,
                can_approve: false,
                can_cancel: false,
                // Quyền mới - mặc định
                data_scope: 'all',
                allowed_showrooms: null,
                can_view_all_users_data: true,
                can_filter_by_showroom: true,
                can_filter_by_user: true,
                can_filter_by_date: true,
                can_filter_by_status: true,
                can_search: true,
            };
            permissions.push(perm);
        }
        perm[action] = checkbox.checked;
        
        // Merge với cấu hình phạm vi dữ liệu
        if (dataScopeConfigs[module]) {
            Object.assign(perm, dataScopeConfigs[module]);
        } else if (currentPermissions[module]) {
            // Sử dụng cấu hình hiện tại nếu chưa thay đổi
            perm.data_scope = currentPermissions[module].data_scope || 'all';
            perm.allowed_showrooms = currentPermissions[module].allowed_showrooms || null;
            perm.can_view_all_users_data = currentPermissions[module].can_view_all_users_data !== false;
            perm.can_filter_by_showroom = currentPermissions[module].can_filter_by_showroom !== false;
            perm.can_filter_by_user = currentPermissions[module].can_filter_by_user !== false;
            perm.can_filter_by_date = currentPermissions[module].can_filter_by_date !== false;
            perm.can_filter_by_status = currentPermissions[module].can_filter_by_status !== false;
            perm.can_search = currentPermissions[module].can_search !== false;
        }
    });
    
    const fieldPermissions = [];
    document.querySelectorAll('.field-checkbox').forEach(checkbox => {
        if (checkbox.checked) {
            fieldPermissions.push({
                module: checkbox.dataset.module,
                field_name: checkbox.dataset.field,
                is_hidden: checkbox.dataset.type === 'is_hidden',
                is_readonly: checkbox.dataset.type === 'is_readonly'
            });
        }
    });
    
    console.log('Saving permissions:', permissions);
    console.log('Saving field permissions:', fieldPermissions);
    
    // Log chi tiết permissions của sales
    const salesPerm = permissions.find(p => p.module === 'sales');
    if (salesPerm) {
        console.log('Sales permissions detail:', salesPerm);
    }
    
    // Validate permissions data
    if (permissions.length === 0) {
        alert('Vui lòng chọn ít nhất một quyền');
        return;
    }
    
    try {
        // Use FormData for better Laravel compatibility
        const formData1 = new FormData();
        formData1.append('_method', 'PUT');
        formData1.append('permissions', JSON.stringify(permissions));
        
        const response1 = await fetch('/permissions/roles/' + currentRoleId + '/permissions', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData1
        });
        
        // Always send field_permissions, even if empty array
        const formData2 = new FormData();
        formData2.append('_method', 'PUT');
        formData2.append('field_permissions', JSON.stringify(fieldPermissions)); // Will be [] if empty
        
        const response2 = await fetch('/permissions/roles/' + currentRoleId + '/field-permissions', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData2
        });
        
        console.log('Response 1 status:', response1.status);
        console.log('Response 2 status:', response2.status);
        
        if (response1.ok && response2.ok) {
            const data1 = await response1.json();
            const data2 = await response2.json();
            console.log('Success:', data1, data2);
            alert('Đã lưu quyền thành công');
            location.reload();
        } else {
            // Parse error response để lấy message
            let errorMessage = '';
            
            if (!response1.ok) {
                try {
                    const errorData = await response1.json();
                    errorMessage = errorData.message || 'Có lỗi khi lưu quyền module';
                } catch {
                    errorMessage = 'Có lỗi khi lưu quyền module';
                }
            } else if (!response2.ok) {
                try {
                    const errorData = await response2.json();
                    errorMessage = errorData.message || 'Có lỗi khi lưu quyền trường';
                } catch {
                    errorMessage = 'Có lỗi khi lưu quyền trường';
                }
            }
            
            console.error('Error:', errorMessage);
            alert(errorMessage);
        }
    } catch (error) {
        console.error('Error saving permissions:', error);
        alert('Có lỗi khi lưu quyền: ' + error.message);
    }
}

// Show add field modal
function showAddFieldModal(module = null) {
    const modal = document.getElementById('addFieldModal');
    const moduleSelect = document.getElementById('add-field-module');
    
    if (module) {
        moduleSelect.value = module;
    }
    
    modal.classList.remove('hidden');
}

// Close add field modal
function closeAddFieldModal() {
    document.getElementById('addFieldModal').classList.add('hidden');
    document.getElementById('addFieldForm').reset();
}

// Add custom field
async function addCustomField(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = {
        module: formData.get('module'),
        field_name: formData.get('field_name'),
        field_label: formData.get('field_label'),
        field_type: formData.get('field_type'),
        field_options: formData.get('field_options'),
        is_required: formData.get('is_required') === 'on'
    };
    
    try {
        const response = await fetch('/permissions/custom-fields', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Đã thêm trường thành công');
            closeAddFieldModal();
            await loadAllFieldPermissions();
        } else {
            alert(result.message || 'Có lỗi khi thêm trường');
        }
    } catch (error) {
        console.error('Error adding custom field:', error);
        alert('Có lỗi khi thêm trường');
    }
}

// Delete custom field
async function deleteCustomField(module, fieldName) {
    if (!confirm('Bạn có chắc muốn xóa trường tùy chỉnh này?\n\nLưu ý: Chỉ có thể xóa nếu trường chưa được sử dụng trong phân quyền.')) {
        return;
    }
    
    // Get field ID
    const fieldId = window.customFieldIds?.[module]?.[fieldName];
    
    if (!fieldId) {
        alert('Không tìm thấy ID của trường này');
        return;
    }
    
    try {
        const response = await fetch('/permissions/custom-fields/' + fieldId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-HTTP-Method-Override': 'DELETE'
            },
            body: JSON.stringify({
                _method: 'DELETE'
            })
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            alert(data.message || 'Đã xóa trường thành công');
            // Reload field permissions
            await loadAllFieldPermissions();
        } else {
            alert(data.message || 'Có lỗi khi xóa trường');
        }
    } catch (error) {
        console.error('Error deleting custom field:', error);
        alert('Có lỗi khi xóa trường: ' + error.message);
    }
}

// Assign role to user
async function assignRole(userId, roleId) {
    // Handle empty selection (do nothing)
    if (roleId === '') {
        return;
    }
    
    // Handle remove role
    if (roleId === 'remove') {
        if (!confirm('Bạn có chắc muốn HỦY GÁN vai trò cho người dùng này?')) {
            location.reload();
            return;
        }
        roleId = null; // Set to null to remove role
    } else {
        if (!confirm('Bạn có chắc muốn gán vai trò này cho người dùng?')) {
            location.reload();
            return;
        }
    }
    
    try {
        const response = await fetch('/permissions/users/' + userId + '/assign-role', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ 
                _method: 'PUT',
                role_id: roleId 
            })
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data.success) {
                if (roleId === null) {
                    alert('Đã hủy gán vai trò thành công');
                } else {
                    alert('Đã gán vai trò thành công');
                }
                location.reload();
            } else {
                alert(data.message || 'Có lỗi khi gán vai trò');
                location.reload();
            }
        } else {
            // Parse error response để lấy message (bao gồm archive mode)
            try {
                const errorData = await response.json();
                alert(errorData.message || 'Có lỗi khi gán vai trò');
            } catch {
                alert('Có lỗi khi gán vai trò');
            }
            location.reload();
        }
    } catch (error) {
        console.error('Error assigning role:', error);
        alert('Có lỗi khi gán vai trò: ' + error.message);
        location.reload();
    }
}

// Edit role
async function editRole(roleId) {
    try {
        const response = await fetch('/permissions/roles/' + roleId);
        const data = await response.json();
        
        document.getElementById('edit-role-name').value = data.role.name;
        document.getElementById('edit-role-description').value = data.role.description || '';
        document.getElementById('editRoleForm').action = '/permissions/roles/' + roleId;
        document.getElementById('editRoleModal').classList.remove('hidden');
    } catch (error) {
        console.error('Error loading role:', error);
        alert('Có lỗi khi tải thông tin vai trò');
    }
}

// Close edit modal
function closeEditModal() {
    document.getElementById('editRoleModal').classList.add('hidden');
}


// Filter roles
function filterRoles() {
    const searchText = document.getElementById('search-roles').value.toLowerCase();
    const roleItems = document.querySelectorAll('.role-item');
    let visibleCount = 0;
    
    roleItems.forEach(item => {
        const name = item.dataset.name || '';
        const description = item.dataset.description || '';
        
        if (name.includes(searchText) || description.includes(searchText)) {
            item.style.display = '';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    
    // Show/hide empty message
    const rolesList = document.getElementById('roles-list');
    let emptyMsg = rolesList.querySelector('.empty-message');
    
    if (visibleCount === 0) {
        if (!emptyMsg) {
            emptyMsg = document.createElement('p');
            emptyMsg.className = 'empty-message text-gray-500 text-center py-8 text-sm';
            emptyMsg.textContent = 'Không tìm thấy vai trò nào';
            rolesList.appendChild(emptyMsg);
        }
        emptyMsg.style.display = '';
    } else {
        if (emptyMsg) {
            emptyMsg.style.display = 'none';
        }
    }
}

// Filter users
function filterUsers() {
    const searchInput = document.getElementById('search-users');
    const filterRoleSelect = document.getElementById('filter-role');
    
    if (!searchInput || !filterRoleSelect) {
        console.error('Search elements not found');
        return;
    }
    
    const searchText = searchInput.value.toLowerCase().trim();
    const filterRole = filterRoleSelect.value;
    const userRows = document.querySelectorAll('.user-row');
    let visibleCount = 0;
    
    console.log('Filtering users:', { searchText, filterRole, totalRows: userRows.length });
    
    userRows.forEach(row => {
        const name = row.dataset.name || '';
        const email = row.dataset.email || '';
        const role = row.dataset.role || '';
        
        let matchSearch = !searchText || name.includes(searchText) || email.includes(searchText);
        let matchRole = !filterRole || role === filterRole;
        
        if (matchSearch && matchRole) {
            row.style.display = 'table-row';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    console.log('Visible users:', visibleCount);
    
    // Update count display
    updateUserCount(visibleCount, userRows.length);
}

// Update user count display
function updateUserCount(visible, total) {
    let countDisplay = document.getElementById('user-count-display');
    
    if (!countDisplay) {
        const header = document.querySelector('#content-users h4');
        if (header) {
            countDisplay = document.createElement('span');
            countDisplay.id = 'user-count-display';
            countDisplay.className = 'ml-2 text-sm text-gray-500';
            header.appendChild(countDisplay);
        }
    }
    
    if (countDisplay) {
        if (visible === total) {
            countDisplay.textContent = `(${total})`;
        } else {
            countDisplay.textContent = `(${visible}/${total})`;
        }
    }
}

// Filter permissions by module (for tab 2)
function filterPermissionModules(searchText) {
    const rows = document.querySelectorAll('#permissions-container table tbody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const moduleText = row.cells[0]?.textContent.toLowerCase() || '';
        
        if (moduleText.includes(searchText.toLowerCase())) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    return visibleCount;
}

// Filter field permissions
function filterFields() {
    const searchText = document.getElementById('search-fields')?.value.toLowerCase() || '';
    const moduleCards = document.querySelectorAll('.field-module-card');
    let totalVisible = 0;
    
    moduleCards.forEach(card => {
        const moduleName = card.dataset.moduleName || '';
        const fieldItems = card.querySelectorAll('.field-item');
        let visibleFields = 0;
        
        // Check if module name matches
        const moduleMatches = moduleName.includes(searchText);
        
        fieldItems.forEach(item => {
            const fieldName = item.dataset.fieldName || '';
            const fieldKey = item.dataset.fieldKey || '';
            
            // Show if search matches field name, field key, or module name
            if (moduleMatches || fieldName.includes(searchText) || fieldKey.includes(searchText)) {
                item.style.display = '';
                visibleFields++;
            } else {
                item.style.display = 'none';
            }
        });
        
        // Show/hide entire module card
        if (visibleFields > 0) {
            card.style.display = '';
            totalVisible++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show empty message if no results
    const container = document.getElementById('field-permissions-container');
    let emptyMsg = container.querySelector('.empty-fields-message');
    
    if (totalVisible === 0 && moduleCards.length > 0) {
        if (!emptyMsg) {
            emptyMsg = document.createElement('div');
            emptyMsg.className = 'empty-fields-message col-span-2 text-center py-8 text-gray-500 text-sm';
            emptyMsg.textContent = 'Không tìm thấy trường nào';
            container.appendChild(emptyMsg);
        }
        emptyMsg.style.display = '';
    } else {
        if (emptyMsg) {
            emptyMsg.style.display = 'none';
        }
    }
}


// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize user count if on users tab
    const userRows = document.querySelectorAll('.user-row');
    if (userRows.length > 0) {
        updateUserCount(userRows.length, userRows.length);
    }
});


// ============================================
// PHẠM VI DỮ LIỆU VÀ QUYỀN LỌC
// ============================================

// Lưu trữ cấu hình phạm vi dữ liệu tạm thời
let dataScopeConfigs = {};

// Hiện modal cấu hình phạm vi dữ liệu
function showDataScopeModal(moduleKey) {
    const modules = window.permissionsModules || {};
    const moduleName = modules[moduleKey] || moduleKey;
    const perms = currentPermissions[moduleKey] || {};
    const features = moduleFilterFeatures[moduleKey] || {};
    
    // Set module info
    document.getElementById('modal-module-key').value = moduleKey;
    document.getElementById('modal-module-name').textContent = moduleName;
    
    // Ẩn/hiện phần phạm vi dữ liệu
    const dataScopeSection = document.getElementById('data-scope-section');
    if (features.hasDataScope) {
        dataScopeSection.classList.remove('hidden');
        document.getElementById('data-scope-select').value = perms.data_scope || 'all';
    } else {
        dataScopeSection.classList.add('hidden');
    }
    
    // Load current config và ẩn/hiện các checkbox
    const filterCheckboxes = [
        { id: 'can-search-wrapper', checkbox: 'can-search', feature: 'hasSearch', perm: 'can_search' },
        { id: 'can-filter-showroom-wrapper', checkbox: 'can-filter-showroom', feature: 'hasShowroomFilter', perm: 'can_filter_by_showroom' },
        { id: 'can-filter-user-wrapper', checkbox: 'can-filter-user', feature: 'hasUserFilter', perm: 'can_filter_by_user' },
        { id: 'can-filter-date-wrapper', checkbox: 'can-filter-date', feature: 'hasDateFilter', perm: 'can_filter_by_date' },
        { id: 'can-filter-status-wrapper', checkbox: 'can-filter-status', feature: 'hasStatusFilter', perm: 'can_filter_by_status' },
    ];
    
    let hasAnyFilter = false;
    filterCheckboxes.forEach(item => {
        const wrapper = document.getElementById(item.id);
        const checkbox = document.getElementById(item.checkbox);
        
        if (features[item.feature]) {
            wrapper.classList.remove('hidden');
            checkbox.checked = perms[item.perm] !== false;
            hasAnyFilter = true;
        } else {
            wrapper.classList.add('hidden');
        }
    });
    
    // Ẩn/hiện section quyền lọc
    const filterSection = document.getElementById('filter-permissions-section');
    if (hasAnyFilter) {
        filterSection.classList.remove('hidden');
    } else {
        filterSection.classList.add('hidden');
    }
    
    // Nếu không có gì để cấu hình
    if (!features.hasDataScope && !hasAnyFilter) {
        alert('Module này không có tính năng lọc/tìm kiếm để cấu hình');
        return;
    }
    
    // Load allowed showrooms
    const allowedShowrooms = perms.allowed_showrooms || [];
    const showroomSelect = document.getElementById('allowed-showrooms-select');
    if (showroomSelect) {
        Array.from(showroomSelect.options).forEach(option => {
            option.selected = allowedShowrooms.includes(parseInt(option.value));
        });
    }
    
    // Show/hide showroom selector
    toggleShowroomSelector();
    
    // Show modal
    document.getElementById('dataScopeModal').classList.remove('hidden');
}

// Đóng modal
function closeDataScopeModal() {
    document.getElementById('dataScopeModal').classList.add('hidden');
}

// Toggle showroom selector
function toggleShowroomSelector() {
    const dataScope = document.getElementById('data-scope-select').value;
    const showroomSelector = document.getElementById('showroom-selector');
    
    if (dataScope === 'showroom') {
        showroomSelector.classList.remove('hidden');
    } else {
        showroomSelector.classList.add('hidden');
    }
}

// Lưu cấu hình phạm vi dữ liệu
function saveDataScope() {
    const moduleKey = document.getElementById('modal-module-key').value;
    const dataScope = document.getElementById('data-scope-select').value;
    
    // Get allowed showrooms
    const showroomSelect = document.getElementById('allowed-showrooms-select');
    const allowedShowrooms = showroomSelect ? Array.from(showroomSelect.selectedOptions).map(opt => parseInt(opt.value)) : [];
    
    // Validate: nếu chọn 'showroom' thì phải chọn ít nhất 1 showroom
    if (dataScope === 'showroom' && allowedShowrooms.length === 0) {
        alert('Vui lòng chọn ít nhất 1 showroom khi chọn phạm vi "Theo showroom"');
        return;
    }
    
    // Get filter permissions
    const config = {
        data_scope: dataScope,
        allowed_showrooms: dataScope === 'showroom' ? allowedShowrooms : null,
        can_search: document.getElementById('can-search').checked,
        can_filter_by_showroom: document.getElementById('can-filter-showroom').checked,
        can_filter_by_user: document.getElementById('can-filter-user').checked,
        can_filter_by_date: document.getElementById('can-filter-date').checked,
        can_filter_by_status: document.getElementById('can-filter-status').checked,
    };
    
    // Lưu vào biến tạm
    dataScopeConfigs[moduleKey] = config;
    
    // Cập nhật currentPermissions
    if (!currentPermissions[moduleKey]) {
        currentPermissions[moduleKey] = {};
    }
    Object.assign(currentPermissions[moduleKey], config);
    
    // Đóng modal
    closeDataScopeModal();
    
    // Re-render để cập nhật hiển thị
    renderPermissions();
    
    // Hiển thị thông báo
    alert('Đã lưu cấu hình. Nhớ nhấn nút "Lưu tất cả quyền" ở cuối trang để áp dụng thay đổi.');
}

// Toggle all modules - Chọn/bỏ chọn TẤT CẢ quyền của TẤT CẢ module
function toggleAllModules() {
    const checkbox = document.getElementById('select-all-modules');
    const isChecked = checkbox.checked;
    
    // Chọn tất cả checkbox trong bảng
    document.querySelectorAll('.perm-checkbox').forEach(cb => {
        cb.checked = isChecked;
    });
    
    // Cập nhật trạng thái các checkbox "chọn tất cả" khác
    document.querySelectorAll('.select-all-action').forEach(cb => {
        cb.checked = isChecked;
    });
    
    document.querySelectorAll('.select-module-all').forEach(cb => {
        cb.checked = isChecked;
    });
}

// Toggle all action - Chọn/bỏ chọn tất cả quyền của 1 loại (VD: tất cả quyền "Xem")
function toggleAllAction(action) {
    const headerCheckbox = document.querySelector('.select-all-action[data-action="' + action + '"]');
    const isChecked = headerCheckbox.checked;
    
    // Chọn tất cả checkbox của action này
    document.querySelectorAll('.perm-checkbox[data-action="' + action + '"]').forEach(cb => {
        cb.checked = isChecked;
    });
    
    // Cập nhật trạng thái checkbox "chọn tất cả module" của từng hàng
    updateModuleCheckboxes();
}

// Toggle module all - Chọn/bỏ chọn tất cả quyền của 1 module
function toggleModuleAll(moduleKey) {
    const moduleCheckbox = document.querySelector('.select-module-all[data-module="' + moduleKey + '"]');
    const isChecked = moduleCheckbox.checked;
    
    // Chọn tất cả checkbox của module này
    document.querySelectorAll('.perm-checkbox[data-module="' + moduleKey + '"]').forEach(cb => {
        cb.checked = isChecked;
    });
    
    // Cập nhật trạng thái các checkbox header
    updateHeaderCheckboxes();
}

// Cập nhật trạng thái checkbox "chọn tất cả" của từng module
function updateModuleCheckboxes() {
    document.querySelectorAll('.select-module-all').forEach(moduleCheckbox => {
        const moduleKey = moduleCheckbox.dataset.module;
        const allCheckboxes = document.querySelectorAll('.perm-checkbox[data-module="' + moduleKey + '"]');
        const checkedCheckboxes = Array.from(allCheckboxes).filter(cb => cb.checked);
        
        // Nếu tất cả đều checked thì check, ngược lại uncheck
        moduleCheckbox.checked = allCheckboxes.length > 0 && checkedCheckboxes.length === allCheckboxes.length;
    });
}

// Cập nhật trạng thái checkbox "chọn tất cả" ở header
function updateHeaderCheckboxes() {
    document.querySelectorAll('.select-all-action').forEach(headerCheckbox => {
        const action = headerCheckbox.dataset.action;
        const allCheckboxes = document.querySelectorAll('.perm-checkbox[data-action="' + action + '"]');
        const checkedCheckboxes = Array.from(allCheckboxes).filter(cb => cb.checked);
        
        // Nếu tất cả đều checked thì check, ngược lại uncheck
        headerCheckbox.checked = allCheckboxes.length > 0 && checkedCheckboxes.length === allCheckboxes.length;
    });
    
    // Cập nhật checkbox "chọn tất cả module"
    const allPermCheckboxes = document.querySelectorAll('.perm-checkbox');
    const allChecked = Array.from(allPermCheckboxes).filter(cb => cb.checked);
    const selectAllModules = document.getElementById('select-all-modules');
    if (selectAllModules) {
        selectAllModules.checked = allPermCheckboxes.length > 0 && allChecked.length === allPermCheckboxes.length;
    }
}
