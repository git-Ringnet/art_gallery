<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnModel extends Model
{
    protected $table = 'returns';

    protected $fillable = [
        'return_code',
        'type',
        'sale_id',
        'customer_id',
        'return_date',
        'total_refund',
        'exchange_amount',
        'reason',
        'status',
        'processed_by',
        'notes',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_refund' => 'decimal:2',
        'exchange_amount' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }

    public function exchangeItems(): HasMany
    {
        return $this->hasMany(ExchangeItem::class, 'return_id');
    }

    public static function generateReturnCode(): string
    {
        $date = now()->format('Ymd');
        $lastReturn = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastReturn ? (int)substr($lastReturn->return_code, -4) + 1 : 1;
        
        return 'RT' . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
