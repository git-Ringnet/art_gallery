# 📊 Hướng Dẫn Sử Dụng Module Báo Cáo
## Ben Thanh Art Gallery - Quản Lý Tranh

---

## 📑 Mục Lục

1. [Tổng Quan Module Báo Cáo](#1-tổng-quan-module-báo-cáo)
2. [Truy Cập Module Báo Cáo](#2-truy-cập-module-báo-cáo)
3. [Báo Cáo Thu Tiền Mặt (Daily Cash Collection)](#3-báo-cáo-thu-tiền-mặt-daily-cash-collection)
4. [Báo Cáo Thống Kê Bán Hàng (Monthly Sales)](#4-báo-cáo-thống-kê-bán-hàng-monthly-sales)
5. [Báo Cáo Công Nợ (Debt Report)](#5-báo-cáo-công-nợ-debt-report)
6. [Báo Cáo Nhập Stock (Stock Import)](#6-báo-cáo-nhập-stock-stock-import)
7. [Các Lưu Ý Quan Trọng](#7-các-lưu-ý-quan-trọng)
8. [Câu Hỏi Thường Gặp (FAQ)](#8-câu-hỏi-thường-gặp-faq)

---

## 1. Tổng Quan Module Báo Cáo

Module Báo cáo trong hệ thống Quản lý Tranh cung cấp các công cụ phân tích và theo dõi hoạt động kinh doanh của gallery. Module bao gồm **4 loại báo cáo chính**:

| Loại Báo Cáo | Mô Tả | Icon |
|--------------|-------|------|
| **Thu tiền mặt** | Theo dõi thu tiền theo ngày/tháng, phân loại tiền mặt và thẻ | 💜 |
| **Thống kê bán hàng** | Báo cáo doanh thu bán hàng theo tháng | 💙 |
| **Công nợ** | Báo cáo công nợ lũy kế hoặc theo tháng | ❤️ |
| **Nhập Stock** | Theo dõi tranh nhập kho theo tháng | 💚 |

![Trang chủ Module Báo cáo](images/01_reports_index.png)
*Hình 1: Trang chính của Module Báo cáo với 4 loại báo cáo*

---

## 2. Truy Cập Module Báo Cáo

### Bước 1: Đăng nhập hệ thống
Truy cập địa chỉ hệ thống và đăng nhập với tài khoản của bạn.

### Bước 2: Mở menu Báo cáo
Từ thanh menu bên trái, click vào mục **"Báo cáo"** (biểu tượng 📊).

### Bước 3: Chọn loại báo cáo
Click vào một trong 4 card báo cáo để xem chi tiết:
- 💜 **Thu tiền mặt** - Viền tím
- 💙 **Thống kê bán hàng** - Viền xanh dương
- ❤️ **Công nợ** - Viền đỏ
- 💚 **Nhập Stock** - Viền xanh lá

> ⚠️ **Lưu ý**: Bạn cần có quyền `reports.can_view` để truy cập module báo cáo.

---

## 3. Báo Cáo Thu Tiền Mặt (Daily Cash Collection)

### 3.1. Mục đích
Báo cáo này giúp theo dõi **tổng tiền thu được** trong một khoảng thời gian, phân loại theo:
- 💵 **Tiền mặt (Cash)**
- 💳 **Thẻ + Chuyển khoản (Card/Transfer)**

![Báo cáo Thu tiền mặt](images/02_daily_cash_collection.png)
*Hình 2: Giao diện báo cáo Thu tiền mặt*

### 3.2. Các Bộ Lọc

| Bộ lọc | Mô tả |
|--------|-------|
| **Từ ngày / Đến ngày** | Chọn khoảng thời gian báo cáo |
| **Showroom** | Lọc theo showroom cụ thể |
| **Tỷ giá (VND/USD)** | Nhập tỷ giá để quy đổi USD → VND |
| **Nhân viên** | Lọc theo nhân viên bán hàng |
| **Khách hàng** | Lọc theo khách hàng cụ thể |
| **Loại thanh toán** | Tiền mặt / Thẻ + Chuyển khoản |

### 3.3. Lọc Nhanh
Sử dụng các nút lọc nhanh để tiết kiệm thời gian:
- 📅 **Hôm nay** - Báo cáo ngày hiện tại
- 📆 **Tuần này** - Từ thứ Hai đến Chủ Nhật
- 🗓️ **Tháng này** - Từ ngày 1 đến cuối tháng
- 📅 **Năm nay** - Từ 1/1 đến 31/12

### 3.4. Các Cột Trong Bảng Chi Tiết

| Cột | Mô tả |
|-----|-------|
| **No.** | Số thứ tự |
| **Invoice** | Mã hóa đơn |
| **ID Code** | Mã khách hàng |
| **Customer name** | Tên khách hàng |
| **Adjustment (USD/VND)** | Điều chỉnh (nếu có) |
| **Collection (USD/VND)** | Tiền thu được |

### 3.5. Tổng Kết Thu Tiền
Cuối báo cáo hiển thị:
- **Collection in CASH**: Tổng tiền mặt thu được
- **In Credit Card + Transfer**: Tổng tiền thẻ/chuyển khoản
- **GRAND TOTAL**: Tổng cộng tất cả

> ⚠️ **QUAN TRỌNG**: Nếu có giao dịch bằng USD, bạn **BẮT BUỘC** phải nhập tỷ giá để xem được tổng kết cuối cùng và in báo cáo!

### 3.6. In Báo Cáo
1. Đảm bảo đã nhập tỷ giá (nếu có USD)
2. Click nút **"In báo cáo"** 🖨️
3. Hệ thống sẽ hiển thị phiên bản in tối ưu

---

## 4. Báo Cáo Thống Kê Bán Hàng (Monthly Sales)

### 4.1. Mục đích
Báo cáo này cung cấp **tổng quan về doanh thu bán hàng** trong khoảng thời gian, bao gồm:
- Tổng doanh thu
- Số tiền đã thu
- Số tiền còn nợ
- Số đơn hàng và sản phẩm

![Báo cáo Thống kê bán hàng](images/03_monthly_sales.png)
*Hình 3: Giao diện báo cáo Thống kê bán hàng*

### 4.2. Thống Kê Tổng Quan
Báo cáo hiển thị 4 card thống kê:

| Card | Màu | Nội dung |
|------|-----|----------|
| **Tổng doanh thu** | 💙 Xanh | Tổng giá trị đơn hàng |
| **Đã thu** | 💚 Xanh lá | Số tiền đã thanh toán |
| **Còn nợ** | ❤️ Đỏ | Số tiền chưa thanh toán |
| **Số đơn hàng** | 💜 Tím | Tổng số đơn và sản phẩm |

### 4.3. Các Bộ Lọc

| Bộ lọc | Mô tả |
|--------|-------|
| **Từ ngày / Đến ngày** | Khoảng thời gian báo cáo |
| **Showroom** | Lọc theo showroom |
| **Nhân viên** | Lọc theo người bán |
| **Tỷ giá** | Quy đổi USD → VND |

### 4.4. Lọc Nhanh
- 🗓️ **Tháng này** 
- 📅 **Tháng trước**
- 📊 **Quý này**
- 📅 **Năm nay**

### 4.5. Bảng Chi Tiết Bán Hàng

| Cột | Mô tả |
|-----|-------|
| **Ngày** | Ngày bán hàng |
| **Mã HĐ** | Mã hóa đơn |
| **ID Code** | Mã khách hàng |
| **Khách hàng** | Tên khách hàng |
| **Tổng USD** | Giá trị bằng USD |
| **Tổng VND** | Giá trị bằng VND |
| **Đã trả** | Số tiền đã thanh toán |
| **Còn nợ** | Số tiền chưa thanh toán |

---

## 5. Báo Cáo Công Nợ (Debt Report)

### 5.1. Mục đích
Báo cáo này giúp **theo dõi và quản lý công nợ** của khách hàng, hỗ trợ:
- Xem tất cả công nợ lũy kế
- Lọc công nợ theo từng tháng
- Theo dõi tiến độ thanh toán

![Báo cáo Công nợ](images/04_debt_report.png)
*Hình 4: Giao diện báo cáo Công nợ*

### 5.2. Loại Báo Cáo

| Loại | Mô tả |
|------|-------|
| **Lũy kế (Cumulative)** | Tất cả công nợ còn tồn đọng đến thời điểm hiện tại |
| **Theo khoảng thời gian** | Công nợ phát sinh trong khoảng thời gian chọn |

### 5.3. Thống Kê Tổng Quan

| Card | Nội dung |
|------|----------|
| **Tổng giá trị đơn hàng** | Tổng tiền các đơn hàng |
| **Đã thanh toán** | Số tiền đã thu |
| **Tổng công nợ** | Số tiền còn nợ + số khách còn nợ |

### 5.4. Các Bộ Lọc

| Bộ lọc | Mô tả |
|--------|-------|
| **Loại báo cáo** | Lũy kế / Theo khoảng thời gian |
| **Từ ngày / Đến ngày** | Khoảng thời gian |
| **Showroom** | Lọc theo showroom |
| **Khách hàng** | Lọc khách hàng cụ thể |
| **Tỷ giá** | Quy đổi USD → VND |

### 5.5. Lọc Nhanh
- 📋 **Tất cả công nợ** - Xem toàn bộ (lũy kế)
- 🗓️ **Tháng này** - Công nợ tháng hiện tại
- 📅 **Tháng trước** - Công nợ tháng trước

### 5.6. Bảng Chi Tiết Công Nợ

| Cột | Mô tả |
|-----|-------|
| **Ngày bán** | Ngày tạo đơn hàng |
| **Mã HĐ** | Mã hóa đơn |
| **ID Code** | Mã khách hàng |
| **Khách hàng** | Tên khách hàng |
| **SĐT** | Số điện thoại liên hệ |
| **Tổng tiền** | Giá trị đơn hàng (USD/VND) |
| **Đã trả** | Số tiền đã thanh toán |
| **Còn nợ** | Số tiền còn phải thu |

> 💡 **Mẹo**: Click vào mã hóa đơn để xem chi tiết đơn hàng và quản lý thanh toán.

---

## 6. Báo Cáo Nhập Stock (Stock Import)

### 6.1. Mục đích
Báo cáo này giúp **theo dõi các tranh nhập kho** trong khoảng thời gian, bao gồm:
- Số lượng tranh nhập
- Giá trị nhập kho
- Thông tin chi tiết từng tranh

![Báo cáo Nhập Stock](images/05_stock_import.png)
*Hình 5: Giao diện báo cáo Nhập Stock*

### 6.2. Thống Kê Tổng Quan

| Card | Nội dung |
|------|----------|
| **Số lượng tranh nhập** | Tổng số tranh + số mã tranh |
| **Tổng giá trị (USD)** | Tổng giá trị bằng USD |
| **Tổng giá trị (VND)** | Tổng giá trị quy đổi VND |

### 6.3. Các Bộ Lọc

| Bộ lọc | Mô tả |
|--------|-------|
| **Từ ngày / Đến ngày** | Khoảng thời gian nhập kho |
| **Tỷ giá (VND/USD)** | Quy đổi để tính tổng VND |

### 6.4. Lọc Nhanh
- 🗓️ **Tháng này**
- 📅 **Tháng trước**
- 📊 **Quý này**
- 📅 **Năm nay**

### 6.5. Bảng Chi Tiết Nhập Kho

| Cột | Mô tả |
|-----|-------|
| **Ngày nhập** | Ngày nhập tranh vào kho |
| **Mã tranh** | Mã định danh tranh |
| **Tên tranh** | Tên tác phẩm |
| **Họa sĩ** | Tên họa sĩ |
| **Chất liệu** | Chất liệu tranh |
| **Kích thước** | Kích thước (cm) |
| **SL** | Số lượng |
| **Giá (USD)** | Giá nhập USD |
| **Trạng thái** | Còn hàng / Đã bán / Đang giữ |

### 6.6. Trạng Thái Tranh

| Trạng thái | Màu | Ý nghĩa |
|------------|-----|---------|
| **Còn hàng** | 💚 Xanh | Tranh có sẵn trong kho |
| **Đã bán** | ❤️ Đỏ | Tranh đã được bán |
| **Đang giữ** | 💛 Vàng | Tranh đang được giữ |

---

## 7. Các Lưu Ý Quan Trọng

### 7.1. Về Tỷ Giá

> ⚠️ **QUAN TRỌNG**

- Nếu có giao dịch bằng **USD**, bạn **BẮT BUỘC** phải nhập tỷ giá để:
  - Xem được tổng kết cuối cùng
  - In được báo cáo
  - Tính toán chính xác tổng VND

**Cách nhập tỷ giá:**
1. Tìm ô "Tỷ giá (VND/USD)"
2. Nhập giá trị (VD: 25000 cho 1 USD = 25,000 VND)
3. Click "Xem báo cáo"

### 7.2. Về Quyền Truy Cập

| Quyền | Mô tả |
|-------|-------|
| `reports.can_view` | Xem báo cáo |
| `reports.can_filter_by_date` | Thay đổi khoảng ngày |
| `reports.can_filter_by_showroom` | Lọc theo showroom |
| `reports.can_filter_by_user` | Lọc theo nhân viên |
| `reports.can_print` | In báo cáo |

### 7.3. Về In Ấn

- Báo cáo được tối ưu cho khổ giấy **A4**
- Khuyến nghị sử dụng chế độ **in ngang (Landscape)** cho báo cáo nhiều cột
- Các thành phần bộ lọc sẽ **tự động ẩn** khi in

### 7.4. Về Dữ Liệu

- Dữ liệu báo cáo được **cập nhật theo thời gian thực**
- Báo cáo sẽ hiển thị dữ liệu của **năm được chọn** (xem góc phải màn hình)
- Đổi năm bằng cách click vào selector năm ở header

---

## 8. Câu Hỏi Thường Gặp (FAQ)

### ❓ Tại sao tôi không thấy được tổng kết cuối cùng?

**Trả lời:** Nếu có giao dịch bằng USD, bạn cần nhập tỷ giá vào ô "Tỷ giá (VND/USD)" và click "Xem báo cáo".

---

### ❓ Làm sao để xem báo cáo của năm trước?

**Trả lời:** Click vào selector năm ở góc phải header (VD: "Năm 2025") và chọn năm muốn xem.

---

### ❓ Tại sao nút In báo cáo bị vô hiệu hóa?

**Trả lời:** Có thể do:
1. Bạn chưa nhập tỷ giá (khi có giao dịch USD)
2. Bạn không có quyền `reports.can_print`

---

### ❓ Dữ liệu báo cáo có chính xác không?

**Trả lời:** Báo cáo lấy dữ liệu trực tiếp từ database và được cập nhật theo thời gian thực. Mọi thay đổi trong hệ thống sẽ phản ánh ngay lập tức.

---

### ❓ Làm sao để xuất báo cáo ra Excel?

**Trả lời:** Hiện tại module báo cáo hỗ trợ **in ra PDF/giấy**. Để xuất Excel, bạn có thể:
1. Copy dữ liệu từ bảng và paste vào Excel
2. Sử dụng chức năng Export từ các module khác (Kho, Công nợ)

---

### ❓ Tại sao tôi không xem được báo cáo của Showroom khác?

**Trả lời:** Bạn cần có quyền `reports.can_filter_by_showroom`. Liên hệ Admin để được cấp quyền.

---

## 📞 Liên Hệ Hỗ Trợ

Nếu gặp vấn đề khi sử dụng module Báo cáo, vui lòng liên hệ:

- **Email**: support@benthanhart.com
- **Hotline**: (84-8) 3823 3001

---

*Tài liệu cập nhật: 29/12/2025*
*Phiên bản: 1.0*
