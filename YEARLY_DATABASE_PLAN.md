# KẾ HOẠCH TÁCH DATABASE THEO NĂM

## 📋 TỔNG QUAN

### Mục tiêu:
- Tách database theo năm để tiết kiệm dung lượng
- Cuối năm: Export dữ liệu cũ sang database riêng (VD: `art_gallery_2024`)
- Năm mới: Database chính chỉ chứa dữ liệu năm hiện tại + tồn kho đầu kỳ
- Tra cứu dữ liệu cũ: Chọn năm → Hệ thống tự kết nối DB tương ứng

---

## 🏗️ KIẾN TRÚC

### Database Structure:
```
art_gallery (DB chính - năm hiện tại)
├── sales (chỉ năm 2025)
├── debts (chỉ năm 2025)
├── returns (chỉ năm 2025)
├── payments (chỉ năm 2025)
├── inventory_transactions (chỉ năm 2025)
├── paintings (tồn kho hiện tại)
├── supplies (tồn kho hiện tại)
├── customers (tất cả - không tách)
├── users (tất cả - không tách)
├── roles (tất cả - không tách)
└── showrooms (tất cả - không tách)

art_gallery_2024 (DB lưu trữ)
├── sales (toàn bộ năm 2024)
├── debts (toàn bộ năm 2024)
├── returns (toàn bộ năm 2024)
├── payments (toàn bộ năm 2024)
├── inventory_transactions (toàn bộ năm 2024)
├── paintings_snapshot (tồn kho cuối năm 2024)
└── supplies_snapshot (tồn kho cuối năm 2024)

art_gallery_2023 (DB lưu trữ)
└── ... (tương tự)
```

---

## 🔧 TRIỂN KHAI

### Bước 1: Thêm cột year vào các bảng cần tách
```sql
ALTER TABLE sales ADD COLUMN year INT NOT NULL DEFAULT 2025;
ALTER TABLE debts ADD COLUMN year INT NOT NULL DEFAULT 2025;
ALTER TABLE returns ADD COLUMN year INT NOT NULL DEFAULT 2025;
ALTER TABLE payments ADD COLUMN year INT NOT NULL DEFAULT 2025;
ALTER TABLE inventory_transactions ADD COLUMN year INT NOT NULL DEFAULT 2025;

-- Thêm index để query nhanh
CREATE INDEX idx_sales_year ON sales(year);
CREATE INDEX idx_debts_year ON debts(year);
CREATE INDEX idx_returns_year ON returns(year);
CREATE INDEX idx_payments_year ON payments(year);
CREATE INDEX idx_inventory_transactions_year ON inventory_transactions(year);
```

### Bước 2: Tạo bảng year_databases
```sql
CREATE TABLE year_databases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    year INT NOT NULL UNIQUE,
    database_name VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_on_server BOOLEAN DEFAULT TRUE,
    description TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Insert dữ liệu mẫu
INSERT INTO year_databases (year, database_name, is_active, is_on_server, description) VALUES
(2025, 'art_gallery', TRUE, TRUE, 'Database năm hiện tại'),
(2024, 'art_gallery_2024', FALSE, TRUE, 'Database năm 2024 - Đã lưu trữ');
```

### Bước 3: Tạo service quản lý database theo năm
File: `app/Services/YearDatabaseService.php`

### Bước 4: Tạo command export dữ liệu cuối năm
File: `app/Console/Commands/ExportYearData.php`

### Bước 5: Cập nhật UI - Thêm dropdown chọn năm
- Header: Dropdown chọn năm (2025, 2024, 2023...)
- Khi chọn năm → Hệ thống tự kết nối DB tương ứng
- Hiển thị badge "Đang xem dữ liệu năm 2024" nếu không phải năm hiện tại

---

## 📅 QUY TRÌNH CUỐI NĂM

### Ngày 31/12/2025:

**1. Backup toàn bộ database hiện tại**
```bash
php artisan db:backup
```

**2. Tạo database mới cho năm cũ**
```bash
php artisan year:create-archive 2025
```
Lệnh này sẽ:
- Tạo database `art_gallery_2025`
- Copy cấu trúc bảng
- Export dữ liệu năm 2025 sang DB mới
- Tạo snapshot tồn kho cuối năm

**3. Dọn dẹp database chính**
```bash
php artisan year:cleanup 2025
```
Lệnh này sẽ:
- Xóa dữ liệu năm 2025 khỏi DB chính
- Giữ lại tồn kho đầu kỳ năm 2026
- Cập nhật bảng year_databases

