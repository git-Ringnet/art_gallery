# Activity Log System - Hướng dẫn sử dụng

## Tổng quan

Hệ thống Activity Log tự động ghi lại tất cả các hoạt động quan trọng của người dùng trong hệ thống quản lý bán tranh, bao gồm:
- Đăng nhập/đăng xuất
- Tạo/sửa/xóa dữ liệu
- Duyệt/hủy phiếu
- Và các thao tác quan trọng khác

## Tính năng chính

### 1. Ghi log tự động
- Tất cả các hoạt động quan trọng được ghi log tự động
- Lưu trữ thông tin: người dùng, thời gian, IP address, user agent
- Theo dõi thay đổi chi tiết (old/new values) cho các thao tác cập nhật

### 2. Xem nhật ký hoạt động (Admin)
- Truy cập: `/activity-logs`
- Chỉ admin có quyền xem toàn bộ nhật ký
- Lọc theo: người dùng, loại hoạt động, module, ngày tháng, IP address
- Đánh dấu hoạt động đáng ngờ

### 3. Xem lịch sử cá nhân
- Truy cập: `/activity-logs/my-activity`
- Mỗi user có thể xem lịch sử hoạt động của mình
- Lọc theo loại hoạt động và ngày tháng

### 4. Xuất báo cáo
- Xuất Excel: `/activity-logs/export/excel`
- Xuất PDF: `/activity-logs/export/pdf`
- Hỗ trợ xuất với các bộ lọc đã chọn

### 5. Phát hiện hoạt động đáng ngờ
Hệ thống tự động đánh dấu các hoạt động đáng ngờ:
- Nhiều lần đăng nhập thất bại từ cùng IP (>5 lần trong 5 phút)
- Xóa quá nhiều bản ghi (>10 lần trong 10 phút)
- Đăng nhập từ IP mới

### 6. Tự động dọn dẹp
- Chạy hàng ngày lúc 01:00
- Xóa log cũ hơn thời gian lưu trữ (mặc định 365 ngày)
- Giữ lại các log quan trọng và đáng ngờ

## Sử dụng ActivityLogger Service

### Trong Controller

```php
use App\Services\ActivityLogger;

class YourController extends Controller
{
    protected $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    public function store(Request $request)
    {
        // ... create logic ...
        
        $this->activityLogger->logCreate(
            'module_name',
            $model,
            'Mô tả tùy chỉnh (optional)'
        );
    }

    public function update(Request $request, $id)
    {
        $model = Model::findOrFail($id);
        $model->update($validated);
        
        $this->activityLogger->logUpdate(
            'module_name',
            $model,
            [], // Changes will be auto-detected
            'Mô tả tùy chỉnh (optional)'
        );
    }

    public function destroy($id)
    {
        $model = Model::findOrFail($id);
        
        $this->activityLogger->logDelete(
            'module_name',
            $model,
            $model->toArray(), // Store deleted data
            'Mô tả tùy chỉnh (optional)'
        );
        
        $model->delete();
    }

    public function approve($id)
    {
        $model = Model::findOrFail($id);
        $model->update(['status' => 'approved']);
        
        $this->activityLogger->logApprove(
            'module_name',
            $model,
            'Lý do duyệt (optional)',
            'Mô tả tùy chỉnh (optional)'
        );
    }
}
```

### Các phương thức có sẵn

```php
// Login/Logout
$activityLogger->logLogin($user);
$activityLogger->logLogout($user, $sessionDuration);

// CRUD Operations
$activityLogger->logCreate($module, $subject, $description);
$activityLogger->logUpdate($module, $subject, $changes, $description);
$activityLogger->logDelete($module, $subject, $deletedData, $description);

// Approval/Cancellation
$activityLogger->logApprove($module, $subject, $reason, $description);
$activityLogger->logCancel($module, $subject, $reason, $description);

// Generic logging
$activityLogger->log($activityType, $module, $subject, $properties, $description);
```

## Cấu hình

File: `config/activitylog.php`

```php
return [
    // Bật/tắt logging
    'enabled' => env('ACTIVITY_LOG_ENABLED', true),
    
    // Số ngày lưu trữ log
    'retention_days' => env('ACTIVITY_LOG_RETENTION_DAYS', 365),
    
    // Có log các thao tác xem không (tạo nhiều log)
    'log_views' => env('ACTIVITY_LOG_VIEWS', false),
    
    // Ngưỡng phát hiện hoạt động đáng ngờ
    'suspicious_login_attempts' => env('ACTIVITY_LOG_SUSPICIOUS_LOGIN_ATTEMPTS', 5),
    'suspicious_login_window' => env('ACTIVITY_LOG_SUSPICIOUS_LOGIN_WINDOW', 300),
    'suspicious_delete_threshold' => env('ACTIVITY_LOG_SUSPICIOUS_DELETE_THRESHOLD', 10),
];
```

