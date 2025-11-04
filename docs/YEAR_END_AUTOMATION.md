# Tự Động Hóa Cuối Năm

## Tổng Quan

Hệ thống có 2 commands chính cho cuối năm:

1. **`year:backup`** - Backup database tự động
2. **`year:cleanup`** - Xóa dữ liệu giao dịch, giữ số đầu kỳ

---

## 1. Backup Tự Động

### Command

```bash
php artisan year:backup
```

**Options:**
```bash
php artisan year:backup --description="Mô tả backup"
```

### Chức Năng

- Export database hiện tại ra file SQL
- Lưu vào `storage/backups/databases/`
- Tạo record trong bảng `database_exports`
- Logging đầy đủ

### Scheduled Tasks

**Đã được schedule tự động:**

1. **Cuối năm** (31/12 lúc 23:00)
   ```php
   Schedule::command('year:backup')
       ->yearlyOn(12, 31, '23:00')
   ```

2. **Hàng tuần** (Chủ nhật lúc 02:00)
   ```php
   Schedule::command('year:backup')
       ->weekly()
       ->sundays()
       ->at('02:00')
   ```

3. **Hàng tháng** (Ngày 1 lúc 01:00)
   ```php
   Schedule::command('year:backup')
       ->monthlyOn(1, '01:00')
   ```

### Kích Hoạt Scheduler

**Cần thêm vào crontab:**

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

**Hoặc trên Windows (Task Scheduler):**

```
Program: C:\xampp\php\php.exe
Arguments: C:\xampp\htdocs\art_gallery\artisan schedule:run
Start in: C:\xampp\htdocs\art_gallery
Trigger: Every 1 minute
```

### Test Scheduler

```bash
# Xem danh sách scheduled tasks
php artisan schedule:list

# Test chạy scheduler
php artisan schedule:run

# Test command trực tiếp
php artisan year:backup --description="Test backup"
```

---

## 2. Cleanup Cuối Năm

### Command

```bash
php artisan year:cleanup --force
```

**⚠️ CẢNH BÁO:** Command này XÓA dữ liệu! Phải có `--force` flag.

### Chức Năng

**Xóa:**
- ❌ Tất cả phiếu bán hàng (`sales`, `sale_items`)
- ❌ Tất cả phiếu đổi trả (`returns`, `return_items`)
- ❌ Tất cả thanh toán (`payments`)
- ❌ Tất cả lịch sử công nợ (`debts`)
- ❌ Tất cả giao dịch kho (`inventory_transactions`)

**Giữ lại:**
- ✅ Danh mục (showrooms, employees, customers)
- ✅ Tồn kho hiện tại (paintings, supplies)
- ✅ Số dư công nợ khách hàng (làm đầu kỳ năm mới)
- ✅ Users, roles, permissions

### Quy Trình Cuối Năm

**Bước 1: Backup (Tự động hoặc thủ công)**

```bash
# Tự động chạy lúc 23:00 ngày 31/12
# Hoặc chạy thủ công:
php artisan year:backup --description="Backup cuối năm 2025"
```

**Bước 2: Verify Backup**

```bash
# Kiểm tra file đã được tạo
ls -lh storage/backups/databases/

# Hoặc vào trang /year để xem
```

**Bước 3: Cleanup (Thủ công)**

```bash
# PHẢI chạy thủ công, không tự động!
php artisan year:cleanup --force
```

**Bước 4: Verify Cleanup**

```bash
# Kiểm tra dữ liệu đã bị xóa
php artisan tinker
>>> DB::table('sales')->count()  // Phải = 0
>>> DB::table('customers')->count()  // Vẫn còn
>>> DB::table('paintings')->count()  // Vẫn còn
```

---

## 3. Quy Trình Chi Tiết

### Scenario: Chuyển Sang Năm 2026

**Ngày 31/12/2025:**

```bash
# 1. Backup tự động lúc 23:00 (hoặc chạy thủ công)
php artisan year:backup --description="Backup cuối năm 2025"

# 2. Tải backup về máy để lưu trữ
# Vào /year → Click "Tải" file backup

# 3. Lưu backup vào nơi an toàn
# - Google Drive
# - External HDD
# - NAS
```

**Ngày 01/01/2026:**

```bash
# 1. Verify backup đã có
ls -lh storage/backups/databases/

# 2. Chạy cleanup (sau khi đã backup!)
php artisan year:cleanup --force

# 3. Verify dữ liệu đã xóa
php artisan tinker
>>> DB::table('sales')->count()  // = 0
>>> DB::table('customers')->count()  // Vẫn còn

# 4. Cập nhật năm trong year_databases
php artisan tinker
>>> $current = App\Models\YearDatabase::where('is_active', true)->first();
>>> $current->update(['is_active' => false, 'archived_at' => now()]);
>>> App\Models\YearDatabase::create([
...     'year' => 2026,
...     'database_name' => 'art_gallery',
...     'is_active' => true,
...     'is_on_server' => true,
...     'description' => 'Database năm 2026'
... ]);

# 5. Bắt đầu làm việc với năm mới!
```

---

## 4. Restore Từ Backup

### Nếu Cần Khôi Phục Dữ Liệu Năm Cũ

**Scenario:** Cần xem lại dữ liệu năm 2025

```bash
# 1. Vào trang /year
# 2. Click "Import Database"
# 3. Chọn file backup năm 2025
# 4. Xác nhận import
# 5. Dữ liệu năm 2025 được restore
```

