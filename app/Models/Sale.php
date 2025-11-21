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
        'payment_usd',
        'payment_vnd',
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
        return $this->hasMany(Payment::class)->orderBy('payment_date', 'desc')->orderBy('id', 'desc');
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

    public static function generateInvoiceCode($showroomId = null)
    {
        return \DB::transaction(function () use ($showroomId) {
            // Get showroom code
            $showroomCode = 'HN'; // Default
            if ($showroomId) {
                $showroom = Showroom::find($showroomId);
                if ($showroom && $showroom->code) {
                    $showroomCode = strtoupper($showroom->code);
                }
            }
            
            $year = now()->format('Y');
            $month = now()->format('m');
            $day = now()->format('d');
            
            // Pattern: SHOWROOMCODE + NUMBER + YEAR + MONTH + DAY
            // Example: HN01202510 10 (HN + 01 + 2025 + 10 + 10)
            $datePattern = $year . $month . $day;
            $prefix = $showroomCode;
            
            // Find last invoice with same showroom code and date pattern
            $lastInvoice = self::where('invoice_code', 'like', "{$prefix}%{$datePattern}")
                              ->lockForUpdate()
                              ->orderBy('invoice_code', 'desc')
                              ->first();
            
            if ($lastInvoice) {
                // Extract number from invoice code (between showroom code and date)
                // Example: HN01202510 10 -> extract "01"
                $codeLength = strlen($prefix);
                $numberPart = substr($lastInvoice->invoice_code, $codeLength, 2);
                $lastNumber = (int) $numberPart;
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
            
            // Format: SHOWROOMCODE + 2-digit-number + YYYYMMDD
            return $prefix . str_pad($newNumber, 2, '0', STR_PAD_LEFT) . $datePattern;
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
        // Tính tổng đã trả theo USD (đơn vị chính)
        $totalPaidUsd = $this->paid_usd; // Sử dụng accessor đã có
        
        // Tính công nợ theo USD
        $debtUsd = $this->total_usd - $totalPaidUsd;
        
        // Xác định trạng thái thanh toán dựa trên USD với tolerance 0.01 USD
        // Nếu nợ <= 0.01 (bao gồm cả trả thừa - số âm), coi như đã thanh toán đủ
        if ($debtUsd <= 0.01) {
            $this->payment_status = 'paid';
            $this->debt_amount = 0; // Đảm bảo debt = 0 khi đã thanh toán đủ
        } elseif ($totalPaidUsd > 0) {
            $this->payment_status = 'partial';
            // Tính debt_amount theo VND để hiển thị (chỉ tham khảo)
            $this->debt_amount = max(0, round($debtUsd * $this->exchange_rate, 2));
        } else {
            $this->payment_status = 'unpaid';
            // Tính debt_amount theo VND để hiển thị (chỉ tham khảo)
            $this->debt_amount = round($this->total_usd * $this->exchange_rate, 2);
        }
        
        // Cập nhật paid_amount (VND) để hiển thị - chỉ tham khảo
        $this->paid_amount = round($totalPaidUsd * $this->exchange_rate, 2);
        
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

    // Accessor để tính paid_usd từ các payments (theo tỉ giá tại thời điểm thanh toán)
    public function getPaidUsdAttribute()
    {
        if (!$this->exchange_rate || $this->exchange_rate == 0) {
            return 0;
        }
        
        // Tính tổng USD đã trả từ tất cả payments
        $totalPaidUsd = 0;
        
        // Nếu có payment records, tính từ payments
        if ($this->payments && $this->payments->count() > 0) {
            foreach ($this->payments as $payment) {
                // Nếu payment có payment_usd thì cộng trực tiếp
                if ($payment->payment_usd > 0) {
                    $totalPaidUsd += $payment->payment_usd;
                }
                
                // Nếu payment có payment_vnd thì quy đổi theo tỉ giá tại thời điểm thanh toán
                if ($payment->payment_vnd > 0) {
                    $exchangeRate = $payment->payment_exchange_rate ?? $this->exchange_rate;
                    $totalPaidUsd += $payment->payment_vnd / $exchangeRate;
                }
            }
        } else {
            // Nếu chưa có payment records (phiếu pending), đọc từ field payment_usd/payment_vnd
            if (isset($this->attributes['payment_usd']) && $this->attributes['payment_usd'] > 0) {
                $totalPaidUsd += $this->attributes['payment_usd'];
            }
            
            if (isset($this->attributes['payment_vnd']) && $this->attributes['payment_vnd'] > 0) {
                $totalPaidUsd += $this->attributes['payment_vnd'] / $this->exchange_rate;
            }
        }
        
        return round($totalPaidUsd, 2);
    }

    // Accessor để tính debt_usd (công nợ theo USD)
    public function getDebtUsdAttribute()
    {
        if (!$this->exchange_rate || $this->exchange_rate == 0) {
            return 0;
        }
        
        // Công nợ USD = Tổng hóa đơn USD - Tổng đã trả USD
        $debtUsd = $this->total_usd - $this->paid_usd;
        
        return round(max(0, $debtUsd), 2);
    }
}
