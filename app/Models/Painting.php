<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Painting extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'artist',
        'material',
        'width',
        'height',
        'paint_year',
        'price_usd',
        'price_vnd',
        'image',
        'quantity',
        'import_date',
        'export_date',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'price_usd' => 'decimal:2',
            'price_vnd' => 'decimal:2',
            'quantity' => 'integer',
            'import_date' => 'date',
            'export_date' => 'date',
        ];
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'item_id')
                    ->where('item_type', 'painting');
    }

    public function isInStock()
    {
        return $this->status === 'in_stock' && $this->quantity > 0;
    }

    public function reduceQuantity($amount = 1)
    {
        if ($this->quantity >= $amount) {
            $this->decrement('quantity', $amount);
            
            if ($this->quantity <= 0) {
                $this->update(['status' => 'sold']);
            }
            
            return true;
        }
        
        return false;
    }

    public function increaseQuantity($amount = 1)
    {
        $this->increment('quantity', $amount);
        
        if ($this->status === 'sold') {
            $this->update(['status' => 'in_stock']);
        }
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'in_stock')->where('quantity', '>', 0);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%")
              ->orWhere('artist', 'like', "%{$search}%");
        });
    }
}
