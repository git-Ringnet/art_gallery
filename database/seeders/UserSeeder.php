<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy các roles
        $adminRole = Role::where('name', 'Admin')->first();
        $managerRole = Role::where('name', 'Quản lý bán hàng')->orWhere('name', 'QL bán hàng')->first();
        $staffRole = Role::where('name', 'Nhân viên bán hàng')->orWhere('name', 'KD BTA')->first();
        
        // Danh sách users theo Excel
        $users = [
            [
                'name' => 'Ms.Hằng',
                'email' => 'hokinhdoanhbenthanhart@gmail.com',
                'phone' => '0906798887',
                'position' => 'Admin', // Vị trí
                'role' => $adminRole,
                // Quyền: Bán hàng (x), Kho (x), Báo cáo BTA (x), Báo cáo AN (x)
            ],
            [
                'name' => 'Mrs.Tâm',
                'email' => 'tam.benthanhart@gmail.com',
                'phone' => '0909 729 598',
                'position' => 'QL bán hàng',
                'role' => $managerRole,
                // Quyền: Bán hàng (x)
            ],
            [
                'name' => 'Mrs.Thắm',
                'email' => 'benthanhart.sales@gmail.com',
                'phone' => '0932 687 588',
                'position' => 'KD BTA',
                'role' => $staffRole,
                // Quyền: Bán hàng (x), Báo cáo BTA (x)
            ],
            [
                'name' => 'Mrs.Tịnh',
                'email' => 'tinh.benthanhart@gmail.com',
                'phone' => '0909 910 784',
                'position' => 'KD AN',
                'role' => $staffRole,
                // Quyền: Bán hàng (x)
            ],
            [
                'name' => 'Ms.Nhi',
                'email' => 'nhi.angallery@gmail.com',
                'phone' => '0352981889',
                'position' => 'KD AN',
                'role' => $staffRole,
                // Quyền: Bán hàng (x), Báo cáo AN (x)
            ],
        ];

        foreach ($users as $userData) {
            // Kiểm tra user đã tồn tại chưa
            $existingUser = User::where('email', $userData['email'])->first();
            
            if (!$existingUser) {
                User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make($userData['email']), // Password = email
                    'phone' => str_replace(' ', '', $userData['phone']), // Xóa khoảng trắng
                    'email_verified_at' => now(),
                    'role_id' => $userData['role'] ? $userData['role']->id : null,
                    'is_active' => true,
                ]);
                
                $this->command->info("Created user: {$userData['name']} ({$userData['email']})");
            } else {
                $this->command->warn("User already exists: {$userData['email']}");
            }
        }
        
        $this->command->info('');
        $this->command->info('=== User Credentials ===');
        $this->command->info('Password for all users is their email address');
        $this->command->info('');
        foreach ($users as $userData) {
            $this->command->info("Email: {$userData['email']}");
            $this->command->info("Password: {$userData['email']}");
            $this->command->info('---');
        }
    }
}
