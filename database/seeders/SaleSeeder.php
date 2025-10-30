<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Payment;
use App\Models\Debt;
use App\Models\Customer;
use App\Models\Showroom;
use App\Models\Painting;
use App\Models\Supply;
use App\Models\User;
use App\Models\ExchangeRate;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();
        $showrooms = Showroom::all();
        $paintings = Painting::all();
        $supplies = Supply::all();
        $user = User::first();
        
        // Sử dụng tỷ giá mặc định
        $exchangeRate = 25000;

        // Tạo 10 hóa đơn mẫu với các trạng thái khác nhau
        for ($i = 1; $i <= 20; $i++) {
            // Phân bổ trạng thái: 3 chờ duyệt, 6 đã hoàn thành, 1 đã hủy
            $saleStatus = $i <= 3 ? 'pending' : ($i <= 9 ? 'completed' : 'cancelled');
            
            $sale = Sale::create([
                'invoice_code' => 'HD2510' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'customer_id' => $customers->random()->id,
                'showroom_id' => $showrooms->random()->id,
                'user_id' => $user->id,
                'sale_date' => now()->subDays(rand(1, 30)),
                'exchange_rate' => $exchangeRate,
                'subtotal_usd' => 0,
                'subtotal_vnd' => 0,
                'discount_percent' => rand(0, 20),
                'discount_usd' => 0,
                'discount_vnd' => 0,
                'total_usd' => 0,
                'total_vnd' => 0,
                'paid_amount' => 0,
                'debt_amount' => 0,
                'payment_status' => 'unpaid',
                'sale_status' => $saleStatus,
                'notes' => 'Hóa đơn mẫu số ' . $i,
            ]);

            // Thêm 1-3 sản phẩm cho mỗi hóa đơn
            $itemCount = rand(1, 3);
            for ($j = 1; $j <= $itemCount; $j++) {
                $painting = $paintings->random();
                $supply = $supplies->random();
                
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'painting_id' => $painting->id,
                    'description' => $painting->name,
                    'quantity' => rand(1, 2),
                    'supply_id' => $supply->id,
                    'supply_length' => rand(1, 5),
                    'currency' => 'USD',
                    'price_usd' => $painting->price_usd,
                    'price_vnd' => $painting->price_usd * $exchangeRate,
                    'total_usd' => $painting->price_usd,
                    'total_vnd' => $painting->price_usd * $exchangeRate,
                ]);
            }

            // Tính toán totals
            $sale->calculateTotals();

            // Tạo thanh toán ngẫu nhiên
            $paymentAmount = rand(0, 1) ? rand(50, 100) / 100 * $sale->total_vnd : 0;
            
            if ($paymentAmount > 0) {
                Payment::create([
                    'sale_id' => $sale->id,
                    'amount' => $paymentAmount,
                    'payment_method' => ['cash', 'bank_transfer', 'card'][rand(0, 2)],
                    'payment_date' => $sale->sale_date,
                    'created_by' => $user->id,
                ]);
            }

            // Tạo debt nếu còn nợ
            if ($sale->debt_amount > 0) {
                Debt::create([
                    'sale_id' => $sale->id,
                    'customer_id' => $sale->customer_id,
                    'total_amount' => $sale->total_vnd,
                    'paid_amount' => $sale->paid_amount,
                    'debt_amount' => $sale->debt_amount,
                    'due_date' => $sale->sale_date->addDays(30),
                    'status' => $sale->payment_status, // Đồng bộ với sale
                ]);
            }
        }
    }
}