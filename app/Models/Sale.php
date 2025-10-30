<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'invoice_code',
        'customer_id',
        'showroom_id',
        'user_id',
        'sale_date',
        'exchange_rate',
        'subtotal_usd',
        'subtotal_vnd',
        'discount_percent',
        'discount_usd',
        'discount_vnd',
        'total_usd',
        'total_vnd',
        'original_total_usd',
        'original_total_vnd',
        'paid_amount',
        'debt_amount',
        'payment_status',
        'sale_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'exchange_rate' => 'decimal:2',
            'subtotal_usd' => 'decimal:2',
            'subtotal_vnd' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_usd' => 'decimal:2',
            'discount_vnd' => 'decimal:2',
            'total_usd' => 'decimal:2',
            'total_vnd' => 'decimal:2',
            'original_total_usd' => 'decimal:2',
            'original_total_vnd' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'debt_amount' => 'decimal:2',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function showroom()
    {
        return $this->belongsTo(Showroom::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function debt()
    {
        return $this->hasOne(Debt::class);
    }

    public function returns()
    {
        return $this->hasMany(ReturnModel::class);
    }

    public function returnItems()
    {
        return $this->hasManyThrough(ReturnItem::class, ReturnModel::class);
    }

    public static function generateInvoiceCode()
    {
        return \DB::transaction(function () {
            $prefix = 'HD';
            $year = now()->format('y');
            $month = now()->format('m');
            
            $lastInvoice = self::where('invoice_code', 'like', "{$prefix}{$year}{$month}%")
                              ->lockForUpdate()
                              ->orderBy('invoice_code', 'desc')
                              ->first();
            
            if ($lastInvoice) {
                $lastNumber = (int) substr($lastInvoice->invoice_code, -4);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
            
            return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        });
    }

    public function calculateTotals()
    {
        // CHỈ tính các sản phẩm chưa bị trả (is_returned = false hoặc null)
        $subtotalUsd = $this->saleItems()->where(function($q) {
            $q->where('is_returned', false)->orWhereNull('is_returned');
        })->sum('total_usd');
        
        $subtotalVnd = $this->saleItems()->where(function($q) {
            $q->where('is_returned', false)->orWhereNull('is_returned');
        })->sum('total_vnd');
        
        $discountUsd = $subtotalUsd * ($this->discount_percent / 100);
        $discountVnd = $subtotalVnd * ($this->discount_percent / 100);
        
        $totalUsd = $subtotalUsd - $discountUsd;
        $totalVnd = $subtotalVnd - $discountVnd;
        
        $this->update([
            'subtotal_usd' => $subtotalUsd,
            'subtotal_vnd' => $subtotalVnd,
            'discount_usd' => $discountUsd,
            'discount_vnd' => $discountVnd,
            'total_usd' => $totalUsd,
            'total_vnd' => $totalVnd,
            'debt_amount' => $totalVnd - $this->paid_amount,
        ]);
        
        $this->updatePaymentStatus();
    }

    public function updatePaymentStatus()
    {
        // CHỈ cập nhật paid_amount từ payments nếu phiếu đã duyệt
        if ($this->sale_status === 'completed' || $this->sale_status === 'cancelled') {
            $paidAmount = $this->payments()->sum('amount');
            $this->paid_amount = $paidAmount;
        } else {
            // Phiếu pending: giữ nguyên paid_amount đã nhập
            $paidAmount = $this->paid_amount;
        }
        
        $this->debt_amount = $this->total_vnd - $paidAmount;
        
        if ($paidAmount >= $this->total_vnd) {
            $this->payment_status = 'paid';
        } elseif ($paidAmount > 0) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'unpaid';
        }
        
        $this->save();
        
        // Đồng bộ với Debt nếu có
        if ($this->debt) {
            $this->debt->updateDebtAmount();
        }
    }

    public function scopeWithDebt($query)
    {
        return $query->where('debt_amount', '>', 0);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('invoice_code', 'like', "%{$search}%")
              ->orWhereHas('customer', function ($customerQuery) use ($search) {
                  $customerQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('phone', 'like', "%{$search}%");
              });
        });
    }

    public function scopePending($query)
    {
        return $query->where('sale_status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('sale_status', 'completed');
    }

    public function isPending()
    {
        return $this->sale_status === 'pending';
    }

    public function isCompleted()
    {
        return $this->sale_status === 'completed';
    }

    public function isCancelled()
    {
        return $this->sale_status === 'cancelled';
    }

    public function canEdit()
    {
        // Cho phép edit khi chưa hủy
        return $this->sale_status !== 'cancelled';
    }

    public function canApprove()
    {
        return $this->sale_status === 'pending';
    }
}
