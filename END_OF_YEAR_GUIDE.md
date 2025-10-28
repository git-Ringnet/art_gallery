# HƯỚNG DẪN QUY TRÌNH CUỐI NĂM

## 📅 THỜI ĐIỂM: 31/12/2025

### 🎯 MỤC TIÊU
- Export dữ liệu năm 2025 sang database riêng
- Dọn dẹp database chính
- Chuẩn bị database cho năm 2026

---

## 📋 QUY TRÌNH CHI TIẾT

### Bước 1: Backup toàn bộ database hiện tại (BẮT BUỘC)

```bash
php artisan year:backup 2025
```

**Kết quả:**
- File backup: `storage/app/backups/backup_art_gallery_2025_YYYY-MM-DD_HHMMSS.sql`
- Kích thước: ~XXX MB

**⚠️ QUAN TRỌNG:**
- Copy file backup ra NAS/Cloud ngay lập tức
- Kiểm tra file backup có mở được không
- Không tiếp tục nếu backup thất bại

---

### Bước 2: Export dữ liệu năm 2025

```bash
php artisan year:create-archive 2025
```

**Lệnh này sẽ:**
1. Tạo database mới: `art_gallery_2025`
2. Copy cấu trúc tất cả bảng
3. Export dữ liệu năm 2025:
   - Sales + Sale Items
   - Debts
   - Returns + Return Items + Exchange Items
   - Payments
   - Inventory Transactions
4. Copy master data: Customers, Showrooms
5. Tạo snapshot tồn kho cuối năm 2025
6. Cập nhật bảng `year_databases`

**Thời gian ước tính:** 5-15 phút (tùy dung lượng dữ liệu)

**Kiểm tra:**
```sql
-- Kiểm tra database mới đã được tạo
SHOW DATABASES LIKE 'art_gallery_2025';

-- Kiểm tra số lượng records
USE art_gallery_2025;
SELECT COUNT(*) FROM sales;
SELECT COUNT(*) FROM debts;
SELECT COUNT(*) FROM returns;
```

---

### Bước 3: Backup database năm 2025 vừa tạo

```bash
php artisan year:backup 2025
```

**⚠️ QUAN TRỌNG:**
- Backup database `art_gallery_2025` để đảm bảo an toàn
- Copy file backup ra NAS/Cloud

---

### Bước 4: Dọn dẹp database chính

```bash
php artisan year:cleanup 2025
```

**Lệnh này sẽ:**
1. Xác nhận bạn đã backup chưa
2. Xóa dữ liệu năm 2025 khỏi database chính:
   - Sale Items → Sales
   - Return Items + Exchange Items → Returns
   - Payments
   - Debts
   - Inventory Transactions

**⚠️ CẢNH BÁO:**
- Lệnh này XÓA dữ liệu vĩnh viễn khỏi database chính
- Dữ liệu vẫn còn trong `art_gallery_2025`
- Không thể hoàn tác!

**Kiểm tra sau khi cleanup:**
```sql
-- Kiểm tra database chính đã sạch
USE art_gallery;
SELECT COUNT(*) FROM sales WHERE year = 2025;  -- Phải = 0
SELECT COUNT(*) FROM debts WHERE year = 2025;  -- Phải = 0
SELECT COUNT(*) FROM returns WHERE year = 2025;  -- Phải = 0
```

---

### Bước 5: Chuẩn bị năm mới 2026

```bash
php artisan year:prepare 2026
```

**Lệnh này sẽ:**
1. Set năm 2025 thành inactive
2. Set năm 2026 thành active (năm hiện tại)
3. Kiểm tra tồn kho đầu kỳ
4. Kiểm tra database sạch

**Kết quả:**
- Năm hiện tại: 2026
- Tồn kho đầu kỳ: Giữ nguyên từ cuối năm 2025
- Database sẵn sàng cho năm mới

---

### Bước 6: Backup lần cuối

```bash
php artisan year:backup 2026
```

