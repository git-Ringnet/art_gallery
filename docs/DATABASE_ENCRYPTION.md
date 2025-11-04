# Database Encryption - Mã Hóa Database Backup

## Tổng Quan

Hệ thống hỗ trợ mã hóa file backup database bằng thuật toán **AES-256-CBC** để bảo vệ dữ liệu nhạy cảm.

## Cách Hoạt Động

### 1. Export Database (Có Mã Hóa)

Khi export database với tùy chọn "Mã hóa file backup":

1. Hệ thống tạo file SQL backup bằng `mysqldump`
2. File SQL được mã hóa bằng AES-256-CBC với key từ `APP_KEY` trong `.env`
3. File mã hóa được lưu với extension `.sql` (nhưng nội dung đã bị mã hóa)
4. File SQL gốc (không mã hóa) bị xóa

**Cấu trúc file mã hóa:**
```
[16 bytes IV] + [encrypted data]
```

### 2. Import Database (Tự Động Giải Mã)

Khi import file backup:

1. Hệ thống kiểm tra file có bị mã hóa không (bằng `isEncrypted()`)
2. Nếu bị mã hóa → Tự động giải mã bằng `APP_KEY`
3. Nếu không mã hóa → Import trực tiếp
4. Import SQL vào database hiện tại

### 3. Phát Hiện File Mã Hóa

Hệ thống phát hiện file mã hóa bằng 2 cách:

1. **Extension**: File có đuôi `.encrypted`
2. **Nội dung**: File không phải text UTF-8 hoặc không bắt đầu bằng SQL comment/command

## Bảo Mật

### APP_KEY

- File mã hóa **CHỈ** có thể giải mã với cùng `APP_KEY`
- Nếu mất `APP_KEY`, file backup sẽ **KHÔNG THỂ** khôi phục
- Lưu giữ `APP_KEY` cẩn thận và an toàn

### Khuyến Nghị

1. **Backup APP_KEY**: Lưu `APP_KEY` ở nơi an toàn (password manager, vault)
2. **Mã hóa khi cần**: Chỉ mã hóa khi cần bảo mật cao (dữ liệu nhạy cảm)
3. **Test restore**: Thử import file backup để đảm bảo có thể khôi phục

## Code Reference

### Service: `DatabaseEncryptionService`

```php
// Mã hóa file
DatabaseEncryptionService::encrypt($inputFile, $outputFile);

// Giải mã file
DatabaseEncryptionService::decrypt($inputFile, $outputFile);

// Kiểm tra file có mã hóa không
DatabaseEncryptionService::isEncrypted($file);
```

### Controller: `YearDatabaseController`

- `exportDatabase()`: Export với tùy chọn mã hóa
- `importDatabase()`: Import với tự động giải mã

## Lưu Ý

1. File mã hóa vẫn có extension `.sql` để dễ quản lý
2. Import tự động phát hiện và giải mã, không cần thao tác thủ công
3. Nếu `APP_KEY` sai, import sẽ báo lỗi "Key có thể không đúng"
4. File backup không mã hóa vẫn import bình thường

## Ví Dụ Sử Dụng

### Export với mã hóa:
1. Vào trang "Backup & Restore Database"
2. Click "Export Database"
3. ✅ Tick vào "Mã hóa file backup"
4. Nhập mô tả (tùy chọn)
5. Click "Export"

### Import file mã hóa:
1. Click "Import Database"
2. Chọn file `.sql` (có thể là file mã hóa hoặc không)
3. Hệ thống tự động phát hiện và giải mã
4. Xác nhận import

## Troubleshooting

### Lỗi: "File bị mã hóa nhưng không thể giải mã"

**Nguyên nhân**: `APP_KEY` không đúng hoặc file bị hỏng

**Giải pháp**:
1. Kiểm tra `APP_KEY` trong `.env`
2. Đảm bảo đang dùng đúng `APP_KEY` khi export
3. Thử export lại file backup mới

### Lỗi: "File SQL không hợp lệ"

**Nguyên nhân**: File không phải SQL hoặc bị hỏng

**Giải pháp**:
1. Kiểm tra file có đúng định dạng SQL không
2. Thử mở file bằng text editor để xem nội dung
3. Export lại file backup mới
