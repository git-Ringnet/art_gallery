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
        return $this->permissions()->where('module', $module)->exists();
    }

    public function getModulePermissions($module)
    {
        return $this->rolePermissions()
            ->whereHas('permission', function($query) use ($module) {
                $query->where('module', $module);
            })
            ->first();
    }

    public function getFieldPermissions($module)
    {
        return $this->fieldPermissions()
            ->where('module', $module)
            ->get()
            ->keyBy('field_name');
    }
}
