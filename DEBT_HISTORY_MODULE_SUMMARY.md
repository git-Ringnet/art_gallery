# MODULE LỊCH SỬ CÔNG NỢ - TỔNG KẾT

## 📋 TỔNG QUAN

Module Lịch sử Công nợ hiển thị **từng lần thanh toán** (payment transactions) thay vì gộp theo hóa đơn. Điều này giúp theo dõi chi tiết lịch sử thanh toán của khách hàng.

---

## ✅ TÍNH NĂNG CHÍNH

### 1. **Hiển thị Lịch sử Thanh toán**
- Mỗi dòng = 1 lần thanh toán (payment record)
- Hiển thị đầy đủ: Ngày giờ, Mã HĐ, Khách hàng, SĐT, Tổng tiền, Đã trả, Phương thức, Còn nợ, Trạng thái
- Trạng thái được tính **TẠI THỜI ĐIỂM thanh toán đó** (không phải trạng thái hiện tại)
- Sắp xếp theo thời gian mới nhất

### 2. **Tìm kiếm & Lọc**
- **Autocomplete Search**: Gợi ý tên khách hàng, SĐT, mã hóa đơn khi gõ
- **Lọc theo ngày**: Từ ngày - Đến ngày
- **Lọc theo trạng thái**: Đã TT / TT một phần / Chưa TT (tại thời điểm thanh toán)
- **Lọc theo số tiền**: Dưới 10tr / 10-50tr / 50-100tr / Trên 100tr

### 3. **Xuất File**
- **Excel**: Định dạng đẹp với headings, column widths, styles
- **PDF**: Layout landscape, có màu sắc phân biệt trạng thái
- **2 options**: Trang hiện tại (15 records) hoặc Tất cả kết quả (theo filter)
- Filename tự động có timestamp
- Bao gồm cột "Loại giao dịch"

### 4. **Chi tiết Công nợ (Show)**
- Thông tin hóa đơn và khách hàng
- Tổng hợp: Tổng tiền, Đã trả, Còn nợ
- Lịch sử thanh toán đầy đủ với: Ngày giờ, Số tiền, Phương thức, Người thu, Ghi chú
- Nút "Thu nợ" nếu còn nợ
- Quick actions: Xem hóa đơn, Xem khách hàng

### 5. **Thu nợ (Collect)**
- Form thu nợ với validation
- Chọn phương thức thanh toán: Tiền mặt / Chuyển khoản / Thẻ
- Ghi chú thanh toán
- Tự động cập nhật trạng thái hóa đơn và công nợ

### 6. **Loại Giao Dịch**
- **Thanh toán bán hàng**: Khi khách thanh toán tiền mua hàng
- **Trả hàng**: Khi khách trả hàng và được hoàn tiền
- **Đổi hàng**: Khi khách đổi hàng (thu thêm hoặc hoàn lại chênh lệch)
- Tự động phân loại dựa vào nguồn gốc thanh toán
- Hiển thị với badge màu sắc riêng biệt

---

## 🔧 CẢI TIẾN ĐÃ THỰC HIỆN

### Vấn đề 1: Cast payment_date sai kiểu
**Trước**: `'payment_date' => 'date'` → Không có timezone
**Sau**: `'payment_date' => 'datetime'` → Có timezone

### Vấn đề 2: Thiếu hiển thị giờ thanh toán
**Thêm**: Hiển thị cả ngày và giờ (d/m/Y H:i) trong index và show

### Vấn đề 3: Thiếu thông tin người thu tiền
**Thêm**: Cột "Người thu" trong bảng lịch sử thanh toán (show page)

### Vấn đề 4: Thiếu sắp xếp theo thời gian
**Thêm**: `orderBy('payment_date', 'desc')->orderBy('id', 'desc')` trong eager loading

### Vấn đề 5: Thiếu chọn phương thức thanh toán
**Thêm**: Dropdown chọn phương thức khi thu nợ (trước đây hardcode 'cash')

### Vấn đề 6: Thiếu hiển thị phương thức trong index
**Thêm**: Cột "PT Thanh toán" với badge màu sắc (Tiền mặt/CK/Thẻ)

### Vấn đề 7 & 8: Export thiếu thông tin
**Thêm**: Cột Giờ TT và PT Thanh toán trong cả Excel và PDF

### Vấn đề 9: Thiếu phân biệt loại giao dịch
**Trước**: Không phân biệt được thanh toán từ bán hàng, trả hàng hay đổi hàng
**Sau**: 
- Thêm cột `transaction_type` vào bảng `payments`
- Tự động set loại giao dịch khi tạo payment:
  - `sale_payment`: Thanh toán bán hàng (từ SalesController, DebtController)
  - `return`: Trả hàng (từ ReturnController khi hoàn thành phiếu trả)
  - `exchange`: Đổi hàng (từ ReturnController khi hoàn thành phiếu đổi)
- Hiển thị cột "Loại giao dịch" trong index, show, Excel và PDF
- Badge màu sắc: Xanh dương (Bán hàng), Cam (Trả hàng), Tím (Đổi hàng)

---

## 📊 CẤU TRÚC DỮ LIỆU

### Payment Model
```php
- id
- sale_id (FK to sales)
- amount (decimal)
- payment_method (cash/bank_transfer/card)
- transaction_type (sale_payment/return/exchange) ← MỚI
- payment_date (datetime) ← Đã sửa từ date
- notes (text)
- created_by (FK to users)
- created_at
- updated_at
```

### Debt Model
```php
- id
- sale_id (FK to sales)
- customer_id (FK to customers)
- total_amount (decimal)
- paid_amount (decimal)
- debt_amount (decimal)
- due_date (date)
- status (unpaid/partial/paid)
- notes (text)
```

