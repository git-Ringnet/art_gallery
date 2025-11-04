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
                'name' => 'Cây gỗ sồi cao cấp',
                'type' => 'frame',
                'unit' => 'cm',
                'quantity' => 1500, // 5 cây x 300cm
                'tree_count' => 5,
                'min_quantity' => 300,
                'notes' => 'Cây gỗ sồi tự nhiên, màu nâu, mỗi cây dài 300cm',
            ],
            [
                'code' => 'S002',
                'name' => 'Cây gỗ thông',
                'type' => 'frame',
                'unit' => 'cm',
                'quantity' => 1000, // 4 cây x 250cm
                'tree_count' => 4,
                'min_quantity' => 250,
                'notes' => 'Cây gỗ thông tự nhiên, mỗi cây dài 250cm',
            ],
            [
                'code' => 'S003',
                'name' => 'Cây gỗ tếch',
                'type' => 'frame',
                'unit' => 'cm',
                'quantity' => 800, // 4 cây x 200cm
                'tree_count' => 4,
                'min_quantity' => 200,
                'notes' => 'Cây gỗ tếch cao cấp, mỗi cây dài 200cm',
            ],
            [
                'code' => 'S004',
                'name' => 'Cây gỗ óc chó',
                'type' => 'frame',
                'unit' => 'cm',
                'quantity' => 600, // 3 cây x 200cm
                'tree_count' => 3,
                'min_quantity' => 200,
                'notes' => 'Cây gỗ óc chó nhập khẩu, mỗi cây dài 200cm',
            ],
            [
                'code' => 'S005',
                'name' => 'Cây gỗ sồi trắng',
                'type' => 'frame',
                'unit' => 'cm',
                'quantity' => 1200, // 6 cây x 200cm
                'tree_count' => 6,
                'min_quantity' => 200,
                'notes' => 'Cây gỗ sồi trắng, phong cách hiện đại, mỗi cây dài 200cm',
            ],
            [
                'code' => 'S006',
                'name' => 'Canvas cotton 100%',
                'type' => 'canvas',
                'unit' => 'm2',
                'quantity' => 30,
                'tree_count' => 0,
                'min_quantity' => 5,
                'notes' => 'Canvas cotton nguyên chất, chất lượng cao',
            ],
            [
                'code' => 'S007',
                'name' => 'Canvas polyester',
                'type' => 'canvas',
                'unit' => 'm2',
                'quantity' => 25,
                'tree_count' => 0,
                'min_quantity' => 5,
                'notes' => 'Canvas polyester, giá rẻ',
            ],
            [
                'code' => 'S008',
                'name' => 'Kính bảo vệ UV',
                'type' => 'other',
                'unit' => 'm2',
                'quantity' => 20,
                'tree_count' => 0,
                'min_quantity' => 3,
                'notes' => 'Kính chống tia UV, bảo vệ tranh',
            ],
            [
                'code' => 'S009',
                'name' => 'Móc treo tranh',
                'type' => 'other',
                'unit' => 'cái',
                'quantity' => 200,
                'tree_count' => 0,
                'min_quantity' => 50,
                'notes' => 'Móc treo inox, chịu lực tốt',
            ],
        ];

        foreach ($supplies as $supply) {
            Supply::create($supply);
        }
    }
}
