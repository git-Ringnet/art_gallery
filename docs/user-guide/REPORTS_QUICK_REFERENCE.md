# 📊 QUICK REFERENCE CARD - Module Báo Cáo
## Ben Thanh Art Gallery

---

## 🔑 Truy Cập Nhanh

| Báo cáo | URL | Phím tắt |
|---------|-----|----------|
| Trang chính | `/reports` | Menu → Báo cáo |
| Thu tiền mặt | `/reports/daily-cash-collection` | - |
| Thống kê bán hàng | `/reports/monthly-sales` | - |
| Công nợ | `/reports/debt-report` | - |
| Nhập Stock | `/reports/stock-import` | - |

---

## ⚡ Lọc Nhanh

### Thu Tiền Mặt
- 📅 Hôm nay → Báo cáo ngày hiện tại
- 📆 Tuần này → Thứ 2 - Chủ nhật
- 🗓️ Tháng này → 1 - cuối tháng
- 📅 Năm nay → 1/1 - 31/12

### Bán Hàng & Nhập Stock
- 🗓️ Tháng này
- 📅 Tháng trước
- 📊 Quý này
- 📅 Năm nay

### Công Nợ
- 📋 Tất cả công nợ (Lũy kế)
- 🗓️ Tháng này
- 📅 Tháng trước

---

## ⚠️ LƯU Ý QUAN TRỌNG

### Khi có giao dịch USD:
```
┌─────────────────────────────────────────┐
│  ⚠️ PHẢI NHẬP TỶ GIÁ                    │
│                                          │
│  1. Nhập giá trị vào ô "Tỷ giá"         │
│  2. Click "Xem báo cáo"                 │
│  3. Mới có thể in báo cáo               │
└─────────────────────────────────────────┘
```

### Ví dụ tỷ giá:
- `25000` = 1 USD = 25,000 VND
- `25500` = 1 USD = 25,500 VND

---

## 📊 Ý Nghĩa Màu Sắc

| Màu | Trong Card | Trong Bảng |
|-----|------------|------------|
| 💙 Xanh | Tổng/Doanh thu | Số tiền tổng |
| 💚 Xanh lá | Đã thu/Còn hàng | Số tiền đã trả |
| ❤️ Đỏ | Còn nợ/Đã bán | Số tiền còn nợ |
| 💜 Tím | Thu tiền/Số đơn | Tiền mặt |
| 💛 Vàng | Cảnh báo | Đang giữ |

---

## 🖨️ In Báo Cáo

1. ✅ Đảm bảo đã nhập tỷ giá (nếu có USD)
2. ✅ Click nút **"In báo cáo"**
3. ✅ Chọn máy in hoặc "Save as PDF"
4. ✅ Khuyến nghị: Khổ A4, in ngang

---

## 🔐 Quyền Cần Có

| Quyền | Cho phép |
|-------|----------|
| `can_view` | Xem báo cáo |
| `can_filter_by_date` | Đổi ngày |
| `can_filter_by_showroom` | Lọc showroom |
| `can_filter_by_user` | Lọc nhân viên |
| `can_print` | In báo cáo |

---

## 📞 Hỗ Trợ

- **Hotline**: (84-8) 3823 3001
- **Email**: support@benthanhart.com

---

*v1.0 - 29/12/2025*
