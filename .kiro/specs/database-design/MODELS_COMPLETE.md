# ✅ HOÀN THÀNH - TẤT CẢ MODELS

## 📊 Tổng quan

Đã tạo thành công **15 Models** tương ứng với 15 bảng database, bao gồm đầy đủ:
- Relationships (quan hệ giữa các bảng)
- Casts (chuyển đổi kiểu dữ liệu)
- Scopes (query helpers)
- Helper methods (phương thức hỗ trợ)
- Boot events (sự kiện lifecycle)

---

## 📁 Danh sách Models đã tạo

### 1. **User** (`app/Models/User.php`)
**Mô tả:** Quản lý người dùng hệ thống

**Relationships:**
- `belongsTo` Role
- `hasMany` Sale, Payment, InventoryTransaction, ExchangeRate
- `hasMany` ReturnItem (as processedReturns)

**Key Methods:**
- `hasPermission($module)` - Kiểm tra quyền truy cập
- `isActive()` - Kiểm tra trạng thái hoạt động
- `updateLastLogin()` - Cập nhật lần đăng nhập cuối

---

### 2. **Role** (`app/Models/Role.php`)
**Mô tả:** Vai trò người dùng

**Relationships:**
- `hasMany` User
- `belongsToMany` Permission (through role_permissions)
- `hasMany` RolePermission

**Key Methods:**
- `hasPermission($module)` - Kiểm tra quyền
- `assignPermission($permissionId)` - Gán quyền
- `removePermission($permissionId)` - Xóa quyền
- `syncPermissions($permissionIds)` - Đồng bộ quyền

---

### 3. **Permission** (`app/Models/Permission.php`)
**Mô tả:** Quyền truy cập module

**Relationships:**
- `belongsToMany` Role (through role_permissions)
- `hasMany` RolePermission

**Key Methods:**
- `scopeForModule($module)` - Lọc theo module
- `getModules()` - Lấy danh sách modules

**Modules hỗ trợ:**
- dashboard - Báo cáo thống kê
- sales - Bán hàng
- debt - Lịch sử công nợ
- returns - Đổi/Trả hàng
- inventory - Quản lý kho
- showrooms - Phòng trưng bày
- permissions - Phân quyền

---

### 4. **RolePermission** (`app/Models/RolePermission.php`)
**Mô tả:** Bảng trung gian Role-Permission

**Relationships:**
- `belongsTo` Role
- `belongsTo` Permission

---

### 5. **Customer** (`app/Models/Customer.php`)
**Mô tả:** Khách hàng

**Relationships:**
- `hasMany` Sale, Debt, ReturnItem

**Key Methods:**
- `hasDebt()` - Kiểm tra có nợ
- `updateTotals()` - Cập nhật tổng tiền
- `scopeWithDebt()` - Lọc khách có nợ
- `scopeSearch($search)` - Tìm kiếm

---

### 6. **Showroom** (`app/Models/Showroom.php`)
**Mô tả:** Phòng trưng bày

**Relationships:**
- `hasMany` Sale

**Key Methods:**
- `getMonthlyRevenue($year, $month)` - Doanh thu tháng
- `scopeActive()` - Lọc showroom hoạt động
- `scopeSearch($search)` - Tìm kiếm
- `getLogoUrlAttribute()` - URL logo

---

### 7. **Painting** (`app/Models/Painting.php`)
**Mô tả:** Tranh nghệ thuật

**Relationships:**
- `hasMany` SaleItem
- `hasMany` InventoryTransaction

**Key Methods:**
- `isInStock()` - Kiểm tra còn hàng
- `reduceQuantity($amount)` - Giảm số lượng
- `increaseQuantity($amount)` - Tăng số lượng
- `scopeAvailable()` - Lọc tranh có sẵn
- `scopeSearch($search)` - Tìm kiếm

**Status:**
- `in_stock` - Còn hàng
- `sold` - Đã bán

---

### 8. **Supply** (`app/Models/Supply.php`)
**Mô tả:** Vật tư khung tranh

**Relationships:**
- `hasMany` SaleItem
- `hasMany` InventoryTransaction

**Key Methods:**
- `isLowStock()` - Kiểm tra sắp hết hàng
- `reduceQuantity($amount)` - Giảm số lượng
- `increaseQuantity($amount)` - Tăng số lượng
- `scopeLowStock()` - Lọc vật tư sắp hết
- `getTypes()` - Danh sách loại vật tư