**4. Chuẩn bị năm mới**
```bash
php artisan year:prepare 2026
```
Lệnh này sẽ:
- Tạo tồn kho đầu kỳ từ tồn kho cuối kỳ năm 2025
- Reset các số liệu thống kê
- Chuẩn bị database cho năm mới

---

## 🔍 TRA CỨU DỮ LIỆU CŨ

### Trường hợp 1: DB cũ vẫn trên server
1. User chọn năm từ dropdown (VD: 2024)
2. Hệ thống kiểm tra `year_databases` → `is_on_server = TRUE`
3. Tự động kết nối `art_gallery_2024`
4. Hiển thị dữ liệu năm 2024
5. Badge cảnh báo: "⚠️ Đang xem dữ liệu năm 2024 (chỉ đọc)"

### Trường hợp 2: DB cũ đã offline (NAS/Cloud)
1. User chọn năm từ dropdown (VD: 2023)
2. Hệ thống kiểm tra `year_databases` → `is_on_server = FALSE`
3. Hiển thị thông báo: "Database năm 2023 đã được lưu trữ ngoại tuyến. Vui lòng import database để xem."
4. Nút "Import Database" → Hướng dẫn import file SQL

---

## 💾 TIẾT KIỆM DUNG LƯỢNG

### Ước tính:
- **Năm 1**: 500MB (sales + debts + returns + inventory_transactions)
- **Năm 2**: 1GB (nếu không tách)
- **Năm 3**: 1.5GB (nếu không tách)

### Sau khi tách:
- **DB chính**: Luôn ~500MB (chỉ năm hiện tại)
- **DB 2024**: 500MB (có thể đưa ra NAS)
- **DB 2023**: 500MB (có thể đưa ra NAS)

**Tiết kiệm**: ~50-70% dung lượng trên server chính

---

## 🛡️ BẢO MẬT & BACKUP

### Backup tự động:
- Hàng ngày: Backup DB chính
- Cuối tháng: Backup tất cả DB (bao gồm DB cũ)
- Cuối năm: Full backup trước khi tách

### Lưu trữ:
- **Server**: DB năm hiện tại + 2 năm gần nhất
- **NAS**: Tất cả DB cũ
- **Cloud**: Backup định kỳ

---

## 📊 DASHBOARD & THỐNG KÊ

### Thống kê theo năm:
- Dropdown chọn năm để xem báo cáo
- So sánh giữa các năm (nếu DB vẫn trên server)
- Biểu đồ tăng trưởng theo năm

### Hạn chế:
- Không thể query cross-year (VD: tổng doanh thu 2023-2025)
- Phải query từng năm rồi tổng hợp

---

## 🚀 ROADMAP TRIỂN KHAI

### Phase 1: Chuẩn bị (1-2 tuần)
- [ ] Thêm cột `year` vào các bảng
- [ ] Tạo bảng `year_databases`
- [ ] Tạo YearDatabaseService
- [ ] Tạo UI dropdown chọn năm

### Phase 2: Commands (1 tuần)
- [ ] Command: `year:create-archive`
- [ ] Command: `year:cleanup`
- [ ] Command: `year:prepare`
- [ ] Command: `year:backup`

### Phase 3: Testing (1 tuần)
- [ ] Test export dữ liệu
- [ ] Test kết nối multi-database
- [ ] Test tra cứu dữ liệu cũ
- [ ] Test performance

### Phase 4: Production (Cuối năm 2025)
- [ ] Chạy export thực tế
- [ ] Monitor và fix bugs
- [ ] Document quy trình

---

## ⚠️ LƯU Ý

### Ưu điểm:
- ✅ Tiết kiệm dung lượng đáng kể
- ✅ Database nhẹ → Query nhanh hơn
- ✅ Dễ backup và restore
- ✅ Có thể offline DB cũ khi không cần

### Nhược điểm:
- ❌ Không query cross-year dễ dàng
- ❌ Phải quản lý nhiều database
- ❌ Cần import DB khi muốn xem dữ liệu offline

### Khuyến nghị:
- Giữ DB 2-3 năm gần nhất trên server
- Backup đầy đủ trước khi tách
- Test kỹ trên staging trước khi production

---

Đây là kế hoạch chi tiết! Bạn muốn tôi bắt đầu triển khai từ phase nào? 🚀
