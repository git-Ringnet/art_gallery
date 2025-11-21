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
        
        // Bypass cho admin@example.com
        if (Auth::user()->email === 'admin@example.com') {
            return true;
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
        
        // Bypass cho admin@example.com
        if (Auth::user()->email === 'admin@example.com') {
            return true;
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

    /**
     * Áp dụng phạm vi dữ liệu vào query
     */
    public static function applyDataScope($query, $module, $userIdColumn = 'user_id', $showroomIdColumn = 'showroom_id')
    {
        if (!Auth::check()) {
            return $query->whereRaw('1 = 0'); // Không có quyền
        }

        // Bypass cho admin@example.com - luôn xem tất cả
        if (Auth::user()->email === 'admin@example.com') {
            return $query; // Không filter gì cả
        }

        if (!Auth::user()->role) {
            return $query->whereRaw('1 = 0'); // Không có quyền
        }

        $role = Auth::user()->role;
        $dataScope = $role->getDataScope($module);

        switch ($dataScope) {
            case 'own':
                // Chỉ xem dữ liệu của chính mình
                $query->where($userIdColumn, Auth::id());
                break;

            case 'showroom':
                // Xem theo showroom được phép
                $allowedShowrooms = $role->getAllowedShowrooms($module);
                if ($allowedShowrooms && is_array($allowedShowrooms) && count($allowedShowrooms) > 0) {
                    $query->whereIn($showroomIdColumn, $allowedShowrooms);
                }
                break;

            case 'all':
                // Xem tất cả - không cần filter
                break;

            case 'none':
            default:
                // Không có quyền xem
                $query->whereRaw('1 = 0');
                break;
        }

        return $query;
    }

    /**
     * Kiểm tra có được lọc theo showroom không
     */
    public static function canFilterByShowroom($module)
    {
        if (!Auth::check()) {
            return false;
        }
        
        // Bypass cho admin@example.com
        if (Auth::user()->email === 'admin@example.com') {
            return true;
        }
        
        if (!Auth::user()->role) {
            return false;
        }
        return Auth::user()->role->canFilterByShowroom($module);
    }

    /**
     * Kiểm tra có được lọc theo nhân viên không
     */
    public static function canFilterByUser($module)
    {
        if (!Auth::check()) {
            return false;
        }
        
        // Bypass cho admin@example.com
        if (Auth::user()->email === 'admin@example.com') {
            return true;
        }
        
        if (!Auth::user()->role) {
            return false;
        }
        return Auth::user()->role->canFilterByUser($module);
    }

    /**
     * Kiểm tra có được tìm kiếm không
     */
    public static function canSearch($module)
    {
        if (!Auth::check()) {
            return false;
        }
        
        // Bypass cho admin@example.com
        if (Auth::user()->email === 'admin@example.com') {
            return true;
        }
        
        if (!Auth::user()->role) {
            return false;
        }
        return Auth::user()->role->canSearch($module);
    }

    /**
     * Kiểm tra có được lọc theo ngày không
     */
    public static function canFilterByDate($module)
    {
        if (!Auth::check()) {
            return false;
        }
        
        // Bypass cho admin@example.com
        if (Auth::user()->email === 'admin@example.com') {
            return true;
        }
        
        if (!Auth::user()->role) {
            return false;
        }
        return Auth::user()->role->canFilterByDate($module);
    }

    /**
     * Kiểm tra có được lọc theo trạng thái không
     */
    public static function canFilterByStatus($module)
    {
        if (!Auth::check()) {
            return false;
        }
        
        // Bypass cho admin@example.com
        if (Auth::user()->email === 'admin@example.com') {
            return true;
        }
        
        if (!Auth::user()->role) {
            return false;
        }
        return Auth::user()->role->canFilterByStatus($module);
    }

    /**
     * Lấy danh sách showroom được phép xem
     */
    public static function getAllowedShowrooms($module)
    {
        if (!Auth::check()) {
            return [];
        }
        
        // Bypass cho admin@example.com - xem tất cả showroom
        if (Auth::user()->email === 'admin@example.com') {
            return \App\Models\Showroom::active()->get();
        }
        
        if (!Auth::user()->role) {
            return [];
        }
        
        $role = Auth::user()->role;
        $dataScope = $role->getDataScope($module);
        
        if ($dataScope === 'all') {
            return \App\Models\Showroom::active()->get();
        } elseif ($dataScope === 'showroom') {
            $allowedIds = $role->getAllowedShowrooms($module);
            if ($allowedIds && is_array($allowedIds) && count($allowedIds) > 0) {
                return \App\Models\Showroom::whereIn('id', $allowedIds)->get();
            }
        }
        
        return collect();
    }
}
