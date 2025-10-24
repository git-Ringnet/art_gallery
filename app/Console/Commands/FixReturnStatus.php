<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\ReturnItem;
use Illuminate\Support\Facades\DB;

class FixReturnStatus extends Command
{
    protected $signature = 'fix:return-status';
    protected $description = 'Fix return status - set sales to cancelled if all items returned';

    public function handle()
    {
        $this->info('Starting to fix return status...');

        try {
            DB::beginTransaction();

            // Get all sales that have completed returns
            $sales = Sale::whereHas('returns', function($q) {
                $q->where('type', 'return')
                  ->where('status', 'completed');
            })->get();

            $this->info("Found {$sales->count()} sales with completed returns");

            foreach ($sales as $sale) {
                $this->info("Processing sale: {$sale->invoice_code}");
                
                // Calculate total items (excluding items with quantity = 0)
                $totalSaleItems = $sale->items->where('quantity', '>', 0)->sum('quantity');
                
                // Calculate total returned items
                $totalReturnedItems = ReturnItem::whereHas('return', function($q) use ($sale) {
                    $q->where('sale_id', $sale->id)
                      ->where('status', 'completed')
                      ->where('type', 'return'); // Only count returns, not exchanges
                })->sum('quantity');

                $this->info("  Total items: {$totalSaleItems}");
                $this->info("  Returned items: {$totalReturnedItems}");

                if ($totalReturnedItems >= $totalSaleItems && $totalSaleItems > 0) {
                    // All items returned, cancel the sale
                    if ($sale->payment_status !== 'cancelled') {
                        $this->info("  ⚠️  Setting status to cancelled");
                        $sale->update(['payment_status' => 'cancelled']);
                        
                        // Update debt if exists
                        if ($sale->debt) {
                            $sale->debt->update(['status' => 'cancelled']);
                        }
                    } else {
                        $this->info("  ✓ Already cancelled");
                    }
                } else {
                    // Partial return or no return, update payment status
                    $this->info("  ✓ Partial return, updating payment status");
                    $sale->updatePaymentStatus();
                }
                
                $this->info("  Current status: {$sale->fresh()->payment_status}");
                $this->info("");
            }

            DB::commit();
            
            $this->info("✓ Successfully fixed {$sales->count()} sales");
            
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
