<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YearDatabase extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'database_name',
        'is_active',
        'is_on_server',
        'description',
        'backup_location',
        'archived_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_on_server' => 'boolean',
        'archived_at' => 'datetime',
    ];

    /**
     * Get năm hiện tại (active)
     */
    public static function getCurrentYear()
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Get tất cả năm có sẵn trên server
     */
    public static function getAvailableYears()
    {
        return self::where('is_on_server', true)
            ->orderBy('year', 'desc')
            ->get();
    }

    /**
     * Get tất cả năm (bao gồm offline)
     */
    public static function getAllYears()
    {
        return self::orderBy('year', 'desc')->get();
    }

    /**
     * Kiểm tra năm có trên server không
     */
    public function isAvailable()
    {
        return $this->is_on_server;
    }

    /**
     * Kiểm tra có phải năm hiện tại không
     */
    public function isCurrent()
    {
        return $this->is_active;
    }
}