**Lưu ý:** Import sẽ ghi đè database hiện tại!

---

## 5. Monitoring & Logging

### Check Logs

```bash
# Xem log backup
tail -f storage/logs/laravel.log | grep "Year-end backup"

# Xem log cleanup
tail -f storage/logs/laravel.log | grep "Year-end cleanup"
```

### Check Scheduled Tasks

```bash
# Xem danh sách tasks
php artisan schedule:list

# Output:
# 0 23 31 12 *  year:backup --description="Backup tự động cuối năm"
# 0 2 * * 0     year:backup --description="Backup tự động hàng tuần"
# 0 1 1 * *     year:backup --description="Backup tự động đầu tháng"
```

### Check Last Run

```bash
# Xem lần chạy cuối
php artisan schedule:list --next

# Hoặc check trong database_exports
php artisan tinker
>>> App\Models\DatabaseExport::latest()->first()
```

---

## 6. Best Practices

### 1. Luôn Backup Trước Khi Cleanup

```bash
# ĐÚNG
php artisan year:backup
# Đợi xong
php artisan year:cleanup --force

# SAI
php artisan year:cleanup --force  # Chưa backup!
```

### 2. Verify Backup Trước Khi Cleanup

```bash
# Kiểm tra file backup
ls -lh storage/backups/databases/art_gallery_2025_*.sql

# Kiểm tra kích thước (phải > 0)
# Kiểm tra ngày tạo (phải là hôm nay)
```

### 3. Test Restore Trước

```bash
# Test trên môi trường dev trước
# Import backup vào database test
# Verify dữ liệu đầy đủ
```

### 4. Lưu Trữ Backup Ngoài Server

```bash
# Tải về máy
# Upload lên cloud
# Lưu vào external storage
```

### 5. Document Process

```
Tạo checklist cuối năm:
- [ ] Backup database
- [ ] Verify backup file
- [ ] Tải backup về máy
- [ ] Upload backup lên cloud
- [ ] Chạy cleanup
- [ ] Verify dữ liệu đã xóa
- [ ] Cập nhật year_databases
- [ ] Test hệ thống với năm mới
```

---

## 7. Troubleshooting

### Backup Fail?

**Check:**
1. Mysqldump có sẵn không?
   ```bash
   mysqldump --version
   ```

2. Quyền ghi vào storage?
   ```bash
   ls -la storage/backups/databases/
   ```

3. Disk space đủ không?
   ```bash
   df -h
   ```

### Cleanup Fail?

**Check:**
1. Database connection OK?
   ```bash
   php artisan tinker
   >>> DB::connection()->getPdo()
   ```

2. Foreign key constraints?
   ```sql
   SET FOREIGN_KEY_CHECKS=0;
   -- Run cleanup
   SET FOREIGN_KEY_CHECKS=1;
   ```

### Scheduler Không Chạy?

**Check:**
1. Crontab đã setup?
   ```bash
   crontab -l
   ```

2. Scheduler có hoạt động?
   ```bash
   php artisan schedule:run
   ```

3. Log có lỗi không?
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## 8. Files

### Commands
- `app/Console/Commands/YearEndBackup.php` - Backup command
- `app/Console/Commands/YearEndCleanup.php` - Cleanup command

### Schedules
- `routes/console.php` - Scheduled tasks

### Logs
- `storage/logs/laravel.log` - Application logs

### Backups
- `storage/backups/databases/` - Backup files

---

## 9. Crontab Setup

### Linux/Mac

```bash
# Edit crontab
crontab -e

# Thêm dòng này
* * * * * cd /var/www/art_gallery && php artisan schedule:run >> /dev/null 2>&1
```

### Windows Task Scheduler

**Tạo task mới:**
1. Mở Task Scheduler
2. Create Basic Task
3. Name: "Laravel Scheduler"
4. Trigger: Daily, repeat every 1 minute
5. Action: Start a program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\art_gallery\artisan schedule:run`
   - Start in: `C:\xampp\htdocs\art_gallery`

---

## 10. Testing

### Test Backup Command

```bash
php artisan year:backup --description="Test backup"

# Check output
# Check file created
ls -lh storage/backups/databases/

# Check database record
php artisan tinker
>>> App\Models\DatabaseExport::latest()->first()
```

### Test Cleanup Command

```bash
# Tạo dữ liệu test trước
php artisan tinker
>>> App\Models\Sale::factory()->create()

# Chạy cleanup
php artisan year:cleanup --force

# Verify đã xóa
php artisan tinker
>>> App\Models\Sale::count()  // = 0
```

### Test Scheduler

```bash
# Xem danh sách
php artisan schedule:list

# Chạy thử
php artisan schedule:run

# Check log
tail -f storage/logs/laravel.log
```

---

## Kết Luận

**Đã có:**
- ✅ Command backup tự động
- ✅ Command cleanup cuối năm
- ✅ Scheduled tasks (cuối năm, hàng tuần, hàng tháng)
- ✅ Logging đầy đủ
- ✅ Error handling

**Cần làm:**
- ⏳ Setup crontab/Task Scheduler
- ⏳ Test commands
- ⏳ Document quy trình cho team

**Lưu ý:**
- ⚠️ Cleanup KHÔNG tự động, phải chạy thủ công
- ⚠️ Luôn backup trước khi cleanup
- ⚠️ Verify backup trước khi xóa dữ liệu

Hệ thống đã sẵn sàng cho cuối năm! 🎉
