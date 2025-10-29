<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy role Admin
        $adminRole = \App\Models\Role::where('name', 'Admin')->first();
        
        // Tạo admin user với role
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role_id' => $adminRole ? $adminRole->id : null,
            'is_active' => true,
        ]);

        // Hoặc thêm nhiều user mẫu khác nếu muốn
        User::factory(3)->create();
    }
}
