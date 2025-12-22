<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'activity_type',
        'module',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'changes',
        'ip_address',
        'user_agent',
        'is_suspicious',
        'is_important',
    ];

    protected $casts = [
        'properties' => 'array',
        'changes' => 'array',
        'is_suspicious' => 'boolean',
        'is_important' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Activity types constants
    const TYPE_LOGIN = 'login';
    const TYPE_LOGOUT = 'logout';
    const TYPE_CREATE = 'create';
    const TYPE_UPDATE = 'update';
    const TYPE_DELETE = 'delete';
    const TYPE_APPROVE = 'approve';
    const TYPE_CANCEL = 'cancel';
    const TYPE_VIEW = 'view';
    const TYPE_EXPORT = 'export';
    const TYPE_IMPORT = 'import';

    // Module constants
    const MODULE_AUTH = 'auth';
    const MODULE_SALES = 'sales';
    const MODULE_CUSTOMERS = 'customers';
    const MODULE_INVENTORY = 'inventory';
    const MODULE_EMPLOYEES = 'employees';
    const MODULE_SHOWROOMS = 'showrooms';
    const MODULE_PAYMENTS = 'payments';
    const MODULE_DEBTS = 'debts';
    const MODULE_DEBT = 'debt';
    const MODULE_RETURNS = 'returns';
    const MODULE_REPORTS = 'reports';
    const MODULE_PERMISSIONS = 'permissions';
    const MODULE_SETTINGS = 'settings';
    const MODULE_FRAMES = 'frames';
    const MODULE_YEAR_DATABASE = 'year_database';

    /**
     * Get the user who performed the activity
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subject model (polymorphic)
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: Filter by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by activity type
     */
    public function scopeByActivityType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Scope: Filter by module
     */
    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeByDateRange($query, $from, $to)
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        return $query;
    }

    /**
     * Scope: Filter by IP address
     */
    public function scopeByIpAddress($query, $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope: Get suspicious activities
     */
    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious', true);
    }

    /**
     * Scope: Get important activities
     */
    public function scopeImportant($query)
    {
        return $query->where('is_important', true);
    }

    /**
     * Get activity type label in Vietnamese
     */
    public function getActivityTypeLabel(): string
    {
        $labels = [
            self::TYPE_LOGIN => 'Đăng nhập',
            self::TYPE_LOGOUT => 'Đăng xuất',
            self::TYPE_CREATE => 'Tạo mới',
            self::TYPE_UPDATE => 'Cập nhật',
            self::TYPE_DELETE => 'Xóa',
            self::TYPE_APPROVE => 'Duyệt',
            self::TYPE_CANCEL => 'Hủy',
            self::TYPE_VIEW => 'Xem',
            self::TYPE_EXPORT => 'Xuất dữ liệu',
            self::TYPE_IMPORT => 'Nhập dữ liệu',
        ];

        return $labels[$this->activity_type] ?? $this->activity_type;
    }

    /**
     * Get module label in Vietnamese
     */
    public function getModuleLabel(): string
    {
        $labels = [
            self::MODULE_AUTH => 'Xác thực',
            self::MODULE_SALES => 'Bán hàng',
            self::MODULE_CUSTOMERS => 'Khách hàng',
            self::MODULE_INVENTORY => 'Kho hàng',
            self::MODULE_EMPLOYEES => 'Nhân viên',
            self::MODULE_SHOWROOMS => 'Showroom',
            self::MODULE_PAYMENTS => 'Thanh toán',
            self::MODULE_DEBTS => 'Công nợ',
            self::MODULE_RETURNS => 'Trả hàng',
            self::MODULE_REPORTS => 'Báo cáo',
            self::MODULE_PERMISSIONS => 'Phân quyền',
            self::MODULE_SETTINGS => 'Cài đặt',
        ];

        return $labels[$this->module] ?? $this->module;
    }

    /**
     * Get all activity types
     */
    public static function getActivityTypes(): array
    {
        return [
            self::TYPE_LOGIN => 'Đăng nhập',
            self::TYPE_LOGOUT => 'Đăng xuất',
            self::TYPE_CREATE => 'Tạo mới',
            self::TYPE_UPDATE => 'Cập nhật',
            self::TYPE_DELETE => 'Xóa',
            self::TYPE_APPROVE => 'Duyệt',
            self::TYPE_CANCEL => 'Hủy',
            self::TYPE_VIEW => 'Xem',
            self::TYPE_EXPORT => 'Xuất dữ liệu',
            self::TYPE_IMPORT => 'Nhập dữ liệu',
        ];
    }

    /**
     * Get all modules
     */
    public static function getModules(): array
    {
        return [
            self::MODULE_AUTH => 'Xác thực',
            self::MODULE_SALES => 'Bán hàng',
            self::MODULE_CUSTOMERS => 'Khách hàng',
            self::MODULE_INVENTORY => 'Kho hàng',
            self::MODULE_EMPLOYEES => 'Nhân viên',
            self::MODULE_SHOWROOMS => 'Showroom',
            self::MODULE_PAYMENTS => 'Thanh toán',
            self::MODULE_DEBTS => 'Công nợ',
            self::MODULE_RETURNS => 'Trả hàng',
            self::MODULE_REPORTS => 'Báo cáo',
            self::MODULE_PERMISSIONS => 'Phân quyền',
            self::MODULE_SETTINGS => 'Cài đặt',
        ];
    }
}

