<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'rate',
        'effective_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'effective_date' => 'date',
        ];
    }

    /**
     * Get the user who created the exchange rate.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the current exchange rate.
     */
    public static function getCurrentRate()
    {
        return self::where('effective_date', '<=', now())
                   ->orderBy('effective_date', 'desc')
                   ->first();
    }

    /**
     * Get the exchange rate for a specific date.
     */
    public static function getRateForDate($date)
    {
        return self::where('effective_date', '<=', $date)
                   ->orderBy('effective_date', 'desc')
                   ->first();
    }

    /**
     * Convert USD to VND.
     */
    public static function convertToVnd($usdAmount, $date = null)
    {
        $rate = $date ? self::getRateForDate($date) : self::getCurrentRate();
        
        if ($rate) {
            return $usdAmount * $rate->rate;
        }
        
        return $usdAmount * 25000; // Default fallback rate
    }

    /**
     * Convert VND to USD.
     */
    public static function convertToUsd($vndAmount, $date = null)
    {
        $rate = $date ? self::getRateForDate($date) : self::getCurrentRate();
        
        if ($rate) {
            return $vndAmount / $rate->rate;
        }
        
        return $vndAmount / 25000; // Default fallback rate
    }

    /**
     * Get formatted rate display.
     */
    public function getFormattedRateAttribute()
    {
        return '1 USD = ' . number_format($this->rate, 0) . ' VND';
    }

    /**
     * Scope a query to get rates for a date range.
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('effective_date', [$from, $to]);
    }

    /**
     * Scope a query to get active rates.
     */
    public function scopeActive($query)
    {
        return $query->where('effective_date', '<=', now());
    }

    /**
     * Scope a query to get future rates.
     */
    public function scopeFuture($query)
    {
        return $query->where('effective_date', '>', now());
    }
}
