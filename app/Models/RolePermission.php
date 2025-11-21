<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id',
        'permission_id',
        'can_view',
        'can_create',
        'can_edit',
        'can_delete',
        'can_export',
        'can_import',
        'can_print',
        'can_approve',
        'can_cancel',
        'data_scope',
        'allowed_showrooms',
        'can_view_all_users_data',
        'can_filter_by_showroom',
        'can_filter_by_user',
        'can_filter_by_date',
        'can_filter_by_status',
        'can_search',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_create' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
        'can_export' => 'boolean',
        'can_import' => 'boolean',
        'can_print' => 'boolean',
        'can_approve' => 'boolean',
        'can_cancel' => 'boolean',
        'allowed_showrooms' => 'array',
        'can_view_all_users_data' => 'boolean',
        'can_filter_by_showroom' => 'boolean',
        'can_filter_by_user' => 'boolean',
        'can_filter_by_date' => 'boolean',
        'can_filter_by_status' => 'boolean',
        'can_search' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
