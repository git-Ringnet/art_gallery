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
        'payment_usd',
        'payment_vnd',
        'payment_exchange_rate',
        'payment_method',
        'transaction_type',
        'payment_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_usd' => 'decimal:2',
            'payment_vnd' => 'decimal:2',
            'payment_exchange_rate' => 'decimal:2',
            'payment_date' => 'datetime',
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

    public static function getTransactionTypes()
    {
        return [
            'sale_payment' => 'Thanh toán bán hàng',
            'return' => 'Trả hàng',
            'exchange' => 'Đổi hàng',
        ];
    }

    public function getTransactionTypeLabel()
    {
        $types = self::getTransactionTypes();
        return $types[$this->transaction_type] ?? 'Thanh toán bán hàng';
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
