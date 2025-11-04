<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatabaseExport extends Model
{
    protected $fillable = [
        'year',
        'filename',
        'file_path',
        'file_size',
        'status',
        'description',
        'exported_by',
        'exported_at',
        'is_encrypted',
    ];

    protected $casts = [
        'exported_at' => 'datetime',
        'file_size' => 'integer',
        'is_encrypted' => 'boolean',
    ];

    /**
     * Lấy danh sách export theo năm
     */
    public static function getByYear($year)
    {
        return self::where('year', $year)
            ->where('status', 'completed')
            ->orderBy('exported_at', 'desc')
            ->get();
    }

    /**
     * Kiểm tra file có tồn tại không
     */
    public function fileExists()
    {
        return file_exists(storage_path($this->file_path));
    }

    /**
     * Lấy kích thước file đã format
     */
    public function getFileSizeFormattedAttribute()
    {
        $bytes = $this->file_size;
        
        if ($bytes === 0) {
            return '0 Bytes';
        }
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }

    /**
     * Relationship với User
     */
    public function exportedBy()
    {
        return $this->belongsTo(User::class, 'exported_by');
    }
}
