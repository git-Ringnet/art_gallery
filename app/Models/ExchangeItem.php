<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeItem extends Model
{
    protected $fillable = [
        'return_id',
        'item_type',
        'item_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function return(): BelongsTo
    {
        return $this->belongsTo(ReturnModel::class, 'return_id');
    }

    public function painting(): BelongsTo
    {
        return $this->belongsTo(Painting::class, 'item_id');
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class, 'item_id');
    }
}
