<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ReturnItem extends Model
{
    protected $fillable = [
        'return_id',
        'sale_item_id',
        'item_type',
        'item_id',
        'quantity',
        'supply_id',
        'supply_length',
        'unit_price',
        'unit_price_usd',
        'subtotal',
        'subtotal_usd',
        'currency',
        'reason',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'supply_length' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'unit_price_usd' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'subtotal_usd' => 'decimal:2',
    ];

    public function return(): BelongsTo
    {
        return $this->belongsTo(ReturnModel::class, 'return_id');
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function item(): MorphTo
    {
        return $this->morphTo('item', 'item_type', 'item_id');
    }

    public function painting(): BelongsTo
    {
        return $this->belongsTo(Painting::class, 'item_id');
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class, 'item_id');
    }

    public function frameSupply(): BelongsTo
    {
        return $this->belongsTo(Supply::class, 'supply_id');
    }
}
