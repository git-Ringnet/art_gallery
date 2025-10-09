<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Nguyễn Văn An',
                'phone' => '0912345678',
                'email' => 'nguyenvanan@email.com',
                'address' => '123 Láng Hạ, Đống Đa, Hà Nội',
                'total_purchased' => 0,
                'total_debt' => 0,
                'notes' => 'Khách hàng VIP, thích tranh phong cảnh',
            ],
            [
                'name' => 'Trần Thị Bình',
                'phone' => '0923456789',
                'email' => 'tranthibinh@email.com',
                'address' => '456 Nguyễn Trãi, Thanh Xuân, Hà Nội',
                'total_purchased' => 0,
                'total_debt' => 0,
                'notes' => 'Sưu tầm tranh cổ điển',
            ],
            [
                'name' => 'Lê Minh Cường',
                'phone' => '0934567890',
                'email' => 'leminhcuong@email.com',
                'address' => '789 Lê Lợi, Quận 1, TP.HCM',
                'total_purchased' => 0,
                'total_debt' => 0,
                'notes' => 'Khách hàng doanh nghiệp',
            ],
            [
                'name' => 'Phạm Thu Hà',
                'phone' => '0945678901',
                'email' => 'phamthuha@email.com',
                'address' => '321 Hai Bà Trưng, Quận 3, TP.HCM',
                'total_purchased' => 0,
                'total_debt' => 0,
                'notes' => 'Thích tranh hiện đại',
            ],
            [
                'name' => 'Hoàng Văn Đức',
                'phone' => '0956789012',
                'email' => 'hoangvanduc@email.com',
                'address' => '654 Trần Hưng Đạo, Hải Châu, Đà Nẵng',
                'total_purchased' => 0,
                'total_debt' => 0,
                'notes' => 'Khách hàng thường xuyên',
            ],
            [
                'name' => 'Vũ Thị Em',
                'phone' => '0967890123',
                'email' => 'vuthiem@email.com',
                'address' => '987 Lý Thường Kiệt, Hoàn Kiếm, Hà Nội',
                'total_purchased' => 0,
                'total_debt' => 0,
                'notes' => 'Mua tranh trang trí văn phòng',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}
