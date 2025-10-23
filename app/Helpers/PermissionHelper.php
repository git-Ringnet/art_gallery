<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class PermissionHelper
{
    /**
     * Check if current user can access a module
     */
    public static function canAccess($module)
    {
        if (!Auth::check()) {
            return false;
        }
        
        return Auth::user()->canAccess($module);
    }

    /**
     * Check if current user has specific permission
     */
    public static function hasPermission($module, $action = 'can_view')
    {
        if (!Auth::check()) {
            return false;
        }
        
        return Auth::user()->hasPermission($module, $action);
    }

    /**
     * Check if field should be hidden
     */
    public static function isFieldHidden($module, $field)
    {
        if (!Auth::check() || !Auth::user()->role) {
            return false;
        }
        
        $fieldPermissions = Auth::user()->role->getFieldPermissions($module);
        $fp = $fieldPermissions->get($field);
        
        return $fp && $fp->is_hidden;
    }

    /**
     * Check if field should be readonly
     */
    public static function isFieldReadonly($module, $field)
    {
        if (!Auth::check() || !Auth::user()->role) {
            return false;
        }
        
        $fieldPermissions = Auth::user()->role->getFieldPermissions($module);
        $fp = $fieldPermissions->get($field);
        
        return $fp && $fp->is_readonly;
    }

    /**
     * Get readonly attribute for input
     */
    public static function getReadonlyAttr($module, $field)
    {
        return self::isFieldReadonly($module, $field) ? 'readonly' : '';
    }

    /**
     * Get disabled attribute for input
     */
    public static function getDisabledAttr($module, $field)
    {
        return self::isFieldReadonly($module, $field) ? 'disabled' : '';
    }
}
