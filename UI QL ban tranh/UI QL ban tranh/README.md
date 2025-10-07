# Hệ thống Quản lý Tranh & Khung

Một ứng dụng web quản lý cửa hàng tranh nghệ thuật được xây dựng với HTML, CSS (Tailwind) và JavaScript thuần.

## 🎨 Tính năng chính

### 1. Dashboard
- Tổng quan doanh số tuần/tháng
- Hiển thị công nợ còn lại
- Thống kê tồn kho tranh và khung
- Biểu đồ doanh thu tương tác
- Danh sách sản phẩm bán chạy

### 2. Bán hàng & Công nợ
- Form tạo hóa đơn với auto-complete mã tranh
- Hiển thị ảnh và thông tin tranh khi nhập mã
- Chọn khung theo kích thước
- Tính toán tự động thành tiền
- Quản lý thông tin khách hàng
- Theo dõi công nợ và thanh toán

### 3. Đổi/Trả hàng
- Tìm kiếm hóa đơn cũ theo mã
- Hiển thị chi tiết sản phẩm đã mua
- Chọn số lượng cần trả
- Tính toán tự động tiền hoàn và giảm công nợ

### 4. Báo cáo & Hóa đơn
- Bộ lọc theo thời gian (tuần/tháng/năm)
- Bảng báo cáo chi tiết với tình trạng thanh toán
- Xuất báo cáo Excel/PDF
- Xem chi tiết hóa đơn trong modal
- In hóa đơn

### 5. Quản lý Kho
- Tổng quan kho theo loại sản phẩm
- Bảng danh sách tồn kho
- Form nhập kho với đầy đủ thông tin
- Form xuất kho với lý do
- Quản lý theo "Phòng tranh" riêng biệt

### 6. Quản lý Tranh
- Bảng danh sách tranh với ảnh
- Thêm/sửa/xóa tranh
- Upload ảnh tranh
- Phân loại theo loại tranh (sơn dầu, canvas, thủy mặc...)
- Quản lý giá bán và tồn kho

## 🚀 Cách sử dụng

1. **Mở file `index.html`** trong trình duyệt web
2. **Điều hướng** qua các module bằng sidebar bên trái
3. **Tương tác** với các form và bảng dữ liệu
4. **Sử dụng** các chức năng tìm kiếm, lọc và xuất báo cáo

## 🛠️ Công nghệ sử dụng

- **HTML5**: Cấu trúc trang web
- **Tailwind CSS**: Framework CSS cho styling
- **JavaScript**: Logic tương tác và xử lý dữ liệu
- **Chart.js**: Biểu đồ doanh thu
- **Font Awesome**: Icons
- **LocalStorage**: Lưu trữ dữ liệu tạm thời

## 📱 Responsive Design

Giao diện được thiết kế responsive, hoạt động tốt trên:
- Desktop (1200px+)
- Tablet (768px - 1199px)
- Mobile (320px - 767px)

## 🎯 Tính năng nổi bật

- **Giao diện đẹp**: Sử dụng gradient và glass effect
- **Tương tác mượt**: Animation và transition
- **Tự động tính toán**: Giá tiền, công nợ, tồn kho
- **Tìm kiếm thông minh**: Auto-complete mã tranh
- **Modal popup**: Xem chi tiết hóa đơn
- **Notification system**: Thông báo trạng thái
- **Data persistence**: Lưu dữ liệu trong localStorage

## 📊 Dữ liệu mẫu

Hệ thống đã được tích hợp sẵn dữ liệu mẫu:
- 3 loại tranh với mã T001, T002, T003
- Hóa đơn mẫu HD001, HD002
- Thông tin khách hàng mẫu
- Dữ liệu tồn kho mẫu

## 🔧 Tùy chỉnh

Bạn có thể dễ dàng tùy chỉnh:
- Màu sắc trong file CSS
- Dữ liệu mẫu trong `script.js`
- Layout và components trong `index.html`
- Thêm tính năng mới bằng JavaScript

## 📝 Ghi chú

- Đây là phiên bản demo với dữ liệu mẫu
- Để sử dụng thực tế, cần tích hợp với database
- Các chức năng xuất Excel/PDF cần thêm thư viện tương ứng
- In hóa đơn cần cấu hình thêm

## 🎨 Thiết kế

Giao diện được thiết kế với:
- Màu chủ đạo: Indigo/Purple gradient
- Typography: Clean và dễ đọc
- Spacing: Consistent với Tailwind
- Icons: Font Awesome
- Effects: Glass morphism, shadows, transitions

---

**Tác giả**: AI Assistant  
**Phiên bản**: 1.0  
**Ngày tạo**: 2024
