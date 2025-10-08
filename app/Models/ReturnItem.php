<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    use HasFactory;

    protected $table = 'returns';

    protected $fillable = [
        'sale_id',
        'customer_id',
        'return_date',
        'reason',
        'refund_amount',
        'status',
        'processed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'refund_amount' => 'decimal:2',
        ];
    }

    /**
     * Get the sale that owns the return.
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the customer that owns the return.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user who processed the return.
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get status label.
     */
    public function getStatusLabel()
    {
        $statuses = [
            'pending' => 'Chờ xử lý',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            'completed' => 'Hoàn tất',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Get status color.
     */
    public function getStatusColor()
    {
        $colors = [
            'pending' => 'warning',
            'approved' => 'info',
            'rejected' => 'danger',
            'completed' => 'success',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    /**
     * Approve the return.
     */
    public function approve($userId)
    {
        $this->update([
            'status' => 'approved',
            'processed_by' => $userId,
        ]);
    }

    /**
     * Reject the return.
     */
    public function reject($userId, $reason = null)
    {
        $this->update([
            'status' => 'rejected',
            'processed_by' => $userId,
            'notes' => $reason ? $this->notes . "\nLý do từ chối: " . $reason : $this->notes,
        ]);
    }

    /**
     * Complete the return and process refund.
     */
    public function complete($userId)
    {
        $this->update([
            'status' => 'completed',
            'processed_by' => $userId,
        ]);

        // Update sale totals if needed
        if ($this->sale) {
            $this->sale->calculateTotals();
        }
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include pending returns.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('return_date', [$from, $to]);
    }
}
