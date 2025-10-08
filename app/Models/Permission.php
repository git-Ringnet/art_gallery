<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'module',
        'name',
        'description',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class);
    }

    public function scopeForModule($query, $module)
    {
        return $query->where('module', $module);
    }

    public static function getModules()
    {
        return [
            'dashboard' => 'Báo cáo thống kê',
            'sales' => 'Bán hàng',
            'debt' => 'Lịch sử công nợ',
            'returns' => 'Đổi/Trả hàng',
            'inventory' => 'Quản lý kho',
            'showrooms' => 'Phòng trưng bày',
            'permissions' => 'Phân quyền',
        ];
    }
}