### Biến môi trường (.env)

```env
ACTIVITY_LOG_ENABLED=true
ACTIVITY_LOG_RETENTION_DAYS=365
ACTIVITY_LOG_VIEWS=false
ACTIVITY_LOG_SUSPICIOUS_LOGIN_ATTEMPTS=5
ACTIVITY_LOG_SUSPICIOUS_LOGIN_WINDOW=300
ACTIVITY_LOG_SUSPICIOUS_DELETE_THRESHOLD=10
```

## Lệnh Artisan

### Dọn dẹp log cũ thủ công

```bash
# Sử dụng retention period từ config
php artisan activitylog:cleanup

# Chỉ định số ngày cụ thể
php artisan activitylog:cleanup --days=180
```

### Lên lịch tự động

Lệnh cleanup đã được lên lịch chạy hàng ngày lúc 01:00 trong `routes/console.php`:

```php
Schedule::command('activitylog:cleanup')
    ->daily()
    ->at('01:00')
    ->timezone('Asia/Ho_Chi_Minh');
```

## Database Schema

Bảng `activity_logs`:

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| id | bigint | Primary key |
| user_id | bigint | ID người dùng (nullable) |
| activity_type | varchar(50) | Loại hoạt động |
| module | varchar(50) | Module/phân hệ |
| description | text | Mô tả hoạt động |
| subject_type | varchar(255) | Loại đối tượng (polymorphic) |
| subject_id | bigint | ID đối tượng (polymorphic) |
| properties | json | Dữ liệu bổ sung |
| changes | json | Thay đổi (old/new values) |
| ip_address | varchar(45) | Địa chỉ IP |
| user_agent | text | Thông tin trình duyệt |
| is_suspicious | boolean | Đánh dấu đáng ngờ |
| is_important | boolean | Đánh dấu quan trọng |
| created_at | timestamp | Thời gian tạo |
| updated_at | timestamp | Thời gian cập nhật |

## Loại hoạt động (Activity Types)

- `login` - Đăng nhập
- `logout` - Đăng xuất
- `create` - Tạo mới
- `update` - Cập nhật
- `delete` - Xóa
- `approve` - Duyệt
- `cancel` - Hủy
- `view` - Xem
- `export` - Xuất dữ liệu
- `import` - Nhập dữ liệu

## Modules

- `auth` - Xác thực
- `sales` - Bán hàng
- `customers` - Khách hàng
- `inventory` - Kho hàng
- `employees` - Nhân viên
- `showrooms` - Showroom
- `payments` - Thanh toán
- `debts` - Công nợ
- `returns` - Trả hàng
- `reports` - Báo cáo
- `permissions` - Phân quyền
- `settings` - Cài đặt

## Best Practices

1. **Luôn log các thao tác quan trọng**: Tạo, sửa, xóa, duyệt, hủy
2. **Cung cấp mô tả rõ ràng**: Giúp dễ hiểu khi xem lại log
3. **Không log thông tin nhạy cảm**: Mật khẩu, token, v.v.
4. **Sử dụng module name nhất quán**: Theo danh sách modules đã định nghĩa
5. **Xem xét performance**: Tránh log quá nhiều trong vòng lặp

## Troubleshooting

### Log không được tạo

1. Kiểm tra `ACTIVITY_LOG_ENABLED=true` trong `.env`
2. Kiểm tra database connection
3. Xem Laravel log: `storage/logs/laravel.log`

### Cleanup không chạy

1. Kiểm tra cron job đã được cấu hình: `* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1`
2. Chạy thủ công để test: `php artisan activitylog:cleanup`
3. Kiểm tra log: `storage/logs/laravel.log`

### Performance issues

1. Tăng `retention_days` để giảm số lượng log
2. Tắt `log_views` nếu đang bật
3. Thêm indexes cho các cột thường xuyên filter
4. Xem xét archive log cũ ra file/database riêng

## Hỗ trợ

Nếu có vấn đề hoặc câu hỏi, vui lòng liên hệ team phát triển.
