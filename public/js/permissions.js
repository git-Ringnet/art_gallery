// Permissions Management JavaScript

let currentRoleId = null;
let currentPermissions = {};
let currentFieldPermissions = {};
let allModuleFields = {};

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
        
        // Year Database - CÓ export + import (export/import database SQL)
        'year_database': ['can_view', 'can_export', 'can_import', 'can_delete']
    };
    
    html += '<div class="overflow-x-auto"><table class="min-w-full divide-y-2 divide-gray-200 border-2 border-gray-200 rounded-lg">';
    html += '<thead class="bg-gray-100"><tr>';
    html += '<th class="px-6 py-4 text-left large-text font-bold text-gray-700 uppercase">Module</th>';
    html += '<th class="px-6 py-4 text-center large-text font-bold text-gray-700 uppercase">Xem</th>';
    html += '<th class="px-6 py-4 text-center large-text font-bold text-gray-700 uppercase">Thêm</th>';
    html += '<th class="px-6 py-4 text-center large-text font-bold text-gray-700 uppercase">Sửa</th>';
    html += '<th class="px-6 py-4 text-center large-text font-bold text-gray-700 uppercase">Xóa</th>';
    html += '<th class="px-6 py-4 text-center large-text font-bold text-gray-700 uppercase">Xuất</th>';
    html += '<th class="px-6 py-4 text-center large-text font-bold text-gray-700 uppercase">Nhập</th>';
    html += '<th class="px-6 py-4 text-center large-text font-bold text-gray-700 uppercase">In</th>';
    html += '<th class="px-6 py-4 text-center large-text font-bold text-gray-700 uppercase">Duyệt</th>';
    html += '<th class="px-6 py-4 text-center large-text font-bold text-gray-700 uppercase">Hủy</th>';
    html += '</tr></thead><tbody class="bg-white divide-y-2 divide-gray-200">';
    
    for (const [key, label] of Object.entries(modules)) {
        const perms = currentPermissions[key] || {};
        const allowedPerms = modulePermissions[key] || ['can_view'];
        
        // Debug: Log permissions của sales
        if (key === 'sales') {
            console.log('Rendering sales permissions:', perms);
        }
        
        html += '<tr class="hover:bg-gray-50 module-row" data-module-name="' + label.toLowerCase() + '">';
        html += '<td class="px-6 py-4 large-text font-semibold text-gray-900">' + label + '</td>';
        
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
                can_cancel: false
            };
            permissions.push(perm);
        }
        perm[action] = checkbox.checked;
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
            const error1 = await response1.text();
            const error2 = await response2.text();
            console.error('Response 1 status:', response1.status);
            console.error('Response 1:', error1);
            console.error('Response 2 status:', response2.status);
            console.error('Response 2:', error2);
            
            // Show more specific error message
            if (!response1.ok) {
                alert('Có lỗi khi lưu quyền module. Vui lòng kiểm tra console (F12).');
            } else if (!response2.ok) {
                alert('Có lỗi khi lưu quyền trường. Vui lòng kiểm tra console (F12).');
            }
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
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-HTTP-Method-Override': 'PUT'
            },
            body: JSON.stringify({ 
                _method: 'PUT',
                role_id: roleId 
            })
        });
        
        if (response.ok) {
            if (roleId === null) {
                alert('Đã hủy gán vai trò thành công');
            } else {
                alert('Đã gán vai trò thành công');
            }
            location.reload();
        } else {
            const errorText = await response.text();
            console.error('Error response:', errorText);
            alert('Có lỗi khi gán vai trò');
        }
    } catch (error) {
        console.error('Error assigning role:', error);
        alert('Có lỗi khi gán vai trò: ' + error.message);
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
