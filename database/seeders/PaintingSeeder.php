<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Painting;

class PaintingSeeder extends Seeder
{
    public function run(): void
    {
        $paintings = [
            // Tranh có giá USD
            [
                'code' => 'P001',
                'name' => 'Mùa Thu Hà Nội',
                'artist' => 'Bùi Xuân Phái',
                'material' => 'Sơn dầu',
                'width' => 80,
                'height' => 60,
                'paint_year' => 1985,
                'price_usd' => 5000,
                'price_vnd' => null,
                'image' => 'paintings/tranh2.jpg',
                'quantity' => 1,
                'import_date' => now()->subDays(60),
                'status' => 'in_stock',
                'notes' => 'Tranh phong cảnh Hà Nội mùa thu - Giá USD',
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
                'price_vnd' => null,
                'image' => 'paintings/tranh3.jpg',
                'quantity' => 1,
                'import_date' => now()->subDays(45),
                'status' => 'in_stock',
                'notes' => 'Tranh chân dung cổ điển - Giá USD',
            ],
            // Tranh có giá VND
            [
                'code' => 'P003',
                'name' => 'Phố Cổ Hội An',
                'artist' => 'Nguyễn Văn Minh',
                'material' => 'Acrylic',
                'width' => 100,
                'height' => 70,
                'paint_year' => 2020,
                'price_usd' => null,
                'price_vnd' => 75000000,
                'image' => 'paintings/tranh4.jpg',
                'quantity' => 1,
                'import_date' => now()->subDays(30),
                'status' => 'in_stock',
                'notes' => 'Tranh phong cảnh phố cổ - Giá VND',
            ],
            [
                'code' => 'P004',
                'name' => 'Vịnh Hạ Long',
                'artist' => 'Lê Phổ',
                'material' => 'Sơn dầu',
                'width' => 120,
                'height' => 80,
                'paint_year' => 1960,
                'price_usd' => null,
                'price_vnd' => 300000000,
                'image' => 'paintings/tranh5.jpg',
                'quantity' => 1,
                'import_date' => now()->subDays(90),
                'status' => 'in_stock',
                'notes' => 'Tranh phong cảnh thiên nhiên - Giá VND',
            ],
            // Tranh có cả 2 giá
            [
                'code' => 'P005',
                'name' => 'Hoa Sen Trắng',
                'artist' => 'Nguyễn Phan Chánh',
                'material' => 'Màu nước',
                'width' => 50,
                'height' => 70,
                'paint_year' => 1950,
                'price_usd' => 4500,
                'price_vnd' => null,
                'image' => 'paintings/tranh6.jpg',
                'quantity' => 1,
                'import_date' => now()->subDays(20),
                'status' => 'in_stock',
                'notes' => 'Tranh hoa sen truyền thống - Có cả 2 giá',
            ],
            [
                'code' => 'P006',
                'name' => 'Đồng Quê Việt Nam',
                'artist' => 'Trần Văn Cẩn',
                'material' => 'Sơn dầu',
                'width' => 90,
                'height' => 60,
                'paint_year' => 2018,
                'price_usd' => null,
                'price_vnd' => 62500000,
                'image' => 'paintings/tranh7.jpg',
                'quantity' => 1,
                'import_date' => now()->subDays(15),
                'status' => 'in_stock',
                'notes' => 'Tranh đồng quê miền Bắc - Có cả 2 giá',
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
                'price_vnd' => null,
                'image' => 'paintings/tranh8.jpg',
                'quantity' => 1,
                'import_date' => now()->subDays(10),
                'status' => 'in_stock',
                'notes' => 'Tranh phong cảnh Sài Gòn - Giá USD',
            ],
            [
                'code' => 'P008',
                'name' => 'Làng Quê Mùa Xuân',
                'artist' => 'Nguyễn Văn Tý',
                'material' => 'Sơn dầu',
                'width' => 100,
                'height' => 70,
                'paint_year' => 2019,
                'price_usd' => null,
                'price_vnd' => 50000000,
                'image' => null,
                'quantity' => 1,
                'import_date' => now()->subDays(5),
                'status' => 'in_stock',
                'notes' => 'Tranh làng quê - Giá VND',
            ],
        ];

        foreach ($paintings as $painting) {
            Painting::create($painting);
        }
    }
}
