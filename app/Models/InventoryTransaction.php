<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_type',
        'item_type',
        'item_id',
        'quantity',
        'reference_type',
        'reference_id',
        'transaction_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    /**
     * Get the user who created the transaction.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the item (painting or supply) polymorphically.
     */
    public function item()
    {
        if ($this->item_type === 'painting') {
            return $this->belongsTo(Painting::class, 'item_id');
        } elseif ($this->item_type === 'supply') {
            return $this->belongsTo(Supply::class, 'item_id');
        }
        
        return null;
    }

    /**
     * Get the reference (sale, return, etc.) polymorphically.
     */
    public function reference()
    {
        if ($this->reference_type === 'sale') {
            return $this->belongsTo(Sale::class, 'reference_id');
        } elseif ($this->reference_type === 'return') {
            return $this->belongsTo(ReturnItem::class, 'reference_id');
        }
        
        return null;
    }

    /**
     * Get transaction type label.
     */
    public function getTransactionTypeLabel()
    {
        $types = [
            'import' => 'Nhập kho',
            'export' => 'Xuất kho',
            'adjustment' => 'Điều chỉnh',
        ];

        return $types[$this->transaction_type] ?? $this->transaction_type;
    }

    /**
     * Get item type label.
     */
    public function getItemTypeLabel()
    {
        $types = [
            'painting' => 'Tranh',
            'supply' => 'Vật tư',
        ];

        return $types[$this->item_type] ?? $this->item_type;
    }

    /**
     * Get item name.
     */
    public function getItemNameAttribute()
    {
        $item = $this->item();
        
        if ($item) {
            if ($this->item_type === 'painting') {
                $painting = Painting::find($this->item_id);
                return $painting ? $painting->name : 'N/A';
            } elseif ($this->item_type === 'supply') {
                $supply = Supply::find($this->item_id);
                return $supply ? $supply->name : 'N/A';
            }
        }
        
        return 'N/A';
    }

    /**
     * Scope a query to filter by transaction type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope a query to filter by item type.
     */
    public function scopeByItemType($query, $itemType)
    {
        return $query->where('item_type', $itemType);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }

    /**
     * Scope a query to only include imports.
     */
    public function scopeImports($query)
    {
        return $query->where('transaction_type', 'import');
    }

    /**
     * Scope a query to only include exports.
     */
    public function scopeExports($query)
    {
        return $query->where('transaction_type', 'export');
    }
}