**Types:**
- `frame` - Khung tranh
- `canvas` - Canvas
- `other` - Khác

---

### 9. **Sale** (`app/Models/Sale.php`)
**Mô tả:** Hóa đơn bán hàng

**Relationships:**
- `belongsTo` Customer, Showroom, User
- `hasMany` SaleItem, Payment, ReturnItem
- `hasOne` Debt

**Key Methods:**
- `generateInvoiceCode()` - Tạo mã hóa đơn (HD + YYMM + 0001)
- `calculateTotals()` - Tính tổng tiền
- `updatePaymentStatus()` - Cập nhật trạng thái thanh toán
- `scopeWithDebt()` - Lọc hóa đơn có nợ
- `scopeSearch($search)` - Tìm kiếm

**Payment Status:**
- `unpaid` - Chưa thanh toán
- `partial` - Thanh toán một phần
- `paid` - Đã thanh toán

---

### 10. **SaleItem** (`app/Models/SaleItem.php`)
**Mô tả:** Chi tiết hóa đơn

**Relationships:**
- `belongsTo` Sale, Painting, Supply

**Key Methods:**
- `calculateTotals()` - Tính tổng tiền item
- `processPaintingStock()` - Xử lý giảm tồn kho tranh

**Currency:**
- `USD` - Đô la Mỹ
- `VND` - Việt Nam Đồng

---

### 11. **Payment** (`app/Models/Payment.php`)
**Mô tả:** Thanh toán

**Relationships:**
- `belongsTo` Sale
- `belongsTo` User (as createdBy)

**Key Methods:**
- `getPaymentMethods()` - Danh sách phương thức thanh toán

**Payment Methods:**
- `cash` - Tiền mặt
- `bank_transfer` - Chuyển khoản
- `card` - Thẻ
- `other` - Khác

**Boot Events:**
- Tự động cập nhật `payment_status` của Sale khi Payment được tạo/sửa/xóa

---

### 12. **Debt** (`app/Models/Debt.php`)
**Mô tả:** Công nợ

**Relationships:**
- `belongsTo` Sale, Customer

**Key Methods:**
- `updateDebtAmount()` - Cập nhật số nợ
- `isOverdue()` - Kiểm tra quá hạn
- `scopeUnpaid()` - Lọc nợ chưa trả
- `scopeOverdue()` - Lọc nợ quá hạn

**Status:**
- `unpaid` - Chưa thanh toán
- `partial` - Thanh toán một phần
- `paid` - Đã thanh toán

**Boot Events:**
- Tự động cập nhật `total_debt` của Customer khi Debt được lưu/xóa

---

### 13. **ReturnItem** (`app/Models/ReturnItem.php`)
**Mô tả:** Đổi/Trả hàng

**Relationships:**
- `belongsTo` Sale, Customer
- `belongsTo` User (as processedBy)

**Key Methods:**
- `approve($userId)` - Duyệt đơn trả
- `reject($userId, $reason)` - Từ chối đơn trả
- `complete($userId)` - Hoàn tất đơn trả
- `scopePending()` - Lọc đơn chờ xử lý

**Status:**
- `pending` - Chờ xử lý
- `approved` - Đã duyệt
- `rejected` - Từ chối
- `completed` - Hoàn tất

---

### 14. **InventoryTransaction** (`app/Models/InventoryTransaction.php`)
**Mô tả:** Lịch sử xuất nhập kho

**Relationships:**
- `belongsTo` User (as createdBy)
- Polymorphic: item (Painting hoặc Supply)
- Polymorphic: reference (Sale hoặc ReturnItem)

**Key Methods:**
- `getTransactionTypeLabel()` - Nhãn loại giao dịch
- `getItemTypeLabel()` - Nhãn loại sản phẩm
- `scopeImports()` - Lọc phiếu nhập
- `scopeExports()` - Lọc phiếu xuất

**Transaction Types:**
- `import` - Nhập kho
- `export` - Xuất kho
- `adjustment` - Điều chỉnh

**Item Types:**
- `painting` - Tranh
- `supply` - Vật tư

---

### 15. **ExchangeRate** (`app/Models/ExchangeRate.php`)
**Mô tả:** Tỷ giá USD/VND

**Relationships:**
- `belongsTo` User (as createdBy)

