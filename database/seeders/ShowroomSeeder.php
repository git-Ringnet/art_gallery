<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Showroom;

class ShowroomSeeder extends Seeder
{
    public function run(): void
    {
        Showroom::create([
            'code' => 'SR001',
            'name' => 'Gallery Hà Nội',
            'phone' => '0241234567',
            'address' => '123 Hoàng Quốc Việt, Cầu Giấy, Hà Nội',
            'bank_name' => 'Vietcombank',
            'bank_account' => '1234567890',
            'bank_holder' => 'Gallery Hà Nội',
            'is_active' => true,
        ]);

        Showroom::create([
            'code' => 'SR002',
            'name' => 'Gallery Hồ Chí Minh',
            'phone' => '0281234567',
            'address' => '456 Nguyễn Huệ, Quận 1, TP.HCM',
            'bank_name' => 'Techcombank',
            'bank_account' => '0987654321',
            'bank_holder' => 'Gallery HCM',
            'is_active' => true,
        ]);

        Showroom::create([
            'code' => 'SR003',
            'name' => 'Gallery Đà Nẵng',
            'phone' => '0236123456',
            'address' => '789 Trần Phú, Hải Châu, Đà Nẵng',
            'bank_name' => 'VPBank',
            'bank_account' => '5555666677',
            'bank_holder' => 'Gallery Đà Nẵng',
            'is_active' => true,
        ]);
    }
}
