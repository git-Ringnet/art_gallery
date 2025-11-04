<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Frame extends Model
{
    protected $fillable = [
        'name',
        'cost_price',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
        ];
    }

    public function items()
    {
        return $this->hasMany(FrameItem::class);
    }

    public function getTotalLengthAttribute()
    {
        return $this->items->sum('total_length');
    }

    public function getTotalTreesAttribute()
    {
        return $this->items->sum('tree_quantity');
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function markAsSold()
    {
        $this->update(['status' => 'sold']);
    }

    public function markAsAvailable()
    {
        $this->update(['status' => 'available']);
    }

    public function isAvailable()
    {
        return $this->status === 'available';
    }

    public function isSold()
    {
        return $this->status === 'sold';
    }
}
