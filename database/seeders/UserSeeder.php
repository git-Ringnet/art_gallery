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
        $staffRole = Role::where('name', 'Nhân viên bán hàng')->first();
        $warehouseRole = Role::where('name', 'Thủ kho')->first();
        $accountantRole = Role::where('name', 'Kế toán')->first();
        
        // Danh sách users theo Excel + Demo accounts
        $users = [
            [
                'name' => 'Demo Admin',
                'email' => 'admin@demo.com',
                'phone' => '0900000001',
                'position' => 'Admin',
                'role' => $adminRole,
                'password' => '123456',
            ],
            [
                'name' => 'Demo Kế Toán',
                'email' => 'ketoan@demo.com',
                'phone' => '0900000002',
                'position' => 'Kế toán',
                'role' => $accountantRole,
                'password' => '123456',
            ],
            [
                'name' => 'Demo Thủ Kho',
                'email' => 'quankho@demo.com',
                'phone' => '0900000003',
                'position' => 'Thủ kho',
                'role' => $warehouseRole,
                'password' => '123456',
            ],
            [
                'name' => 'Demo Bảo Hành',
                'email' => 'baohanh@demo.com',
                'phone' => '0900000004',
                'position' => 'Nhân viên bán hàng',
                'role' => $staffRole,
                'password' => '123456',
            ],
        ];

        foreach ($users as $userData) {
            // Kiểm tra user đã tồn tại chưa
            $existingUser = User::where('email', $userData['email'])->first();
            
            if (!$existingUser) {
                User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make($userData['password'] ?? $userData['email']), // Sử dụng password nếu có, ngược lại dùng email
                    'phone' => str_replace(' ', '', $userData['phone']), // Xóa khoảng trắng
                    'email_verified_at' => now(),
                    'role_id' => $userData['role'] ? $userData['role']->id : null,
                    'is_active' => true,
                ]);
                
                $this->command->info("Created user: {$userData['name']} ({$userData['email']})");
            } else {
                $existingUser->update([
                    'password' => Hash::make($userData['password'] ?? $userData['email']),
                    'role_id' => $userData['role'] ? $userData['role']->id : null,
                    'is_active' => true,
                ]);
                $this->command->info("Updated user credentials: {$userData['email']}");
            }
        }
        
        $this->command->info('');
        $this->command->info('=== User Credentials ===');
        $this->command->info('Default password is the email address, except for demo users which use 123456.');
        $this->command->info('');
        foreach ($users as $userData) {
            $password = $userData['password'] ?? $userData['email'];
            $this->command->info("Email: {$userData['email']}");
            $this->command->info("Password: {$password}");
            $this->command->info('---');
        }
    }
}
