<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions for all modules
        $modules = [
            'dashboard' => 'Báo cáo thống kê',
            'sales' => 'Bán hàng',
            'debt' => 'Lịch sử công nợ',
            'returns' => 'Đổi/Trả hàng',
            'inventory' => 'Quản lý kho',
            'showrooms' => 'Phòng trưng bày',
            'customers' => 'Khách hàng',
            'employees' => 'Nhân viên',
            'permissions' => 'Phân quyền',
        ];

        foreach ($modules as $key => $name) {
            Permission::firstOrCreate(
                ['module' => $key],
                [
                    'name' => $name,
                    'description' => 'Quyền truy cập ' . $name,
                ]
            );
        }

        // Create Admin role with full permissions
        $adminRole = Role::firstOrCreate(
            ['name' => 'Admin'],
            ['description' => 'Quản trị viên - Có toàn quyền truy cập']
        );

        foreach (Permission::all() as $permission) {
            RolePermission::updateOrCreate(
                [
                    'role_id' => $adminRole->id,
                    'permission_id' => $permission->id,
                ],
                [
                    'can_view' => true,
                    'can_create' => true,
                    'can_edit' => true,
                    'can_delete' => true,
                    'can_export' => true,
                    'can_import' => true,
                    'can_print' => true,
                    'can_approve' => true,
                    'can_cancel' => true,
                ]
            );
        }

        // Create Sales Staff role
        $salesRole = Role::firstOrCreate(
            ['name' => 'Nhân viên bán hàng'],
            ['description' => 'Nhân viên bán hàng - Quản lý bán hàng và khách hàng']
        );

        $salesModules = ['dashboard', 'sales', 'returns', 'customers'];
        foreach ($salesModules as $module) {
            $permission = Permission::where('module', $module)->first();
            if ($permission) {
                RolePermission::updateOrCreate(
                    [
                        'role_id' => $salesRole->id,
                        'permission_id' => $permission->id,
                    ],
                    [
                        'can_view' => true,
                        'can_create' => $module !== 'dashboard',
                        'can_edit' => $module !== 'dashboard',
                        'can_delete' => false,
                        'can_export' => true,
                        'can_import' => false,
                        'can_print' => true,
                        'can_approve' => false,
                        'can_cancel' => false,
                    ]
                );
            }
        }

        // Create Warehouse Staff role
        $warehouseRole = Role::firstOrCreate(
            ['name' => 'Thủ kho'],
            ['description' => 'Thủ kho - Quản lý kho hàng']
        );

        $warehouseModules = ['dashboard', 'inventory'];
        foreach ($warehouseModules as $module) {
            $permission = Permission::where('module', $module)->first();
            if ($permission) {
                RolePermission::updateOrCreate(
                    [
                        'role_id' => $warehouseRole->id,
                        'permission_id' => $permission->id,
                    ],
                    [
                        'can_view' => true,
                        'can_create' => $module === 'inventory',
                        'can_edit' => $module === 'inventory',
                        'can_delete' => false,
                        'can_export' => true,
                        'can_import' => $module === 'inventory',
                        'can_print' => true,
                        'can_approve' => false,
                        'can_cancel' => false,
                    ]
                );
            }
        }

        // Create Accountant role
        $accountantRole = Role::firstOrCreate(
            ['name' => 'Kế toán'],
            ['description' => 'Kế toán - Quản lý tài chính và công nợ']
        );

        $accountantModules = ['dashboard', 'sales', 'debt', 'customers'];
        foreach ($accountantModules as $module) {
            $permission = Permission::where('module', $module)->first();
            if ($permission) {
                RolePermission::updateOrCreate(
                    [
                        'role_id' => $accountantRole->id,
                        'permission_id' => $permission->id,
                    ],
                    [
                        'can_view' => true,
                        'can_create' => false,
                        'can_edit' => $module === 'debt',
                        'can_delete' => false,
                        'can_export' => true,
                        'can_import' => false,
                        'can_print' => true,
                        'can_approve' => false,
                        'can_cancel' => false,
                    ]
                );
            }
        }
    }
}