**Backup database chính sau khi đã cleanup và chuẩn bị năm mới**

---

## ✅ CHECKLIST HOÀN THÀNH

- [ ] Backup database năm 2025 (database chính)
- [ ] Export dữ liệu năm 2025 sang `art_gallery_2025`
- [ ] Kiểm tra database `art_gallery_2025` có đầy đủ dữ liệu
- [ ] Backup database `art_gallery_2025`
- [ ] Copy tất cả file backup ra NAS/Cloud
- [ ] Cleanup dữ liệu năm 2025 khỏi database chính
- [ ] Kiểm tra database chính đã sạch
- [ ] Chuẩn bị năm 2026
- [ ] Backup database chính sau cleanup
- [ ] Test hệ thống với năm 2026

---

## 🧪 TEST SAU KHI HOÀN THÀNH

### Test 1: Kiểm tra dropdown năm
1. Đăng nhập vào hệ thống
2. Click dropdown năm ở header
3. Thấy 2 năm: 2026 (có dấu check), 2025

### Test 2: Xem dữ liệu năm 2025
1. Click dropdown → Chọn "Năm 2025"
2. Badge cảnh báo hiển thị: "Đang xem năm 2025"
3. Vào Sales → Thấy dữ liệu năm 2025
4. Vào Debt → Thấy dữ liệu năm 2025

### Test 3: Quay lại năm 2026
1. Click dropdown → Chọn "Năm 2026"
2. Badge cảnh báo ẩn
3. Vào Sales → Không có dữ liệu (database sạch)
4. Tạo hóa đơn mới → Thành công

### Test 4: Kiểm tra tồn kho
1. Vào Inventory
2. Kiểm tra số lượng tranh và vật tư
3. Phải giống với cuối năm 2025

---

## 🚨 XỬ LÝ SỰ CỐ

### Sự cố 1: Export thất bại
**Nguyên nhân:** Lỗi database, thiếu quyền, hết dung lượng

**Giải pháp:**
1. Kiểm tra log lỗi
2. Kiểm tra dung lượng ổ đĩa
3. Kiểm tra quyền user MySQL
4. Thử lại lệnh export

### Sự cố 2: Cleanup nhầm dữ liệu
**Nguyên nhân:** Chạy cleanup trước khi export

**Giải pháp:**
1. Restore từ file backup
2. Chạy lại từ Bước 1

### Sự cố 3: Không thể chuyển sang năm 2025
**Nguyên nhân:** Database `art_gallery_2025` không tồn tại hoặc không kết nối được

**Giải pháp:**
1. Kiểm tra database có tồn tại:
   ```sql
   SHOW DATABASES LIKE 'art_gallery_2025';
   ```
2. Kiểm tra bảng `year_databases`:
   ```sql
   SELECT * FROM year_databases WHERE year = 2025;
   ```
3. Kiểm tra `is_on_server = TRUE`

---

## 💾 LƯU TRỮ DÀI HẠN

### Sau 6 tháng (30/06/2026):
- Backup database `art_gallery_2025`
- Copy ra NAS/Cloud
- Có thể xóa database `art_gallery_2025` khỏi server
- Update `year_databases`: `is_on_server = FALSE`

### Khi cần xem lại dữ liệu năm 2025:
1. Import file backup vào server:
   ```bash
   mysql -u root -p art_gallery_2025 < backup_art_gallery_2025_YYYY-MM-DD.sql
   ```
2. Update `year_databases`: `is_on_server = TRUE`
3. Refresh trang → Dropdown năm sẽ hiển thị năm 2025

---

## 📞 HỖ TRỢ

Nếu gặp vấn đề, kiểm tra:
1. Log file: `storage/logs/laravel.log`
2. MySQL error log
3. Dung lượng ổ đĩa
4. Quyền truy cập database

**Lưu ý:** Luôn backup trước khi thực hiện bất kỳ thao tác nào!

---

Chúc mừng năm mới 2026! 🎉
