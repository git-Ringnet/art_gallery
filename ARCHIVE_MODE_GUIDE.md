# HƯỚNG DẪN QUẢN LÝ DATABASE THEO NĂM

## 📋 TỔNG QUAN

Hệ thống cho phép:
1. **Xem dữ liệu năm cũ** - Chế độ chỉ đọc (read-only)
2. **Export backup** - Tạo file ZIP chứa SQL + ảnh
3. **Cleanup năm cũ** - Xóa dữ liệu giao dịch, giữ tồn đầu kỳ
4. **Chuẩn bị năm mới** - Tạo năm mới và set làm năm hiện tại

---

## 🎯 XEM DỮ LIỆU NĂM CŨ

### Cách sử dụng
1. Click dropdown **"Năm 20XX"** ở góc phải header
2. Chọn năm muốn xem
3. **Banner cảnh báo màu cam** hiển thị
4. Tất cả nút **Thêm/Sửa/Xóa** bị ẩn
5. Click **"Quay lại năm hiện tại"** để trở về

### Lưu ý
- Dữ liệu vẫn nằm trong cùng database (filter theo cột `year`)
- Middleware sẽ block mọi thao tác thay đổi dữ liệu

---

## 📦 QUY TRÌNH CUỐI NĂM

### Bước 1: Export Backup (với ảnh)
```bash
php artisan year:export 2024 --include-images
```
Hoặc qua UI: `/year/manage` → "Export với Ảnh"

File ZIP sẽ chứa:
- `database_2024.sql` - SQL dump của năm
- `images/paintings/` - Ảnh tranh
- `images/supplies/` - Ảnh vật tư

### Bước 2: Cleanup Năm Cũ
```bash
php artisan year:cleanup 2024
```
Hoặc qua UI: `/year/manage` → "Cleanup Năm Cũ"

Sẽ xóa:
- Tất cả giao dịch năm 2024 (sales, debts, payments, returns...)
- Ảnh của sản phẩm đã bán hết (quantity = 0)
- Sản phẩm đã bán hết

Giữ lại:
- Tồn kho đầu kỳ (sản phẩm còn quantity > 0)
- Ảnh của sản phẩm còn tồn

### Bước 3: Chuẩn Bị Năm Mới
```bash
php artisan year:prepare 2026
```
Hoặc qua UI: `/year/manage` → "Tạo Năm Mới"

---

## 🔧 ARTISAN COMMANDS

### Export năm
```bash
# Export SQL only
php artisan year:export 2024

# Export SQL + ảnh
php artisan year:export 2024 --include-images
```

### Cleanup năm
```bash
# Xóa dữ liệu + ảnh
php artisan year:cleanup 2024

# Xóa dữ liệu, giữ ảnh
php artisan year:cleanup 2024 --keep-images

# Bỏ qua xác nhận
php artisan year:cleanup 2024 --force
```

### Chuẩn bị năm mới
```bash
php artisan year:prepare 2026
php artisan year:prepare 2026 --force
```

---

## 🌐 ROUTES

| Route | Method | Chức năng |
|-------|--------|-----------|
| `/year` | GET | Trang backup/restore |
| `/year/manage` | GET | Trang quản lý năm |
| `/year/switch` | POST | Chuyển năm xem |
| `/year/reset` | POST | Quay lại năm hiện tại |
| `/year/export` | POST | Export SQL |
| `/year/export-with-images` | POST | Export SQL + ảnh |
| `/year/cleanup` | POST | Xóa dữ liệu năm cũ |
| `/year/prepare` | POST | Tạo năm mới |
| `/year/stats/{year}` | GET | Thống kê năm |

---

## 📁 CẤU TRÚC FILE

```
app/
├── Console/Commands/
│   ├── ExportYearData.php      # Command export
│   ├── CleanupYearData.php     # Command cleanup
│   └── PrepareNewYear.php      # Command prepare
├── Http/
│   ├── Controllers/
│   │   └── YearDatabaseController.php
│   └── Middleware/
│       └── CheckArchiveMode.php
├── Models/
│   └── YearDatabase.php
└── Services/
    └── YearDatabaseService.php

resources/views/
├── components/
│   └── year-selector.blade.php
└── year-database/
    ├── simple.blade.php        # Trang backup/restore
    └── manage.blade.php        # Trang quản lý năm

storage/backups/databases/      # Thư mục lưu file backup
```

---

## 🛠️ BLADE DIRECTIVES

```blade
{{-- Ẩn khi xem năm cũ --}}
@notArchive
    <button>Thêm mới</button>
@endnotArchive

{{-- Hiện khi xem năm cũ --}}
@isArchive
    <div class="alert">Đang xem dữ liệu cũ</div>
@endisArchive
```

---

## ⚠️ LƯU Ý QUAN TRỌNG

1. **LUÔN export backup trước khi cleanup**
2. **Cleanup không thể hoàn tác**
3. **Ảnh của sản phẩm còn tồn sẽ được giữ lại**
4. **Dữ liệu năm hiện tại không thể cleanup**
