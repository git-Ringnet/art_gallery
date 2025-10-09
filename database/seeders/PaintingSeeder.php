<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Painting;

class PaintingSeeder extends Seeder
{
    public function run(): void
    {
        $rate = 25000; // Tỷ giá mặc định
        
        $paintings = [
            [
                'code' => 'P001',
                'name' => 'Mùa Thu Hà Nội',
                'artist' => 'Bùi Xuân Phái',
                'material' => 'Sơn dầu',
                'width' => 80,
                'height' => 60,
                'paint_year' => 1985,
                'price_usd' => 5000,
                'price_vnd' => 5000 * $rate,
                'quantity' => 1,
                'import_date' => now()->subDays(60),
                'status' => 'in_stock',
                'notes' => 'Tranh phong cảnh Hà Nội mùa thu',
            ],
            [
                'code' => 'P002',
                'name' => 'Cô Gái Bên Hoa Sen',
                'artist' => 'Tô Ngọc Vân',
                'material' => 'Sơn dầu',
                'width' => 70,
                'height' => 90,
                'paint_year' => 1943,
                'price_usd' => 8000,
                'price_vnd' => 8000 * $rate,
                'quantity' => 1,
                'import_date' => now()->subDays(45),
                'status' => 'in_stock',
                'notes' => 'Tranh chân dung cổ điển',
            ],
            [
                'code' => 'P003',
                'name' => 'Phố Cổ Hội An',
                'artist' => 'Nguyễn Văn Minh',
                'material' => 'Acrylic',
                'width' => 100,
                'height' => 70,
                'paint_year' => 2020,
                'price_usd' => 3000,
                'price_vnd' => 3000 * $rate,
                'quantity' => 2,
                'import_date' => now()->subDays(30),
                'status' => 'in_stock',
                'notes' => 'Tranh phong cảnh phố cổ',
            ],
            [
                'code' => 'P004',
                'name' => 'Vịnh Hạ Long',
                'artist' => 'Lê Phổ',
                'material' => 'Sơn dầu',
                'width' => 120,
                'height' => 80,
                'paint_year' => 1960,
                'price_usd' => 12000,
                'price_vnd' => 12000 * $rate,
                'quantity' => 1,
                'import_date' => now()->subDays(90),
                'status' => 'in_stock',
                'notes' => 'Tranh phong cảnh thiên nhiên',
            ],
            [
                'code' => 'P005',
                'name' => 'Hoa Sen Trắng',
                'artist' => 'Nguyễn Phan Chánh',
                'material' => 'Màu nước',
                'width' => 50,
                'height' => 70,
                'paint_year' => 1950,
                'price_usd' => 4500,
                'price_vnd' => 4500 * $rate,
                'quantity' => 3,
                'import_date' => now()->subDays(20),
                'status' => 'in_stock',
                'notes' => 'Tranh hoa sen truyền thống',
            ],
            [
                'code' => 'P006',
                'name' => 'Đồng Quê Việt Nam',
                'artist' => 'Trần Văn Cẩn',
                'material' => 'Sơn dầu',
                'width' => 90,
                'height' => 60,
                'paint_year' => 2018,
                'price_usd' => 2500,
                'price_vnd' => 2500 * $rate,
                'quantity' => 2,
                'import_date' => now()->subDays(15),
                'status' => 'in_stock',
                'notes' => 'Tranh đồng quê miền Bắc',
            ],
            [
                'code' => 'P007',
                'name' => 'Chợ Bến Thành',
                'artist' => 'Nguyễn Thanh Bình',
                'material' => 'Acrylic',
                'width' => 80,
                'height' => 60,
                'paint_year' => 2021,
                'price_usd' => 2000,
                'price_vnd' => 2000 * $rate,
                'quantity' => 4,
                'import_date' => now()->subDays(10),
                'status' => 'in_stock',
                'notes' => 'Tranh phong cảnh Sài Gòn',
            ],
            [
                'code' => 'P008',
                'name' => 'Thiếu Nữ Bên Đàn',
                'artist' => 'Mai Trung Thứ',
                'material' => 'Lụa',
                'width' => 60,
                'height' => 80,
                'paint_year' => 1970,
                'price_usd' => 15000,
                'price_vnd' => 15000 * $rate,
                'quantity' => 1,
                'import_date' => now()->subDays(120),
                'status' => 'in_stock',
                'notes' => 'Tranh lụa cổ điển quý hiếm',
            ],
        ];

        foreach ($paintings as $painting) {
            Painting::create($painting);
        }
    }
}
