<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;

class AddReportsPermissionToRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy permission "reports"
        $reportsPermission = Permission::where('module', 'reports')->first();
        
        if (!$reportsPermission) {
            echo "Permission 'reports' không tồn tại. Chạy PermissionsSeeder trước.\n";
            return;
        }
        
        // Lấy tất cả roles
        $roles = Role::all();
        
        foreach ($roles as $role) {
            // Kiểm tra xem đã có RolePermission chưa
            $existing = RolePermission::where('role_id', $role->id)
                ->where('permission_id', $reportsPermission->id)
                ->first();
            
            if ($existing) {
                echo "Role '{$role->name}' đã có quyền reports.\n";
                continue;
            }
            
            // Tạo RolePermission mới
            $permissions = [];
            
            // Admin: Toàn quyền (báo cáo chỉ có xem và in)
            if ($role->name === 'Admin') {
                $permissions = [
                    'can_view' => true,
                    'can_create' => false,
                    'can_edit' => false,
                    'can_delete' => false,
                    'can_export' => false,
                    'can_import' => false,
                    'can_print' => true,
                    'can_approve' => true,
                    'can_cancel' => true,
                    'data_scope' => 'all',
                    'allowed_showrooms' => null,
                    'can_view_all_users_data' => true,
                    'can_filter_by_showroom' => true,
                    'can_filter_by_user' => true,
                    'can_filter_by_date' => true,
                    'can_filter_by_status' => false,  // Báo cáo không có lọc trạng thái
                    'can_search' => false,            // Báo cáo không có tìm kiếm
                ];
            }
            // Kế toán: Xem, In (không có xuất)
            elseif ($role->name === 'Kế toán') {
                $permissions = [
                    'can_view' => true,
                    'can_create' => false,
                    'can_edit' => false,
                    'can_delete' => false,
                    'can_export' => false,
                    'can_import' => false,
                    'can_print' => true,
                    'can_approve' => false,
                    'can_cancel' => false,
                    'data_scope' => 'all',
                    'allowed_showrooms' => null,
                    'can_view_all_users_data' => true,
                    'can_filter_by_showroom' => true,
                    'can_filter_by_user' => true,
                    'can_filter_by_date' => true,
                    'can_filter_by_status' => false,  // Báo cáo không có lọc trạng thái
                    'can_search' => false,            // Báo cáo không có tìm kiếm
                ];
            }
            // Các role khác: Không có quyền mặc định (có thể cấu hình sau)
            else {
                $permissions = [
                    'can_view' => false,
                    'can_create' => false,
                    'can_edit' => false,
                    'can_delete' => false,
                    'can_export' => false,
                    'can_import' => false,
                    'can_print' => false,
                    'can_approve' => false,
                    'can_cancel' => false,
                    'data_scope' => 'all',
                    'allowed_showrooms' => null,
                    'can_view_all_users_data' => false,
                    'can_filter_by_showroom' => true,
                    'can_filter_by_user' => false,
                    'can_filter_by_date' => true,
                    'can_filter_by_status' => false,  // Báo cáo không có lọc trạng thái
                    'can_search' => false,            // Báo cáo không có tìm kiếm
                ];
            }
            
            RolePermission::create(array_merge([
                'role_id' => $role->id,
                'permission_id' => $reportsPermission->id,
            ], $permissions));
            
            echo "Đã thêm quyền reports cho role '{$role->name}'.\n";
        }
        
        echo "Hoàn tất!\n";
    }
}
