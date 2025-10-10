# 🔍 Hướng dẫn Search & Filter - Trang Bán Hàng

## ✨ Tính năng đã thêm

### 1. Search với Gợi ý Thông Minh (Smart Search Suggestions)

**Tìm kiếm theo:**
- ✅ Mã hóa đơn (HD2501001, HD2501002...)
- ✅ Tên khách hàng
- ✅ Số điện thoại khách hàng
- ✅ Email khách hàng
- ✅ Mô tả sản phẩm trong hóa đơn
- ✅ Ghi chú

**Gợi ý tự động:**
- Gõ từ 2 ký tự trở lên → hiện gợi ý
- Gợi ý được phân loại:
  - 📄 **Hóa đơn**: Click để xem chi tiết
  - 👤 **Khách hàng**: Click để tìm tất cả đơn của KH
- Hiển thị thông tin phụ (số tiền, số đơn hàng...)

### 2. Filter Cơ Bản

**Row 1:**
- 🔍 **Tìm kiếm**: Ô search với gợi ý
- 📊 **Sắp xếp**: Theo ngày bán, mã HD, tổng tiền, công nợ (tăng/giảm dần)

**Row 2:**
- 📅 **Từ ngày - Đến ngày**: Lọc theo khoảng thời gian
- 💳 **Trạng thái thanh toán**: Đã TT / TT 1 phần / Chưa TT
- 🏪 **Showroom**: Lọc theo phòng trưng bày
- 👤 **Nhân viên**: Lọc theo người bán

### 3. Filter Nâng Cao (Advanced Filters)

Click nút **"Nâng cao"** để hiện thêm:
- 💰 **Số tiền từ - đến**: Lọc theo khoảng giá trị (VNĐ)
- ⚠️ **Công nợ**: Có công nợ / Không công nợ

### 4. Thống Kê Tổng Quan

Hiển thị 4 card thống kê:
- 📈 **Tổng doanh thu**: Tổng tiền của các đơn đang hiển thị
- 💵 **Đã thu**: Tổng số tiền đã thanh toán
- 🔴 **Công nợ**: Tổng số tiền còn nợ
- 📋 **Số đơn hàng**: Tổng số hóa đơn

### 5. Cột Mới Trong Bảng

Thêm cột **"Nhân viên"** hiển thị người bán hàng

## 🎯 Cách Sử Dụng

### Tìm kiếm nhanh:
```
1. Gõ mã HD: "HD2501" → Hiện gợi ý các HD
2. Gõ tên KH: "Nguyễn" → Hiện gợi ý KH
3. Gõ SĐT: "0987" → Hiện gợi ý KH
4. Click vào gợi ý để xem hoặc tìm
```

### Lọc theo nhiều điều kiện:
```
1. Chọn khoảng thời gian: 01/01/2025 - 31/01/2025
2. Chọn trạng thái: "Chưa thanh toán"
3. Chọn showroom: "Showroom Quận 1"
4. Click "Lọc"
```

### Lọc nâng cao:
```
1. Click "Nâng cao"
2. Nhập số tiền từ: 1000000
3. Nhập số tiền đến: 5000000
4. Chọn "Có công nợ"
5. Click "Lọc"
```

### Sắp xếp:
```
1. Chọn "Sắp xếp theo": Tổng tiền
2. Chọn: Giảm dần
3. Click "Lọc"
```

## 🔧 API Endpoints

### Search Suggestions:
```
GET /sales/api/search/suggestions?q={query}

Response:
[
  {
    "type": "invoice",
    "icon": "fa-file-invoice",
    "label": "HD2501001",
    "sublabel": "Nguyễn Văn A - 2,500,000đ",
    "value": "HD2501001",
    "url": "/sales/1"
  },
  {
    "type": "customer",
    "icon": "fa-user",
    "label": "Nguyễn Văn A",
    "sublabel": "0987654321 - 5 đơn hàng",
    "value": "Nguyễn Văn A",
    "search": "Nguyễn Văn A"
  }
]
```

## 📊 Query Parameters

Tất cả filter được truyền qua URL query string:

```
/sales?search=HD2501
      &from_date=2025-01-01
      &to_date=2025-01-31
      &payment_status=unpaid
      &showroom_id=1
      &user_id=2
      &min_amount=1000000
      &max_amount=5000000
      &has_debt=1
      &sort_by=total_vnd
      &sort_order=desc
```

## 💡 Tips

1. **Tìm kiếm nhanh**: Gõ mã HD hoặc tên KH → Enter
2. **Xóa filter**: Click nút "Xóa lọc" để reset tất cả
3. **Bookmark**: URL có query string → có thể bookmark
4. **Pagination**: Filter được giữ khi chuyển trang
5. **Responsive**: Hoạt động tốt trên mobile

## 🎨 UI/UX Features

- ✅ Autocomplete với debounce (300ms)
- ✅ Click outside để đóng suggestions
- ✅ Keyboard navigation (sắp tới)
- ✅ Loading states
- ✅ Icon phân loại rõ ràng
- ✅ Màu sắc theo loại (invoice=blue, customer=green)
- ✅ Collapsible advanced filters
- ✅ Real-time result count

## 🚀 Performance

- Debounce search: 300ms
- Limit suggestions: 5 mỗi loại
- Pagination: 20 items/page
- Eager loading: customer, showroom, user
- Index database: invoice_code, sale_date

## 📝 Code Structure

```
Controller:
- index(): Main listing với filters
- searchSuggestions(): API cho autocomplete

View:
- Search input với suggestions dropdown
- Filter form với basic + advanced
- Stats cards
- Table với sorting

JavaScript:
- fetchSuggestions(): Gọi API
- displaySuggestions(): Render UI
- selectSuggestion(): Handle click
- toggleAdvancedFilters(): Show/hide
```

---

**Hoàn thành! Hệ thống search & filter đã sẵn sàng sử dụng.** 🎉
