<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrameItem extends Model
{
    protected $fillable = [
        'frame_id',
        'supply_id',
        'tree_quantity',
        'length_per_tree',
        'total_length',
        'use_whole_trees',
    ];

    protected function casts(): array
    {
        return [
            'tree_quantity' => 'integer',
            'length_per_tree' => 'decimal:2',
            'total_length' => 'decimal:2',
            'use_whole_trees' => 'boolean',
        ];
    }

    public function frame()
    {
        return $this->belongsTo(Frame::class);
    }

    public function supply()
    {
        return $this->belongsTo(Supply::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->total_length = $item->tree_quantity * $item->length_per_tree;
        });
    }
}
