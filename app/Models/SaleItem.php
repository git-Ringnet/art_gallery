<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'painting_id',
        'frame_id',
        'description',
        'quantity',
        'supply_id',
        'supply_length',
        'currency',
        'price_usd',
        'price_vnd',
        'discount_percent',
        'discount_amount_usd',
        'discount_amount_vnd',
        'total_usd',
        'total_vnd',
        'is_returned',
        'returned_quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'supply_length' => 'decimal:2',
            'price_usd' => 'decimal:2',
            'price_vnd' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount_usd' => 'decimal:2',
            'discount_amount_vnd' => 'decimal:2',
            'total_usd' => 'decimal:2',
            'total_vnd' => 'decimal:2',
        ];
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function painting()
    {
        return $this->belongsTo(Painting::class);
    }

    public function frame()
    {
        return $this->belongsTo(Frame::class);
    }

    public function supply()
    {
        return $this->belongsTo(Supply::class);
    }

    public function calculateTotals()
    {
        // Lấy exchange_rate từ sale
        $exchangeRate = $this->sale->exchange_rate ?? 0;

        if ($this->currency === 'USD') {
            // Item USD - CHỈ tính USD, KHÔNG tính VND
            $subtotal = $this->quantity * $this->price_usd;

            // Giảm theo %
            $discountPercentAmount = $subtotal * ($this->discount_percent / 100);

            // Giảm theo số tiền cố định (USD)
            $discountFixedAmount = $this->discount_amount_usd ?? 0;

            // Tổng giảm = giảm % + giảm số tiền
            $totalDiscount = $discountPercentAmount + $discountFixedAmount;

            $this->total_usd = max(0, $subtotal - $totalDiscount);
            $this->total_vnd = 0; // Item USD không có VND
            $this->price_vnd = 0;
        } else {
            // Item VND - CHỈ tính VND, KHÔNG tính USD
            $subtotal = $this->quantity * $this->price_vnd;

            // Giảm theo %
            $discountPercentAmount = $subtotal * ($this->discount_percent / 100);

            // Giảm theo số tiền cố định (VND)
            $discountFixedAmount = $this->discount_amount_vnd ?? 0;

            // Tổng giảm = giảm % + giảm số tiền
            $totalDiscount = $discountPercentAmount + $discountFixedAmount;

            $this->total_vnd = max(0, $subtotal - $totalDiscount);
            $this->total_usd = 0; // Item VND không có USD
            $this->price_usd = 0;
        }

        $this->save();
    }

    public function processPaintingStock()
    {
        if ($this->painting_id) {
            $painting = $this->painting;

            if ($painting->isInStock() && $painting->reduceQuantity($this->quantity)) {
                InventoryTransaction::create([
                    'transaction_type' => 'export',
                    'item_type' => 'painting',
                    'item_id' => $this->painting_id,
                    'quantity' => $this->quantity,
                    'reference_type' => 'sale',
                    'reference_id' => $this->sale_id,
                    'transaction_date' => $this->sale->sale_date,
                    'notes' => "Bán trong hóa đơn {$this->sale->invoice_code}",
                    'created_by' => $this->sale->user_id,
                ]);

                return true;
            }

            return false;
        }

        return true;
    }
}
