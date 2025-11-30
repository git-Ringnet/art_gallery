<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'total_purchased',
        'total_debt',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_purchased' => 'decimal:2',
            'total_debt' => 'decimal:2',
        ];
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function debts()
    {
        return $this->hasMany(Debt::class);
    }

    public function returns()
    {
        return $this->hasMany(ReturnItem::class);
    }

    public function hasDebt()
    {
        return $this->total_debt > 0;
    }

    public function updateTotals()
    {
        $this->update([
            'total_purchased' => $this->sales()->where('sale_status', 'completed')->sum('total_vnd'),
            'total_debt' => $this->debts()->where('status', '!=', 'paid')->sum('debt_amount'),
        ]);
    }

    public function getTotalPurchasedUsdAttribute()
    {
        return $this->sales()->where('sale_status', 'completed')->sum('total_usd');
    }

    public function getTotalPurchasedVndAttribute()
    {
        return $this->sales()->where('sale_status', 'completed')->sum('total_vnd');
    }

    // LOGIC MỚI: Tính công nợ riêng USD và VND
    public function getTotalDebtUsdAttribute()
    {
        // Tính tổng nợ USD từ các phiếu có item USD
        return $this->sales()
            ->where('sale_status', 'completed')
            ->get()
            ->sum(function($sale) {
                return $sale->debt_usd ?? 0;
            });
    }

    public function getTotalDebtVndAttribute()
    {
        // Tính tổng nợ VND từ các phiếu có item VND
        return $this->sales()
            ->where('sale_status', 'completed')
            ->get()
            ->sum(function($sale) {
                return $sale->debt_vnd ?? 0;
            });
    }

    // Kiểm tra có nợ không (USD hoặc VND)
    public function getHasAnyDebtAttribute()
    {
        return $this->total_debt_usd > 0 || $this->total_debt_vnd > 0;
    }

    public function scopeWithDebt($query)
    {
        return $query->where('total_debt', '>', 0);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }
}
