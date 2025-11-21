<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\FieldPermission;
use App\Models\User;
use App\Models\CustomField;

class PermissionController extends Controller
{
    public function index()
    {
        $roles = Role::with(['permissions', 'rolePermissions.permission', 'fieldPermissions'])->get();
        $permissions = Permission::all();
        $modules = Permission::getModules();
        $users = User::with('role')->get();

        return view('permissions.index', compact('roles', 'permissions', 'modules', 'users'));
    }

    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
            'description' => 'nullable|string|max:255',
        ]);

        $role = Role::create($validated);

        return redirect()->route('permissions.index')
            ->with('success', 'Đã tạo vai trò thành công');
    }

    public function updateRole(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name,' . $id,
            'description' => 'nullable|string|max:255',
        ]);

        $role->update($validated);

        return redirect()->route('permissions.index')
            ->with('success', 'Đã cập nhật vai trò thành công');
    }

    public function deleteRole($id)
    {
        $role = Role::findOrFail($id);

        // Check if role is assigned to any users
        if ($role->users()->count() > 0) {
            return redirect()->route('permissions.index')
                ->with('error', 'Không thể xóa vai trò đang được gán cho người dùng');
        }

        $role->delete();

        return redirect()->route('permissions.index')
            ->with('success', 'Đã xóa vai trò thành công');
    }

    public function getRole($id)
    {
        $role = Role::with(['rolePermissions.permission', 'fieldPermissions'])->findOrFail($id);

        $permissions = [];
        foreach ($role->rolePermissions as $rp) {
            $permissions[$rp->permission->module] = [
                'can_view' => $rp->can_view,
                'can_create' => $rp->can_create,
                'can_edit' => $rp->can_edit,
                'can_delete' => $rp->can_delete,
                'can_export' => $rp->can_export,
                'can_import' => $rp->can_import,
                'can_print' => $rp->can_print,
                'can_approve' => $rp->can_approve ?? false,
                'can_cancel' => $rp->can_cancel ?? false,
                // Quyền mới
                'data_scope' => $rp->data_scope ?? 'all',
                'allowed_showrooms' => $rp->allowed_showrooms ?? null,
                'can_view_all_users_data' => $rp->can_view_all_users_data ?? true,
                'can_filter_by_showroom' => $rp->can_filter_by_showroom ?? true,
                'can_filter_by_user' => $rp->can_filter_by_user ?? true,
                'can_filter_by_date' => $rp->can_filter_by_date ?? true,
                'can_filter_by_status' => $rp->can_filter_by_status ?? true,
                'can_search' => $rp->can_search ?? true,
            ];
        }

        $fieldPermissions = [];
        foreach ($role->fieldPermissions as $fp) {
            if (!isset($fieldPermissions[$fp->module])) {
                $fieldPermissions[$fp->module] = [];
            }
            $fieldPermissions[$fp->module][$fp->field_name] = [
                'is_hidden' => $fp->is_hidden,
                'is_readonly' => $fp->is_readonly,
            ];
        }

        return response()->json([
            'role' => $role,
            'permissions' => $permissions,
            'fieldPermissions' => $fieldPermissions,
        ]);
    }

    public function updatePermissions(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        // Handle both JSON and FormData
        $permissions = $request->input('permissions');
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true);
        }

        $validated = validator(['permissions' => $permissions], [
            'permissions' => 'required|array',
            'permissions.*.module' => 'required|string',
            'permissions.*.can_view' => 'boolean',
            'permissions.*.can_create' => 'boolean',
            'permissions.*.can_edit' => 'boolean',
            'permissions.*.can_delete' => 'boolean',
            'permissions.*.can_export' => 'boolean',
            'permissions.*.can_import' => 'boolean',
            'permissions.*.can_print' => 'boolean',
            'permissions.*.can_approve' => 'boolean',
            'permissions.*.can_cancel' => 'boolean',
            // Quyền mới
            'permissions.*.data_scope' => 'nullable|in:all,own,showroom,none',
            'permissions.*.allowed_showrooms' => 'nullable|array',
            'permissions.*.allowed_showrooms.*' => 'integer|exists:showrooms,id',
            'permissions.*.can_view_all_users_data' => 'boolean',
            'permissions.*.can_filter_by_showroom' => 'boolean',
            'permissions.*.can_filter_by_user' => 'boolean',
            'permissions.*.can_filter_by_date' => 'boolean',
            'permissions.*.can_filter_by_status' => 'boolean',
            'permissions.*.can_search' => 'boolean',
        ])->validate();

        // Delete existing permissions
        $role->rolePermissions()->delete();

        // Create new permissions
        foreach ($validated['permissions'] as $permData) {
            $permission = Permission::firstOrCreate(
                ['module' => $permData['module']],
                [
                    'name' => Permission::getModules()[$permData['module']] ?? $permData['module'],
                    'description' => 'Quyền truy cập ' . (Permission::getModules()[$permData['module']] ?? $permData['module']),
                ]
            );

            RolePermission::create([
                'role_id' => $role->id,
                'permission_id' => $permission->id,
                'can_view' => $permData['can_view'] ?? false,
                'can_create' => $permData['can_create'] ?? false,
                'can_edit' => $permData['can_edit'] ?? false,
                'can_delete' => $permData['can_delete'] ?? false,
                'can_export' => $permData['can_export'] ?? false,
                'can_import' => $permData['can_import'] ?? false,
                'can_print' => $permData['can_print'] ?? false,
                'can_approve' => $permData['can_approve'] ?? false,
                'can_cancel' => $permData['can_cancel'] ?? false,
                // Quyền mới
                'data_scope' => $permData['data_scope'] ?? 'all',
                'allowed_showrooms' => $permData['allowed_showrooms'] ?? null,
                'can_view_all_users_data' => $permData['can_view_all_users_data'] ?? true,
                'can_filter_by_showroom' => $permData['can_filter_by_showroom'] ?? true,
                'can_filter_by_user' => $permData['can_filter_by_user'] ?? true,
                'can_filter_by_date' => $permData['can_filter_by_date'] ?? true,
                'can_filter_by_status' => $permData['can_filter_by_status'] ?? true,
                'can_search' => $permData['can_search'] ?? true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật quyền thành công'
        ]);
    }

    public function updateFieldPermissions(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        // Handle both JSON and FormData
        $fieldPermissions = $request->input('field_permissions');
        if (is_string($fieldPermissions)) {
            $fieldPermissions = json_decode($fieldPermissions, true);
        }

        // If null or not array, set to empty array
        if (!is_array($fieldPermissions)) {
            $fieldPermissions = [];
        }

        $validated = validator(['field_permissions' => $fieldPermissions], [
            'field_permissions' => 'array', // Removed 'required' to allow empty array
            'field_permissions.*.module' => 'required|string',
            'field_permissions.*.field_name' => 'required|string',
            'field_permissions.*.is_hidden' => 'boolean',
            'field_permissions.*.is_readonly' => 'boolean',
        ])->validate();

        // Delete existing field permissions for this role
        $role->fieldPermissions()->delete();

        // Create new field permissions (only if not empty)
        if (!empty($validated['field_permissions'])) {
            foreach ($validated['field_permissions'] as $fpData) {
                FieldPermission::create([
                    'role_id' => $role->id,
                    'module' => $fpData['module'],
                    'field_name' => $fpData['field_name'],
                    'is_hidden' => $fpData['is_hidden'] ?? false,
                    'is_readonly' => $fpData['is_readonly'] ?? false,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật quyền trường thành công'
        ]);
    }

    public function assignRole(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        // Handle both JSON and FormData
        $roleId = $request->input('role_id');
        if (is_string($roleId) && json_decode($roleId) !== null) {
            $roleId = json_decode($roleId);
        }

        // Allow null to remove role
        $validated = validator(['role_id' => $roleId], [
            'role_id' => 'nullable|exists:roles,id',
        ])->validate();

        $user->update(['role_id' => $validated['role_id']]);

        $message = $validated['role_id'] ? 'Đã gán vai trò thành công' : 'Đã hủy gán vai trò thành công';

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    public function getModuleFields($module)
    {
        $fields = CustomField::getAllFieldsForModule($module);

        return response()->json([
            'success' => true,
            'fields' => $fields
        ]);
    }

    public function storeCustomField(Request $request)
    {
        $validated = $request->validate([
            'module' => 'required|string|max:50',
            'field_name' => 'required|string|max:100',
            'field_label' => 'required|string|max:255',
            'field_type' => 'required|in:text,number,date,textarea,select',
            'field_options' => 'nullable|string',
            'is_required' => 'boolean',
            'display_section' => 'nullable|string|max:50',
            'section_order' => 'nullable|integer',
        ]);

        // Check if field already exists
        $exists = CustomField::where('module', $validated['module'])
            ->where('field_name', $validated['field_name'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Trường này đã tồn tại'
            ], 422);
        }

        // Get max display order
        $maxOrder = CustomField::where('module', $validated['module'])->max('display_order') ?? 0;
        $validated['display_order'] = $maxOrder + 1;

        // Set default display_section if not provided
        if (!isset($validated['display_section'])) {
            $validated['display_section'] = 'custom';
        }

        // Set default section_order if not provided
        if (!isset($validated['section_order'])) {
            $maxSectionOrder = CustomField::where('module', $validated['module'])
                ->where('display_section', $validated['display_section'])
                ->max('section_order') ?? 0;
            $validated['section_order'] = $maxSectionOrder + 1;
        }

        $field = CustomField::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm trường thành công',
            'field' => $field
        ]);
    }

    public function deleteCustomField($id)
    {
        $field = CustomField::findOrFail($id);

        // Check if field is being used in field_permissions
        $isUsed = FieldPermission::where('module', $field->module)
            ->where('field_name', $field->field_name)
            ->exists();

        if ($isUsed) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa trường này vì đang được sử dụng trong phân quyền. Vui lòng xóa các phân quyền liên quan trước.'
            ], 422);
        }

        $field->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa trường thành công'
        ]);
    }

    public function getDisplaySections($module)
    {
        $sections = CustomField::getDisplaySections($module);

        return response()->json([
            'success' => true,
            'sections' => $sections
        ]);
    }
}
