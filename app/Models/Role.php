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

    public function hasPermission($module)
    {
        return $this->permissions()->where('module', $module)->exists();
    }

    public function assignPermission($permissionId)
    {
        return $this->permissions()->attach($permissionId);
    }

    public function removePermission($permissionId)
    {
        return $this->permissions()->detach($permissionId);
    }

    public function syncPermissions($permissionIds)
    {
        return $this->permissions()->sync($permissionIds);
    }
}
