<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessedItem extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'code',
        'name',
        'quantity',
        'unit',
        'price_vnd',
        'price_usd',
        'notes',
    ];

    /**
     * Tăng số lượng (khi hoàn trả hoặc nhập kho)
     */
    public function increaseQuantity($amount)
    {
        $this->quantity += (float) $amount;
        return $this->save();
    }

    /**
     * Trừ số lượng (khi bán/xuất kho)
     */
    public function reduceQuantity($amount)
    {
        // Cho phép tồn kho âm vì đây là hàng gia công (hàng order)
        $this->quantity -= (float) $amount;
        return $this->save();
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'processed_item_id');
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'item_id')
            ->where('item_type', 'processed_item');
    }

    public function latestSale()
    {
        return $this->hasOne(SaleItem::class, 'processed_item_id')
            ->latestOfMany();
    }
}
