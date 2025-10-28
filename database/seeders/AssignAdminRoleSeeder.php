<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class AssignAdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy role Admin
        $adminRole = Role::where('name', 'Admin')->first();
        
        if (!$adminRole) {
            echo "Role Admin không tồn tại. Chạy PermissionsSeeder trước!\n";
            return;
        }
        
        // Gán role Admin cho tất cả user có email admin
        $adminUsers = User::where('email', 'like', '%admin%')->get();
        
        foreach ($adminUsers as $user) {
            $user->role_id = $adminRole->id;
            $user->is_active = true;
            $user->save();
            echo "✓ Đã gán role Admin cho user: {$user->email}\n";
        }
        
        // Nếu không có user admin nào, tạo mới
        if ($adminUsers->isEmpty()) {
            $user = User::create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'role_id' => $adminRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            echo "✓ Đã tạo user Admin mới: {$user->email}\n";
        }
    }
}
