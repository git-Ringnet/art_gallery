# Hướng dẫn Import Ảnh từ Excel

## Tổng quan
Hệ thống hỗ trợ 3 cách import ảnh khi nhập dữ liệu từ file Excel:

### 1. Upload ảnh riêng (Ưu tiên cao nhất)
- Tên file ảnh phải trùng với **Mã tranh** hoặc **Mã vật tư** (không cần phần mở rộng)
- Ví dụ: Nếu mã tranh là `T001`, file ảnh có thể là `T001.jpg`, `T001.png`, v.v.
- Chọn nhiều file ảnh cùng lúc khi import

### 2. Ảnh nhúng trong Excel (Ưu tiên thứ 2)
- Chèn ảnh trực tiếp vào file Excel
- Ảnh sẽ được tự động trích xuất và lưu vào hệ thống
- Ảnh phải nằm đúng dòng với dữ liệu tương ứng

### 3. Đường dẫn ảnh trong Excel (Ưu tiên thứ 3) - **MỚI**
- Điền đường dẫn đầy đủ đến file ảnh trong cột **"Đường dẫn hình ảnh"**
- Hệ thống sẽ tự động copy ảnh từ đường dẫn đó vào storage

## Cách sử dụng Đường dẫn ảnh

### Đối với Tranh (Paintings)
1. Mở file Excel template nhập tranh
2. Tại cột **"Đường dẫn hình ảnh"** (cột thứ 12), điền đường dẫn đầy đủ đến file ảnh
3. Ví dụ:
   ```
   C:\Users\thaih\Downloads\T001.jpg
   C:\Users\thaih\Downloads\T002.jpg
   D:\Pictures\artwork\T003.png
   ```

### Đối với Vật tư (Supplies)
1. Mở file Excel template nhập vật tư
2. Tại cột **"Đường dẫn hình ảnh"**, điền đường dẫn đầy đủ đến file ảnh
3. Ví dụ:
   ```
   C:\Users\thaih\Downloads\VT001.jpg
   C:\Users\thaih\Downloads\VT002.jpg
   ```

## Lưu ý quan trọng

### Đường dẫn file
- ✅ Sử dụng đường dẫn tuyệt đối (đầy đủ): `C:\Users\thaih\Downloads\image.jpg`
- ✅ Đảm bảo file ảnh tồn tại tại đường dẫn đó
- ✅ Hỗ trợ các định dạng: JPG, JPEG, PNG, GIF, WEBP
- ❌ Không sử dụng đường dẫn tương đối: `./images/image.jpg`
- ❌ Không sử dụng đường dẫn mạng: `\\server\share\image.jpg` (chưa hỗ trợ)

### Thứ tự ưu tiên
Nếu bạn cung cấp nhiều nguồn ảnh, hệ thống sẽ ưu tiên theo thứ tự:
1. **Upload ảnh riêng** (tên file trùng mã)
2. **Ảnh nhúng trong Excel** (embedded images)
3. **Đường dẫn ảnh trong cột** (image path column)

### Xử lý lỗi
- Nếu file ảnh không tồn tại, hệ thống sẽ ghi log cảnh báo và bỏ qua ảnh đó
- Nếu file không phải là ảnh hợp lệ, hệ thống sẽ báo lỗi
- Dữ liệu vẫn được import nhưng không có ảnh
- Kiểm tra phần "Lỗi import" sau khi import để xem chi tiết

## Ví dụ thực tế

### File Excel mẫu cho Tranh:
| Mã tranh | Tên tranh | Họa sĩ | ... | Đường dẫn hình ảnh |
|----------|-----------|--------|-----|--------------------|
| T001 | Tranh mẫu 1 | Nguyễn Văn A | ... | C:\Users\thaih\Downloads\T001.jpg |
| T002 | Tranh mẫu 2 | Trần Thị B | ... | C:\Users\thaih\Downloads\T002.jpg |

### File Excel mẫu cho Vật tư:
| Mã vật tư | Tên vật tư | Loại | ... | Đường dẫn hình ảnh |
|-----------|------------|------|-----|--------------------|
| VT001 | Khung tranh 1 | Khung | ... | C:\Users\thaih\Downloads\VT001.jpg |
| VT002 | Canvas 2 | Canvas | ... | C:\Users\thaih\Downloads\VT002.jpg |

## Khắc phục sự cố

### Ảnh không được import
1. Kiểm tra đường dẫn file có chính xác không
2. Kiểm tra file ảnh có tồn tại không
3. Kiểm tra định dạng file (phải là JPG, PNG, GIF, WEBP)
4. Kiểm tra quyền truy cập file (Windows có thể chặn)
5. Xem log lỗi sau khi import để biết chi tiết

### Đường dẫn có dấu cách
- ✅ Không cần dấu ngoặc kép: `C:\Users\thaih\My Pictures\image.jpg`
- ✅ Excel tự động xử lý dấu cách trong cell

### Đường dẫn tiếng Việt
- ⚠️ Nên tránh sử dụng đường dẫn có ký tự tiếng Việt có dấu
- ✅ Sử dụng đường dẫn tiếng Anh hoặc không dấu để tránh lỗi encoding
