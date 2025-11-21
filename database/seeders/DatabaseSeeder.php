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
            // PermissionsSeeder::class,  // Tạo roles và permissions trước
            // ShowroomSeeder::class,
            // SupplySeeder::class,
            // PaintingSeeder::class,
            CustomerSeeder::class,
            //UserSeeder::class,  // Tạo users sau khi có roles
            //FrameSeeder::class,  // Tạo khung tranh sau khi có supplies
            // SaleSeeder::class,
            // ReturnSeeder::class,
        ]);
    }
}
