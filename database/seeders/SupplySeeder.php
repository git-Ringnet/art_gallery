<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supply;

class SupplySeeder extends Seeder
{
    public function run(): void
    {
        $supplies = [
            [
                'code' => 'S001',
                'name' => 'Khung gỗ sồi cao cấp',
                'type' => 'frame',
                'unit' => 'm',
                'quantity' => 50,
                'min_quantity' => 10,
                'notes' => 'Khung gỗ sồi tự nhiên, màu nâu',
            ],
            [
                'code' => 'S002',
                'name' => 'Khung nhôm vàng',
                'type' => 'frame',
                'unit' => 'm',
                'quantity' => 80,
                'min_quantity' => 15,
                'notes' => 'Khung nhôm mạ vàng, sang trọng',
            ],
            [
                'code' => 'S003',
                'name' => 'Khung gỗ trắng',
                'type' => 'frame',
                'unit' => 'm',
                'quantity' => 60,
                'min_quantity' => 12,
                'notes' => 'Khung gỗ sơn trắng, phong cách hiện đại',
            ],
            [
                'code' => 'S004',
                'name' => 'Khung nhôm đen',
                'type' => 'frame',
                'unit' => 'm',
                'quantity' => 45,
                'min_quantity' => 10,
                'notes' => 'Khung nhôm đen, tối giản',
            ],
            [
                'code' => 'S005',
                'name' => 'Canvas cotton 100%',
                'type' => 'canvas',
                'unit' => 'm2',
                'quantity' => 30,
                'min_quantity' => 5,
                'notes' => 'Canvas cotton nguyên chất, chất lượng cao',
            ],
            [
                'code' => 'S006',
                'name' => 'Canvas polyester',
                'type' => 'canvas',
                'unit' => 'm2',
                'quantity' => 25,
                'min_quantity' => 5,
                'notes' => 'Canvas polyester, giá rẻ',
            ],
            [
                'code' => 'S007',
                'name' => 'Kính bảo vệ UV',
                'type' => 'other',
                'unit' => 'm2',
                'quantity' => 20,
                'min_quantity' => 3,
                'notes' => 'Kính chống tia UV, bảo vệ tranh',
            ],
            [
                'code' => 'S008',
                'name' => 'Móc treo tranh',
                'type' => 'other',
                'unit' => 'cái',
                'quantity' => 200,
                'min_quantity' => 50,
                'notes' => 'Móc treo inox, chịu lực tốt',
            ],
        ];

        foreach ($supplies as $supply) {
            Supply::create($supply);
        }
    }
}
