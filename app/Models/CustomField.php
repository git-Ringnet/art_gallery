<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class CustomField extends Model
{
    use HasFactory;

    protected $fillable = [
        'module',
        'field_name',
        'field_label',
        'field_type',
        'field_options',
        'is_required',
        'display_order',
        'display_section',
        'section_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get all fields for a module (database + custom)
     */
    public static function getAllFieldsForModule($module)
    {
        $fields = [];

        // Get database fields
        $databaseFields = self::getDatabaseFields($module);
        foreach ($databaseFields as $key => $label) {
            $fields[$key] = [
                'label' => $label,
                'type' => 'database',
                'field_type' => 'text',
            ];
        }

        // Get custom fields
        $customFields = self::where('module', $module)->orderBy('display_order')->get();
        foreach ($customFields as $field) {
            $fields[$field->field_name] = [
                'id' => $field->id,
                'label' => $field->field_label,
                'type' => 'custom',
                'field_type' => $field->field_type,
                'is_required' => $field->is_required,
                'field_options' => $field->field_options,
                'display_section' => $field->display_section,
                'section_order' => $field->section_order,
            ];
        }

        return $fields;
    }

    /**
     * Get database fields for a module
     */
    public static function getDatabaseFields($module)
    {
        $tableMap = [
            'sales' => 'sales',
            'inventory' => ['paintings', 'supplies'],
            'customers' => 'customers',
            'employees' => 'users',
            'showrooms' => 'showrooms',
            'debt' => 'debts',
            'returns' => 'returns',
        ];

        if (!isset($tableMap[$module])) {
            return [];
        }

        $tables = is_array($tableMap[$module]) ? $tableMap[$module] : [$tableMap[$module]];
        $fields = [];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $columns = Schema::getColumnListing($table);
            $excludeColumns = ['id', 'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token', 'email_verified_at'];

            foreach ($columns as $column) {
                if (in_array($column, $excludeColumns)) {
                    continue;
                }

                // Generate label from column name
                $label = self::generateLabel($column);
                $fields[$column] = $label;
            }
        }

        return $fields;
    }

    /**
     * Generate human-readable label from column name
     */
    private static function generateLabel($columnName)
    {
        $labels = [
            // Sales
            'invoice_code' => 'Mã hóa đơn',
            'customer_id' => 'Khách hàng',
            'showroom_id' => 'Phòng trưng bày',
            'user_id' => 'Nhân viên',
            'sale_date' => 'Ngày bán',
            'exchange_rate' => 'Tỷ giá',
            'subtotal_usd' => 'Tạm tính (USD)',
            'subtotal_vnd' => 'Tạm tính (VND)',
            'discount_percent' => 'Giảm giá (%)',
            'discount_usd' => 'Giảm giá (USD)',
            'discount_vnd' => 'Giảm giá (VND)',
            'total_usd' => 'Tổng tiền (USD)',
            'total_vnd' => 'Tổng tiền (VND)',
            'paid_amount' => 'Đã thanh toán',
            'debt_amount' => 'Còn nợ',
            'payment_status' => 'Trạng thái thanh toán',
            'notes' => 'Ghi chú',

            // Paintings
            'code' => 'Mã',
            'name' => 'Tên',
            'artist' => 'Họa sĩ',
            'material' => 'Chất liệu',
            'width' => 'Chiều rộng',
            'height' => 'Chiều cao',
            'paint_year' => 'Năm sản xuất',
            'price_usd' => 'Giá (USD)',
            'price_vnd' => 'Giá (VND)',
            'image' => 'Hình ảnh',
            'quantity' => 'Số lượng',
            'import_date' => 'Ngày nhập',
            'export_date' => 'Ngày xuất',
            'status' => 'Trạng thái',

            // Supplies
            'type' => 'Loại',
            'unit' => 'Đơn vị',
            'min_quantity' => 'Số lượng tối thiểu',

            // Customers
            'phone' => 'Số điện thoại',
            'email' => 'Email',
            'address' => 'Địa chỉ',
            'total_purchased' => 'Tổng đã mua',
            'total_debt' => 'Tổng công nợ',

            // Users/Employees
            'avatar' => 'Ảnh đại diện',
            'is_active' => 'Trạng thái',
            'role_id' => 'Vai trò',
            'last_login_at' => 'Đăng nhập lần cuối',
        ];

        return $labels[$columnName] ?? ucfirst(str_replace('_', ' ', $columnName));
    }

    /**
     * Get available display sections for a module
     */
    public static function getDisplaySections($module)
    {
        $sections = [
            'sales' => [
                'header' => 'Thông tin hóa đơn (Header)',
                'customer_info' => 'Thông tin khách hàng',
                'items' => 'Danh sách sản phẩm',
                'totals' => 'Tính toán & Thanh toán',
                'notes' => 'Ghi chú',
                'custom' => 'Phần tùy chỉnh (cuối trang)',
            ],
            'inventory' => [
                'header' => 'Thông tin chung',
                'details' => 'Chi tiết sản phẩm',
                'pricing' => 'Giá cả',
                'stock' => 'Tồn kho',
                'custom' => 'Phần tùy chỉnh',
            ],
            'customers' => [
                'basic' => 'Thông tin cơ bản',
                'contact' => 'Thông tin liên hệ',
                'address' => 'Địa chỉ',
                'custom' => 'Phần tùy chỉnh',
            ],
            'employees' => [
                'basic' => 'Thông tin cơ bản',
                'contact' => 'Thông tin liên hệ',
                'work' => 'Thông tin công việc',
                'custom' => 'Phần tùy chỉnh',
            ],
        ];

        return $sections[$module] ?? ['custom' => 'Phần tùy chỉnh'];
    }

    /**
     * Get fields grouped by section
     */
    public static function getFieldsBySection($module)
    {
        $allFields = self::getAllFieldsForModule($module);
        $grouped = [];

        foreach ($allFields as $fieldKey => $fieldData) {
            $section = $fieldData['display_section'] ?? 'custom';
            if (!isset($grouped[$section])) {
                $grouped[$section] = [];
            }
            $grouped[$section][$fieldKey] = $fieldData;
        }

        // Sort fields within each section by section_order
        foreach ($grouped as $section => $fields) {
            uasort($grouped[$section], function ($a, $b) {
                return ($a['section_order'] ?? 0) - ($b['section_order'] ?? 0);
            });
        }

        return $grouped;
    }
}
