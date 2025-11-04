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
            'frames' => 'Khung tranh',
            'showrooms' => 'Phòng trưng bày',
            'customers' => 'Khách hàng',
            'employees' => 'Nhân viên',
            'permissions' => 'Phân quyền',
            'year_database' => 'Database',
        ];
    }

    public static function getModuleFields($module)
    {
        // Get fields from CustomField model which includes both database and custom fields
        return \App\Models\CustomField::getAllFieldsForModule($module);
    }
}
