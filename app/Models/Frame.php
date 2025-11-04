<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Frame extends Model
{
    protected $fillable = [
        'name',
        'cost_price',
        'notes',
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
}