**Key Methods:**
- `getCurrentRate()` - Lấy tỷ giá hiện tại
- `getRateForDate($date)` - Lấy tỷ giá theo ngày
- `convertToVnd($usdAmount, $date)` - Chuyển USD sang VND
- `convertToUsd($vndAmount, $date)` - Chuyển VND sang USD
- `scopeActive()` - Lọc tỷ giá đang áp dụng
- `scopeFuture()` - Lọc tỷ giá tương lai

---

## 🔗 Sơ đồ quan hệ chính

```
User
├── Role → Permission (many-to-many)
├── Sale
│   ├── Customer
│   ├── Showroom
│   ├── SaleItem
│   │   ├── Painting
│   │   └── Supply
│   ├── Payment
│   ├── Debt
│   └── ReturnItem
└── InventoryTransaction
    ├── Painting/Supply (polymorphic)
    └── Sale/ReturnItem (polymorphic)

ExchangeRate (standalone)
```

---

## ✅ Tính năng đã implement

### 🔐 Authentication & Authorization
- ✅ User authentication với role-based access
- ✅ Permission system theo module
- ✅ Tracking last login

### 💰 Sales Management
- ✅ Tạo hóa đơn với mã tự động (HD + YYMM + 0001)
- ✅ Hỗ trợ 2 loại tiền tệ (USD/VND)
- ✅ Tính toán tự động subtotal, discount, total
- ✅ Tracking payment status (unpaid/partial/paid)

### 👥 Customer Management
- ✅ Tracking tổng mua hàng
- ✅ Tracking tổng công nợ
- ✅ Search theo name/phone/email

### 📦 Inventory Management
- ✅ Quản lý tranh và vật tư
- ✅ Tự động giảm tồn kho khi bán
- ✅ Tự động tăng tồn kho khi trả hàng
- ✅ Low stock warning
- ✅ Lịch sử xuất nhập kho chi tiết

### 💳 Payment & Debt
- ✅ Nhiều phương thức thanh toán
- ✅ Thanh toán từng phần
- ✅ Tự động cập nhật công nợ
- ✅ Tracking overdue debts

### 🔄 Returns Management
- ✅ Workflow: pending → approved → completed
- ✅ Tracking người xử lý
- ✅ Hoàn tiền tự động

### 💱 Exchange Rate
- ✅ Lịch sử tỷ giá
- ✅ Tỷ giá theo ngày
- ✅ Chuyển đổi USD ↔ VND

---

## 🎯 Các tính năng tự động

1. **Auto-update Payment Status**: Khi tạo/sửa/xóa Payment → tự động cập nhật Sale.payment_status
2. **Auto-update Customer Totals**: Khi tạo/sửa/xóa Debt → tự động cập nhật Customer.total_debt
3. **Auto-reduce Stock**: Khi bán hàng → tự động giảm Painting.quantity và Supply.quantity
4. **Auto-create Inventory Transaction**: Khi xuất/nhập kho → tự động tạo lịch sử
5. **Auto-generate Invoice Code**: Mã hóa đơn tự động theo format HD + YYMM + 0001

---

## 📝 Ghi chú quan trọng

### Casts (Chuyển đổi kiểu dữ liệu)
- `decimal:2` - Số thập phân 2 chữ số (tiền, số lượng)
- `date` - Ngày tháng
- `datetime` - Ngày giờ
- `boolean` - True/False
- `integer` - Số nguyên

### Scopes (Query Helpers)
Tất cả Models đều có scopes để query dễ dàng:
- `search($keyword)` - Tìm kiếm
- `active()` - Lọc đang hoạt động
- `dateRange($from, $to)` - Lọc theo khoảng thời gian

### Boot Events
Một số Models có boot events để tự động xử lý:
- Payment: Cập nhật Sale payment_status
- Debt: Cập nhật Customer totals

---

## 🚀 Sẵn sàng cho bước tiếp theo

Database và Models đã hoàn chỉnh! Bạn có thể:

1. ✅ Tạo Seeders để thêm dữ liệu mẫu
2. ✅ Tạo Controllers để xử lý logic
3. ✅ Tạo Views để hiển thị giao diện
4. ✅ Tạo API endpoints
5. ✅ Viết tests

---

**Ngày hoàn thành:** 08/10/2025  
**Tổng số Models:** 15  
**Tổng số Relationships:** 40+  
**Tổng số Methods:** 100+
