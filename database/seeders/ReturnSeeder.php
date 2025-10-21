<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReturnModel;
use App\Models\ReturnItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\Payment;

class ReturnSeeder extends Seeder
{
    public function run(): void
    {
        // Get some sales to create returns for
        $sales = Sale::with('items')->take(3)->get();
        $user = User::first();

        foreach ($sales as $sale) {
            // Only create return if sale has items
            if ($sale->items->count() === 0) continue;

            // Create return for 1-2 items from the sale
            $itemsToReturn = $sale->items->random(min(2, $sale->items->count()));
            
            $totalRefund = 0;
            $returnItems = [];

            foreach ($itemsToReturn as $saleItem) {
                // Return 1 or half of quantity
                $returnQty = min(1, ceil($saleItem->quantity / 2));
                
                // Determine item type and id
                $itemType = $saleItem->painting_id ? 'painting' : 'supply';
                $itemId = $saleItem->painting_id ?: $saleItem->supply_id;
                $unitPrice = $saleItem->price_vnd;
                
                $subtotal = $returnQty * $unitPrice;
                $totalRefund += $subtotal;

                $returnItems[] = [
                    'sale_item_id' => $saleItem->id,
                    'item_type' => $itemType,
                    'item_id' => $itemId,
                    'quantity' => $returnQty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'reason' => fake()->randomElement([
                        'Sản phẩm bị lỗi',
                        'Không đúng mô tả',
                        'Khách hàng đổi ý',
                        'Kích thước không phù hợp',
                        'Màu sắc không như mong đợi'
                    ]),
                ];
            }

            // Randomly choose type
            $type = fake()->randomElement(['return', 'return', 'exchange']); // More returns than exchanges
            $exchangeAmount = null;
            
            if ($type === 'exchange') {
                // For exchange, calculate difference (can be positive or negative)
                $exchangeAmount = fake()->randomElement([
                    fake()->numberBetween(-500000, -100000), // Customer gets refund
                    0, // Equal value exchange
                    fake()->numberBetween(100000, 500000), // Customer pays more
                ]);
            }

            // Create return record
            $return = ReturnModel::create([
                'return_code' => ReturnModel::generateReturnCode(),
                'type' => $type,
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'return_date' => fake()->dateTimeBetween($sale->sale_date, 'now'),
                'total_refund' => $totalRefund,
                'exchange_amount' => $exchangeAmount,
                'reason' => fake()->randomElement([
                    'Sản phẩm không đạt chất lượng',
                    'Khách hàng không hài lòng',
                    'Giao nhầm sản phẩm',
                    'Sản phẩm bị hư hỏng trong quá trình vận chuyển',
                    'Muốn đổi sang sản phẩm khác',
                    'Kích thước không phù hợp',
                    'Màu sắc không như mong đợi'
                ]),
                'status' => fake()->randomElement(['completed', 'completed', 'approved', 'pending', 'cancelled']),
                'processed_by' => $user->id,
                'notes' => fake()->optional(0.3)->sentence(),
            ]);

            // Create return items
            foreach ($returnItems as $itemData) {
                $return->items()->create($itemData);
            }

            // Create refund payment if completed
            if ($return->status === 'completed') {
                Payment::create([
                    'sale_id' => $sale->id,
                    'payment_date' => $return->return_date,
                    'amount' => -$totalRefund,
                    'payment_method' => 'cash',
                    'notes' => "Hoàn tiền cho phiếu trả {$return->return_code}",
                    'created_by' => $user->id,
                ]);
            }
        }

        $this->command->info('✓ Created sample returns');
    }
}
