<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Tạo Roles
        $admin = Role::create([
            'name' => 'Admin',
            'description' => 'Quản trị viên - Toàn quyền hệ thống',
        ]);

        $manager = Role::create([
            'name' => 'Manager',
            'description' => 'Quản lý - Quản lý bán hàng và kho',
        ]);

        $staff = Role::create([
            'name' => 'Staff',
            'description' => 'Nhân viên - Bán hàng cơ bản',
        ]);

        // Tạo Permissions
        $modules = [
            'dashboard' => 'Báo cáo thống kê',
            'sales' => 'Bán hàng',
            'debt' => 'Lịch sử công nợ',
            'returns' => 'Đổi/Trả hàng',
            'inventory' => 'Quản lý kho',
            'showrooms' => 'Phòng trưng bày',
            'permissions' => 'Phân quyền',
        ];

        $permissions = [];
        foreach ($modules as $module => $name) {
            $permissions[$module] = Permission::create([
                'module' => $module,
                'name' => $name,
                'description' => "Quyền truy cập module {$name}",
            ]);
        }

        // Gán quyền cho Admin - Toàn quyền
        $admin->permissions()->attach(array_column($permissions, 'id'));

        // Gán quyền cho Manager - Không có phân quyền
        $manager->permissions()->attach([
            $permissions['dashboard']->id,
            $permissions['sales']->id,
            $permissions['debt']->id,
            $permissions['returns']->id,
            $permissions['inventory']->id,
            $permissions['showrooms']->id,
        ]);

        // Gán quyền cho Staff - Chỉ bán hàng
        $staff->permissions()->attach([
            $permissions['dashboard']->id,
            $permissions['sales']->id,
        ]);
    }
}
