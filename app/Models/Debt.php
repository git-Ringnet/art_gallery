<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Debt extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'customer_id',
        'total_usd',
        'paid_usd',
        'debt_usd',
        'exchange_rate',
        'total_amount',
        'paid_amount',
        'debt_amount',
        'due_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_usd' => 'decimal:2',
            'paid_usd' => 'decimal:2',
            'debt_usd' => 'decimal:2',
            'exchange_rate' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'debt_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function updateDebtAmount()
    {
        // Đồng bộ với Sale - cập nhật cả total_amount
        $this->total_amount = $this->sale->total_vnd;
        $this->paid_amount = $this->sale->paid_amount;
        $this->debt_amount = $this->sale->debt_amount;
        
        // Đồng bộ status với Sale payment_status
        $this->status = $this->sale->payment_status;
        
        $this->save();
    }

    public function isOverdue()
    {
        return $this->due_date && 
               $this->due_date->isPast() && 
               $this->status !== 'paid';
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', '!=', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->where('status', '!=', 'paid');
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($debt) {
            if ($debt->customer) {
                $debt->customer->updateTotals();
            }
        });

        static::deleted(function ($debt) {
            if ($debt->customer) {
                $debt->customer->updateTotals();
            }
        });
    }
}
