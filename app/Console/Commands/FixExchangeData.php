<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReturnModel;
use App\Models\Sale;
use App\Models\Painting;
use App\Models\Supply;
use Illuminate\Support\Facades\DB;

class FixExchangeData extends Command
{
    protected $signature = 'fix:exchange-data';
    protected $description = 'Fix exchange data - update sale items with exchanged products';

    public function handle()
    {
        $this->info('Starting to fix exchange data...');

        try {
            DB::beginTransaction();

            // Get all completed exchanges
            $exchanges = ReturnModel::where('type', 'exchange')
                ->where('status', 'completed')
                ->with(['items.saleItem', 'exchangeItems', 'sale'])
                ->get();

            $this->info("Found {$exchanges->count()} completed exchanges");

            foreach ($exchanges as $return) {
                $this->info("Processing exchange: {$return->return_code}");
                
                $sale = $return->sale;
                
                // Remove or reduce old items (set quantity to 0 instead of deleting)
                foreach ($return->items as $returnItem) {
                    $saleItem = $returnItem->saleItem;
                    
                    if (!$saleItem) {
                        $this->warn("  - Sale item not found for return item {$returnItem->id}");
                        continue;
                    }
                    
                    // Check if item still exists
                    if ($saleItem->quantity <= $returnItem->quantity) {
                        $this->info("  - Setting sale item quantity to 0: {$saleItem->description}");
                        $saleItem->update([
                            'quantity' => 0,
                            'total_usd' => 0,
                            'total_vnd' => 0,
                        ]);
                    } else {
                        $newQty = $saleItem->quantity - $returnItem->quantity;
                        $this->info("  - Reducing sale item quantity: {$saleItem->description} from {$saleItem->quantity} to {$newQty}");
                        $saleItem->update([
                            'quantity' => $newQty,
                            'total_usd' => $newQty * $saleItem->price_usd * (1 - $saleItem->discount_percent / 100),
                            'total_vnd' => $newQty * $saleItem->price_vnd * (1 - $saleItem->discount_percent / 100),
                        ]);
                    }
                }
                
                // Add new exchange items to sale
                foreach ($return->exchangeItems as $exchangeItem) {
                    $priceUsd = $exchangeItem->unit_price / $sale->exchange_rate;
                    $priceVnd = $exchangeItem->unit_price;
                    
                    // Get item description
                    $description = '';
                    if ($exchangeItem->item_type === 'painting') {
                        $painting = Painting::find($exchangeItem->item_id);
                        $description = $painting ? $painting->name : 'N/A';
                    } else {
                        $supply = Supply::find($exchangeItem->item_id);
                        $description = $supply ? $supply->name : 'N/A';
                    }
                    
                    // Check if this exchange item already added
                    $exists = $sale->items()
                        ->where('painting_id', $exchangeItem->item_type === 'painting' ? $exchangeItem->item_id : null)
                        ->where('supply_id', $exchangeItem->item_type === 'supply' ? $exchangeItem->item_id : null)
                        ->where('quantity', $exchangeItem->quantity)
                        ->where('price_vnd', $priceVnd)
                        ->exists();
                    
                    if (!$exists) {
                        $this->info("  - Adding new sale item: {$description}");
                        $sale->items()->create([
                            'painting_id' => $exchangeItem->item_type === 'painting' ? $exchangeItem->item_id : null,
                            'supply_id' => $exchangeItem->item_type === 'supply' ? $exchangeItem->item_id : null,
                            'description' => $description,
                            'quantity' => $exchangeItem->quantity,
                            'currency' => 'VND',
                            'price_usd' => $priceUsd,
                            'price_vnd' => $priceVnd,
                            'discount_percent' => 0,
                            'total_usd' => $exchangeItem->subtotal / $sale->exchange_rate,
                            'total_vnd' => $exchangeItem->subtotal,
                        ]);
                    } else {
                        $this->warn("  - Exchange item already exists, skipping: {$description}");
                    }
                }
                
                // Recalculate sale totals
                $this->info("  - Recalculating sale totals");
                $sale->calculateTotals();
                
                // Make sure exchange doesn't set status to cancelled
                if ($sale->payment_status === 'cancelled') {
                    $this->info("  - Fixing cancelled status");
                    $sale->updatePaymentStatus();
                }
                
                $this->info("  ✓ Completed exchange: {$return->return_code}");
            }

            DB::commit();
            
            $this->info('');
            $this->info("✓ Successfully fixed {$exchanges->count()} exchanges");
            
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
