<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class);
    }

    public function fieldPermissions()
    {
        return $this->hasMany(FieldPermission::class);
    }

    public function hasPermission($module, $action = 'can_view')
    {
        return $this->rolePermissions()
            ->whereHas('permission', function($query) use ($module) {
                $query->where('module', $module);
            })
            ->where($action, true)
            ->exists();
    }

    public function canAccess($module)
    {
        // Kiểm tra xem role có ít nhất 1 quyền can_view cho module này không
        return $this->rolePermissions()
            ->whereHas('permission', function($query) use ($module) {
                $query->where('module', $module);
            })
            ->where('can_view', true)
            ->exists();
    }

    public function getModulePermissions($module)
    {
        return $this->rolePermissions()
            ->whereHas('permission', function($query) use ($module) {
                $query->where('module', $module);
            })
            ->first();
    }

    /**
     * Kiểm tra phạm vi dữ liệu cho module
     */
    public function getDataScope($module)
    {
        $permission = $this->getModulePermissions($module);
        return $permission ? $permission->data_scope : 'none';
    }

    /**
     * Lấy danh sách showroom được phép xem
     */
    public function getAllowedShowrooms($module)
    {
        $permission = $this->getModulePermissions($module);
        return $permission ? $permission->allowed_showrooms : null;
    }

    /**
     * Kiểm tra có được xem dữ liệu của tất cả nhân viên không
     */
    public function canViewAllUsersData($module)
    {
        $permission = $this->getModulePermissions($module);
        return $permission ? $permission->can_view_all_users_data : false;
    }

    /**
     * Kiểm tra có được lọc theo showroom không
     */
    public function canFilterByShowroom($module)
    {
        $permission = $this->getModulePermissions($module);
        return $permission ? $permission->can_filter_by_showroom : false;
    }

    /**
     * Kiểm tra có được lọc theo nhân viên không
     */
    public function canFilterByUser($module)
    {
        $permission = $this->getModulePermissions($module);
        return $permission ? $permission->can_filter_by_user : false;
    }

    /**
     * Kiểm tra có được tìm kiếm không
     */
    public function canSearch($module)
    {
        $permission = $this->getModulePermissions($module);
        return $permission ? $permission->can_search : false;
    }

    /**
     * Kiểm tra có được lọc theo ngày không
     */
    public function canFilterByDate($module)
    {
        $permission = $this->getModulePermissions($module);
        return $permission ? $permission->can_filter_by_date : false;
    }

    /**
     * Kiểm tra có được lọc theo trạng thái không
     */
    public function canFilterByStatus($module)
    {
        $permission = $this->getModulePermissions($module);
        return $permission ? $permission->can_filter_by_status : false;
    }

    public function getFieldPermissions($module)
    {
        return $this->fieldPermissions()
            ->where('module', $module)
            ->get()
            ->keyBy('field_name');
    }
}