---

## 🎯 LOGIC QUAN TRỌNG

### 1. Tính trạng thái TẠI THỜI ĐIỂM thanh toán
```php
// Tính tổng đã trả TẠI THỜI ĐIỂM payment này (dùng ID)
$paidAtThisTime = $payment->sale->payments()
    ->where('id', '<=', $payment->id)
    ->sum('amount');

// So sánh với tổng tiền để xác định trạng thái
if ($paidAtThisTime >= $totalAmount) {
    $status = 'paid';
} elseif ($paidAtThisTime > 0) {
    $status = 'partial';
} else {
    $status = 'unpaid';
}
```

### 2. Tính số nợ còn lại SAU thanh toán
```php
$paidUpToNow = $payment->sale->payments()
    ->where('id', '<=', $payment->id)
    ->sum('amount');
$remainingDebt = $payment->sale->total_vnd - $paidUpToNow;
```

### 3. Filter theo trạng thái tại thời điểm
```php
$allPayments = $allPayments->filter(function($payment) use ($statusFilter) {
    // Tính trạng thái tại thời điểm payment này
    // So sánh với $statusFilter
    return $status === $statusFilter;
});
```

---

## 🚀 ROUTES

```php
GET  /debt                              → debt.index (Danh sách)
GET  /debt/api/search/suggestions       → debt.api.search.suggestions (Autocomplete)
GET  /debt/export/excel?scope=current   → debt.export.excel (Xuất Excel)
GET  /debt/export/pdf?scope=all         → debt.export.pdf (Xuất PDF)
GET  /debt/{id}                         → debt.show (Chi tiết)
POST /debt/{id}/collect                 → debt.collect (Thu nợ)
```

---

## 📁 FILES LIÊN QUAN

### Controllers
- `app/Http/Controllers/DebtController.php`

### Models
- `app/Models/Debt.php`
- `app/Models/Payment.php`

### Views
- `resources/views/debts/index.blade.php` (Danh sách)
- `resources/views/debts/show.blade.php` (Chi tiết)
- `resources/views/debts/pdf.blade.php` (Template PDF)

### Exports
- `app/Exports/DebtHistoryExport.php`

### Migrations
- `database/migrations/xxxx_create_debts_table.php`
- `database/migrations/xxxx_create_payments_table.php`

---

## 🎨 UI/UX

### Màu sắc trạng thái
- **Đã Thanh Toán**: Xanh lá (green-100/green-800)
- **Thanh Toán một phần**: Vàng (yellow-100/yellow-800)
- **Chưa Thanh Toán**: Đỏ (red-100/red-800)

### Màu sắc phương thức
- **Tiền mặt**: Xanh lá (green-100/green-700)
- **Chuyển khoản**: Xanh dương (blue-100/blue-700)
- **Thẻ**: Tím (purple-100/purple-700)

### Màu sắc loại giao dịch
- **Thanh toán bán hàng**: Xanh dương (blue-100/blue-700)
- **Trả hàng**: Cam (orange-100/orange-700)
- **Đổi hàng**: Tím (purple-100/purple-700)

### Icons
- Lịch sử công nợ: `fa-credit-card`
- Thanh toán: `fa-money-bill-wave`
- Tiền mặt: `fa-money-bill-wave`
- Chuyển khoản: `fa-university`
- Thẻ: `fa-credit-card`
- Người thu: `fa-user-circle`

---

## ⚡ PERFORMANCE

### Eager Loading
```php
Payment::with(['sale.customer', 'sale.debt', 'sale.payments'])
```

### Pagination
- 15 records per page
- Manual pagination cho filtered results

### Caching
- Không có caching (real-time data)

---

## 🔒 SECURITY

### Validation
- Amount: required, numeric, min:1, max:debt_amount
- Payment method: required, in:cash,bank_transfer,card
- Notes: nullable, string

### Authorization
- Middleware: auth
- Chưa có role-based permissions (có thể thêm sau)

---

## 📝 GHI CHÚ

1. **Lịch sử không thể xóa/sửa**: Module này chỉ để XEM lịch sử, không có chức năng edit/delete payment
2. **Timezone**: Tất cả datetime đều hiển thị theo Asia/Ho_Chi_Minh
3. **Số tiền**: Format VND với dấu phân cách hàng nghìn
4. **Export**: Filename có timestamp để tránh trùng lặp

---

## 🐛 KNOWN ISSUES

Không có issues đã biết.

---

## 🔮 FUTURE ENHANCEMENTS

1. Thêm filter theo phương thức thanh toán
2. Thêm filter theo người thu tiền
3. Thêm chart/graph thống kê thanh toán theo thời gian
4. Thêm chức năng in receipt cho từng lần thanh toán
5. Thêm notification khi có thanh toán mới
6. Thêm export theo template tùy chỉnh

---

**Ngày cập nhật**: 29/10/2025
**Version**: 1.1
**Status**: ✅ Production Ready

---

## 📝 CHANGELOG

### Version 1.1 (29/10/2025)
- ✨ **NEW**: Thêm cột "Loại giao dịch" để phân biệt thanh toán bán hàng, trả hàng, đổi hàng
- ✨ **NEW**: Tự động phân loại giao dịch khi tạo payment
- ✨ **NEW**: Hiển thị loại giao dịch trong index, show, Excel và PDF
- 🔧 **UPDATE**: Cập nhật database schema với cột `transaction_type`
- 🔧 **UPDATE**: Migration tự động phân loại dữ liệu cũ dựa vào notes

### Version 1.0 (14/10/2025)
- 🎉 Initial release với đầy đủ tính năng cơ bản
