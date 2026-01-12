<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Frame;
use Illuminate\Support\Facades\DB;

class FixSaleFrameData extends Command
{
    protected $signature = 'fix:sale-frame {sale_id} {frame_ids* : Danh sách frame_id, cách nhau bởi dấu cách}';
    protected $description = 'Cập nhật frame_id cho đơn bán và đánh dấu khung đã bán';

    public function handle()
    {
        $saleId = $this->argument('sale_id');
        $frameIds = $this->argument('frame_ids');

        // Kiểm tra đơn bán
        $sale = Sale::find($saleId);
        if (!$sale) {
            $this->error("Không tìm thấy đơn bán ID: {$saleId}");
            return 1;
        }

        $this->info("Đơn bán: #{$sale->id} - {$sale->invoice_code}");

        // Kiểm tra các khung
        $frames = [];
        foreach ($frameIds as $frameId) {
            $frame = Frame::find($frameId);
            if (!$frame) {
                $this->error("Không tìm thấy khung ID: {$frameId}");
                return 1;
            }
            $frames[] = $frame;
            $this->info("Khung: #{$frame->id} - {$frame->name} (Status: {$frame->status})");
        }

        // Tìm sale_items cần cập nhật (frame_id = null và không có painting_id, supply_id)
        $saleItems = SaleItem::where('sale_id', $saleId)
            ->where(function($q) {
                $q->whereNull('frame_id')
                  ->orWhere('frame_id', 0);
            })
            ->get();

        if ($saleItems->count() !== count($frames)) {
            $this->warn("Số sale_items ({$saleItems->count()}) không khớp với số khung (" . count($frames) . ")");
            $this->info("\nCác items cần cập nhật:");
            foreach ($saleItems as $item) {
                $this->line("  - ID: {$item->id}, description: " . ($item->description ?? '-'));
            }
            
            if (!$this->confirm('Vẫn tiếp tục? (sẽ cập nhật theo thứ tự)')) {
                return 0;
            }
        }

        $this->info("\nSẽ cập nhật:");
        $count = min($saleItems->count(), count($frames));
        for ($i = 0; $i < $count; $i++) {
            $this->line("  - Sale Item #{$saleItems[$i]->id} ({$saleItems[$i]->description}) → Khung #{$frames[$i]->id} ({$frames[$i]->name})");
        }

        if (!$this->confirm('Xác nhận cập nhật?')) {
            $this->info('Đã hủy');
            return 0;
        }

        DB::beginTransaction();
        try {
            for ($i = 0; $i < $count; $i++) {
                $item = $saleItems[$i];
                $frame = $frames[$i];
                
                // Cập nhật frame_id cho sale_item
                $item->update(['frame_id' => $frame->id]);
                $this->info("✓ Đã cập nhật sale_item #{$item->id} với frame_id = {$frame->id}");

                // Đánh dấu khung đã bán
                $frame->update(['status' => 'sold']);
                $this->info("✓ Đã đánh dấu khung #{$frame->id} là 'sold'");
            }

            DB::commit();
            $this->info("\n✅ Hoàn tất! Đã cập nhật {$count} items.");
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Lỗi: " . $e->getMessage());
            return 1;
        }
    }
}
