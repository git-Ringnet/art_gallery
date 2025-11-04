<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supply extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'unit',
        'quantity',
        'tree_count',
        'min_quantity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'tree_count' => 'integer',
            'min_quantity' => 'decimal:2',
        ];
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function frameItems()
    {
        return $this->hasMany(FrameItem::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'item_id')
                    ->where('item_type', 'supply');
    }

    public function isLowStock()
    {
        return $this->quantity <= $this->min_quantity;
    }

    public function reduceQuantity($amount)
    {
        if ($this->quantity >= $amount) {
            $this->decrement('quantity', $amount);
            return true;
        }
        
        return false;
    }

    public function increaseQuantity($amount)
    {
        $this->increment('quantity', $amount);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('quantity <= min_quantity');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%");
        });
    }

    public static function getTypes()
    {
        return [
            'frame' => 'Khung tranh',
            'canvas' => 'Canvas',
            'other' => 'Khác',
        ];
    }
}
