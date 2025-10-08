<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'amount',
        'payment_method',
        'payment_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function getPaymentMethods()
    {
        return [
            'cash' => 'Tiền mặt',
            'bank_transfer' => 'Chuyển khoản',
            'card' => 'Thẻ',
            'other' => 'Khác',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($payment) {
            $payment->sale->updatePaymentStatus();
        });

        static::updated(function ($payment) {
            $payment->sale->updatePaymentStatus();
        });

        static::deleted(function ($payment) {
            $payment->sale->updatePaymentStatus();
        });
    }
}
