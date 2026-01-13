<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    // Tự động set year khi tạo Sale mới
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            if (empty($sale->year)) {
                // Lấy năm từ sale_date nếu có, nếu không thì lấy năm hiện tại
                if ($sale->sale_date) {
                    $sale->year = date('Y', strtotime($sale->sale_date));
                } else {
                    $sale->year = date('Y');
                }
            }

            // Tự động tạo record năm nếu chưa có
            self::ensureYearExists($sale->year);
        });
    }

    /**
     * Đảm bảo năm tồn tại trong bảng year_databases
     */
    public static function ensureYearExists($year)
    {
        if (!$year)
            return;

        $exists = YearDatabase::where('year', $year)->exists();
        if (!$exists) {
            YearDatabase::create([
                'year' => $year,
                'database_name' => config('database.connections.mysql.database'),
                'is_active' => false, // Không phải năm hiện tại
                'is_on_server' => true,
                'description' => "Năm {$year} (tự động tạo)",
            ]);
        }
    }



    // Thêm các accessor vào JSON output
    protected $appends = [
        'paid_usd',
        'paid_vnd',
        'debt_usd',
        'debt_vnd',
    ];

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
        'discount_amount_usd',
        'discount_amount_vnd',
        'discount_usd',
        'discount_vnd',
        'shipping_fee_usd',
        'shipping_fee_vnd',
        'total_usd',
        'total_vnd',
        'original_total_usd',
        'original_total_vnd',
        'paid_amount',
        'payment_usd',
        'payment_vnd',
        'payment_method',
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
            'discount_amount_usd' => 'decimal:2',
            'discount_amount_vnd' => 'decimal:2',
            'discount_usd' => 'decimal:2',
            'discount_vnd' => 'decimal:2',
            'shipping_fee_usd' => 'decimal:2',
            'shipping_fee_vnd' => 'decimal:2',
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

            // Pattern: SHOWROOMCODE + NUMBER + DAY + MONTH + YEAR
            // Example: HN0106012026 (HN + 01 + 06 + 01 + 2026)
            $datePattern = $day . $month . $year;
            $prefix = $showroomCode;

            // Find last invoice with same showroom code and date pattern
            $lastInvoice = self::where('invoice_code', 'like', "{$prefix}%{$datePattern}")
                ->lockForUpdate()
                ->orderBy('invoice_code', 'desc')
                ->first();

            if ($lastInvoice) {
                // Extract number from invoice code (between showroom code and date)
                // Example: HN0106012026 -> extract "01"
                $codeLength = strlen($prefix);
                $numberPart = substr($lastInvoice->invoice_code, $codeLength, 2);
                $lastNumber = (int) $numberPart;
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            // Format: SHOWROOMCODE + 2-digit-number + DDMMYYYY
            return $prefix . str_pad($newNumber, 2, '0', STR_PAD_LEFT) . $datePattern;
        });
    }

    public function calculateTotals()
    {
        // CHỈ tính các sản phẩm chưa bị trả (is_returned = false hoặc null)
        $subtotalUsd = $this->saleItems()->where(function ($q) {
            $q->where('is_returned', false)->orWhereNull('is_returned');
        })->sum('total_usd');

        $subtotalVnd = $this->saleItems()->where(function ($q) {
            $q->where('is_returned', false)->orWhereNull('is_returned');
        })->sum('total_vnd');

        // Giảm theo %
        $discountPercentUsd = $subtotalUsd * ($this->discount_percent / 100);
        $discountPercentVnd = $subtotalVnd * ($this->discount_percent / 100);

        // Giảm theo số tiền cố định
        $discountFixedUsd = $this->discount_amount_usd ?? 0;
        $discountFixedVnd = $this->discount_amount_vnd ?? 0;

        // Tổng số tiền giảm (% + cố định)
        $discountUsd = $discountPercentUsd + $discountFixedUsd;
        $discountVnd = $discountPercentVnd + $discountFixedVnd;

        // Phí vận chuyển
        $shippingFeeUsd = $this->shipping_fee_usd ?? 0;
        $shippingFeeVnd = $this->shipping_fee_vnd ?? 0;

        $totalUsd = max(0, $subtotalUsd - $discountUsd) + $shippingFeeUsd;
        $totalVnd = max(0, $subtotalVnd - $discountVnd) + $shippingFeeVnd;

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
        // Xác định loại tiền tệ chính của hóa đơn
        $hasUsd = $this->total_usd > 0;
        $hasVnd = $this->total_vnd > 0;

        // Tính công nợ dựa trên loại tiền tệ
        if ($hasUsd && !$hasVnd) {
            // Chỉ có USD
            $totalPaid = (float) $this->paid_usd;
            $totalAmount = (float) $this->total_usd;
            $debt = $totalAmount - $totalPaid;

            // Xác định trạng thái
            if ($debt <= 0.01) {
                $this->payment_status = 'paid';
                $this->debt_amount = 0;
            } elseif ($totalPaid > 0.01) {
                $this->payment_status = 'partial';
                $this->debt_amount = $this->exchange_rate > 0 ? (string) round($debt * (float) $this->exchange_rate, 2) : 0;
            } else {
                $this->payment_status = 'unpaid';
                $this->debt_amount = $this->exchange_rate > 0 ? (string) round($totalAmount * (float) $this->exchange_rate, 2) : 0;
            }

            $this->paid_amount = $this->exchange_rate > 0 ? (string) round($totalPaid * (float) $this->exchange_rate, 2) : 0;

        } elseif ($hasVnd && !$hasUsd) {
            // Chỉ có VND
            $totalPaid = (float) $this->paid_vnd;
            $totalAmount = (float) $this->total_vnd;
            $debt = $totalAmount - $totalPaid;

            // Xác định trạng thái
            if ($debt <= 1) { // Tolerance 1 VND
                $this->payment_status = 'paid';
                $this->debt_amount = 0;
            } elseif ($totalPaid > 1) {
                $this->payment_status = 'partial';
                $this->debt_amount = (string) round($debt, 2);
            } else {
                $this->payment_status = 'unpaid';
                $this->debt_amount = (string) round($totalAmount, 2);
            }

            $this->paid_amount = (string) round($totalPaid, 2);

        } else {
            // Có cả USD và VND - Kiểm tra RIÊNG từng loại
            // CHỈ tính debt cho loại tiền có total > 0
            $hasUsdDebt = false;
            $hasVndDebt = false;

            if ($this->total_usd > 0.01) {
                $debtUsd = (float) $this->total_usd - (float) $this->paid_usd;
                if ($debtUsd > 0.01) {
                    $hasUsdDebt = true;
                }
            }

            if ($this->total_vnd > 1) {
                $debtVnd = (float) $this->total_vnd - (float) $this->paid_vnd;
                if ($debtVnd > 1) {
                    $hasVndDebt = true;
                }
            }

            // Xác định trạng thái: Chỉ "paid" khi KHÔNG còn nợ cả USD và VND
            if (!$hasUsdDebt && !$hasVndDebt) {
                $this->payment_status = 'paid';
                $this->debt_amount = 0;
            } elseif ($this->paid_usd > 0.01 || $this->paid_vnd > 1) {
                $this->payment_status = 'partial';
                // debt_amount chỉ để tham khảo, không dùng để tính toán
                $debtVndValue = $this->total_vnd > 0 ? max(0, (float) $this->total_vnd - (float) $this->paid_vnd) : 0;
                $this->debt_amount = (string) round($debtVndValue, 2);
            } else {
                $this->payment_status = 'unpaid';
                $this->debt_amount = (string) round((float) $this->total_vnd, 2);
            }

            // paid_amount chỉ để tham khảo, không dùng để tính toán
            $this->paid_amount = (string) round((float) $this->paid_vnd, 2);
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

    // Accessor để tính paid_usd từ các payments
    public function getPaidUsdAttribute()
    {
        // Xác định loại hóa đơn dựa trên ORIGINAL total (trước khi trả hàng)
        $originalUsd = $this->original_total_usd ?? $this->total_usd;
        $originalVnd = $this->original_total_vnd ?? $this->total_vnd;

        $hasUsdTotal = $originalUsd > 0;
        $hasVndTotal = $originalVnd > 0;

        // Tính tổng USD đã trả từ tất cả payments
        $totalPaidUsd = 0;

        // Nếu có payment records, tính từ payments
        if ($this->payments && $this->payments->count() > 0) {
            foreach ($this->payments as $payment) {
                // Cộng payment_usd
                if ($payment->payment_usd > 0) {
                    $totalPaidUsd += $payment->payment_usd;
                }

                // Quy đổi payment_vnd → USD CHỈ KHI:
                // - Hóa đơn CHỈ có USD (thanh toán chéo VND → USD)
                if ($hasUsdTotal && !$hasVndTotal && $payment->payment_vnd > 0) {
                    $exchangeRate = $payment->payment_exchange_rate ?? $this->exchange_rate;
                    if ($exchangeRate > 0) {
                        $converted = $payment->payment_vnd / $exchangeRate;
                        // Smart rounding: Nếu gần số nguyên (sai số < 0.05) làm tròn
                        if (abs($converted - round($converted)) < 0.05) {
                            $converted = round($converted);
                        }
                        $totalPaidUsd += $converted;
                    }
                }
            }
        } else {
            // Nếu chưa có payment records (phiếu pending), đọc từ field
            if (isset($this->attributes['payment_usd']) && $this->attributes['payment_usd'] > 0) {
                $totalPaidUsd += $this->attributes['payment_usd'];
            }

            // Quy đổi payment_vnd → USD CHỈ KHI hóa đơn CHỈ có USD
            if ($hasUsdTotal && !$hasVndTotal && isset($this->attributes['payment_vnd']) && $this->attributes['payment_vnd'] > 0) {
                if ($this->exchange_rate > 0) {
                    $converted = $this->attributes['payment_vnd'] / $this->exchange_rate;
                    // Smart rounding
                    if (abs($converted - round($converted)) < 0.05) {
                        $converted = round($converted);
                    }
                    $totalPaidUsd += $converted;
                }
            }
        }

        return round($totalPaidUsd, 2);
    }

    // Accessor để tính paid_vnd từ các payments
    public function getPaidVndAttribute()
    {
        // Xác định loại hóa đơn dựa trên ORIGINAL total (trước khi trả hàng)
        $originalUsd = $this->original_total_usd ?? $this->total_usd;
        $originalVnd = $this->original_total_vnd ?? $this->total_vnd;

        $hasUsdTotal = $originalUsd > 0;
        $hasVndTotal = $originalVnd > 0;

        // Tính tổng VND đã trả từ tất cả payments
        $totalPaidVnd = 0;

        // Nếu có payment records, tính từ payments
        if ($this->payments && $this->payments->count() > 0) {
            foreach ($this->payments as $payment) {
                // Cộng payment_vnd
                if ($payment->payment_vnd > 0) {
                    $totalPaidVnd += $payment->payment_vnd;
                }

                // Quy đổi payment_usd → VND CHỈ KHI:
                // - Hóa đơn CHỈ có VND (thanh toán chéo USD → VND)
                if ($hasVndTotal && !$hasUsdTotal && $payment->payment_usd > 0) {
                    $exchangeRate = $payment->payment_exchange_rate ?? $this->exchange_rate;
                    if ($exchangeRate > 0) {
                        $totalPaidVnd += $payment->payment_usd * $exchangeRate;
                    }
                }
            }
        } else {
            // Nếu chưa có payment records (phiếu pending), đọc từ field
            if (isset($this->attributes['payment_vnd']) && $this->attributes['payment_vnd'] > 0) {
                $totalPaidVnd += $this->attributes['payment_vnd'];
            }

            // Quy đổi payment_usd → VND CHỈ KHI hóa đơn CHỈ có VND
            if ($hasVndTotal && !$hasUsdTotal && isset($this->attributes['payment_usd']) && $this->attributes['payment_usd'] > 0) {
                if ($this->exchange_rate > 0) {
                    $totalPaidVnd += $this->attributes['payment_usd'] * $this->exchange_rate;
                }
            }
        }

        return round($totalPaidVnd, 2);
    }

    // Accessor để tính debt_usd (công nợ theo USD)
    public function getDebtUsdAttribute()
    {
        // CHỈ dùng khi tổng hóa đơn có USD
        if ($this->total_usd <= 0) {
            return 0;
        }

        // Công nợ USD = Tổng hóa đơn USD - Tổng đã trả USD
        $debtUsd = $this->total_usd - $this->paid_usd;

        return round(max(0, $debtUsd), 2);
    }

    // Accessor để tính debt_vnd (công nợ theo VND)
    public function getDebtVndAttribute()
    {
        // CHỈ dùng khi tổng hóa đơn có VND
        if ($this->total_vnd <= 0) {
            return 0;
        }

        // Công nợ VND = Tổng hóa đơn VND - Tổng đã trả VND
        $debtVnd = $this->total_vnd - $this->paid_vnd;

        return round(max(0, $debtVnd), 2);
    }
}
