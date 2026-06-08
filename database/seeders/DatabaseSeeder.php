<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,  // Tạo roles và permissions trước
            ShowroomSeeder::class,
            UserSeeder::class,         // Tạo users sau khi có roles
            CustomerSeeder::class,     // Tạo khách hàng mẫu
            SupplySeeder::class,       // Tạo vật tư mẫu
            PaintingSeeder::class,     // Tạo tranh mẫu
            FrameSeeder::class,        // Tạo khung tranh sau khi có vật tư
            SaleSeeder::class,         // Tạo đơn hàng sau khi có đầy đủ dữ liệu
            ReturnSeeder::class,       // Tạo đổi trả sau khi có đơn hàng
        ]);
    }
}
