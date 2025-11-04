<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Frame;
use App\Models\FrameItem;
use App\Models\Supply;

class FrameSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy các cây gỗ từ database
        $cayGoSoi = Supply::where('code', 'S001')->first();
        $cayGoThong = Supply::where('code', 'S002')->first();
        $cayGoTech = Supply::where('code', 'S003')->first();
        $cayGoOcCho = Supply::where('code', 'S004')->first();
        $cayGoSoiTrang = Supply::where('code', 'S005')->first();

        if (!$cayGoSoi || !$cayGoThong || !$cayGoTech || !$cayGoOcCho || !$cayGoSoiTrang) {
            $this->command->error('Vui lòng chạy SupplySeeder trước!');
            return;
        }

        // Khung 1: Khung tranh 40x60 - Dùng 1 loại cây
        $frame1 = Frame::create([
            'name' => 'Khung tranh 40x60 - Gỗ sồi',
            'cost_price' => 150000,
            'notes' => 'Khung tranh cỡ trung, phù hợp tranh phong cảnh',
        ]);

        FrameItem::create([
            'frame_id' => $frame1->id,
            'supply_id' => $cayGoSoi->id,
            'tree_quantity' => 1,
            'length_per_tree' => 240,
            'total_length' => 240,
            'use_whole_trees' => false,
        ]);

        // Trừ kho
        $cayGoSoi->decrement('quantity', 240);

        // Khung 2: Khung tranh 50x70 - Dùng 2 loại cây
        $frame2 = Frame::create([
            'name' => 'Khung tranh 50x70 - Gỗ thông + Gỗ tếch',
            'cost_price' => 220000,
            'notes' => 'Khung tranh lớn, kết hợp 2 loại gỗ',
        ]);

        FrameItem::create([
            'frame_id' => $frame2->id,
            'supply_id' => $cayGoThong->id,
            'tree_quantity' => 1,
            'length_per_tree' => 200,
            'total_length' => 200,
            'use_whole_trees' => false,
        ]);

        FrameItem::create([
            'frame_id' => $frame2->id,
            'supply_id' => $cayGoTech->id,
            'tree_quantity' => 1,
            'length_per_tree' => 180,
            'total_length' => 180,
            'use_whole_trees' => false,
        ]);

        // Trừ kho
        $cayGoThong->decrement('quantity', 200);
        $cayGoTech->decrement('quantity', 180);

        // Khung 3: Khung tranh 30x40 - Dùng nguyên cây
        $frame3 = Frame::create([
            'name' => 'Khung tranh 30x40 - Gỗ óc chó',
            'cost_price' => 180000,
            'notes' => 'Khung tranh nhỏ, dùng nguyên cây vì phần còn lại quá ngắn',
        ]);

        FrameItem::create([
            'frame_id' => $frame3->id,
            'supply_id' => $cayGoOcCho->id,
            'tree_quantity' => 1,
            'length_per_tree' => 180,
            'total_length' => 180,
            'use_whole_trees' => true, // Dùng nguyên cây
        ]);

        // Trừ kho (cả chiều dài và số cây)
        $cayGoOcCho->decrement('quantity', 180);
        $cayGoOcCho->decrement('tree_count', 1);

        // Khung 4: Khung tranh 60x80 - Dùng nhiều cây
        $frame4 = Frame::create([
            'name' => 'Khung tranh 60x80 - Gỗ sồi trắng',
            'cost_price' => 280000,
            'notes' => 'Khung tranh rất lớn, dùng 2 cây',
        ]);

        FrameItem::create([
            'frame_id' => $frame4->id,
            'supply_id' => $cayGoSoiTrang->id,
            'tree_quantity' => 2,
            'length_per_tree' => 200,
            'total_length' => 400,
            'use_whole_trees' => false,
        ]);

        // Trừ kho
        $cayGoSoiTrang->decrement('quantity', 400);

        // Khung 5: Khung tranh 70x100 - Kết hợp 3 loại cây
        $frame5 = Frame::create([
            'name' => 'Khung tranh 70x100 - Cao cấp',
            'cost_price' => 450000,
            'notes' => 'Khung tranh siêu lớn, kết hợp 3 loại gỗ cao cấp',
        ]);

        FrameItem::create([
            'frame_id' => $frame5->id,
            'supply_id' => $cayGoSoi->id,
            'tree_quantity' => 1,
            'length_per_tree' => 250,
            'total_length' => 250,
            'use_whole_trees' => false,
        ]);

        FrameItem::create([
            'frame_id' => $frame5->id,
            'supply_id' => $cayGoThong->id,
            'tree_quantity' => 1,
            'length_per_tree' => 200,
            'total_length' => 200,
            'use_whole_trees' => false,
        ]);

        FrameItem::create([
            'frame_id' => $frame5->id,
            'supply_id' => $cayGoSoiTrang->id,
            'tree_quantity' => 1,
            'length_per_tree' => 150,
            'total_length' => 150,
            'use_whole_trees' => false,
        ]);

        // Trừ kho
        $cayGoSoi->decrement('quantity', 250);
        $cayGoThong->decrement('quantity', 200);
        $cayGoSoiTrang->decrement('quantity', 150);

        $this->command->info('Đã tạo 5 khung tranh mẫu!');
    }
}
