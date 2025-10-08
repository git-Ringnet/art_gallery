# 📊 TỔNG HỢP DATABASE - Hệ thống Quản lý Tranh & Khung

## ✅ Đã tạo thành công 15 bảng

### 1. **roles** - Vai trò người dùng
- **Mục đích**: Quản lý các vai trò trong hệ thống
- **Các trường chính**:
  - `id`: ID tự tăng
  - `name`: Tên vai trò (Admin, Nhân viên bán hàng, Thủ kho)
  - `description`: Mô tả vai trò
  - `timestamps`: Thời gian tạo/cập nhật

### 2. **permissions** - Quyền truy cập module
- **Mục đích**: Định nghĩa các quyền truy cập module
- **Các trường chính**:
  - `id`: ID tự tăng
  - `module`: Tên module (dashboard, sales, debt, returns, inventory, showrooms, permissions)
  - `name`: Tên quyền
  - `description`: Mô tả quyền

### 3. **role_permissions** - Liên kết vai trò và quyền
- **Mục đích**: Gán quyền cho từng vai trò
- **Các trường chính**:
  - `id`: ID tự tăng
  - `role_id`: ID vai trò (FK → roles)
  - `permission_id`: ID quyền (FK → permissions)
- **Ràng buộc**: UNIQUE(role_id, permission_id)

### 4. **customers** - Khách hàng
- **Mục đích**: Lưu thông tin khách hàng
- **Các trường chính**:
  - `id`: ID tự tăng
  - `name`: Tên khách hàng
  - `phone`: Số điện thoại
  - `email`: Email
  - `address`: Địa chỉ
  - `total_purchased`: Tổng giá trị đã mua (VND)
  - `total_debt`: Tổng công nợ hiện tại (VND)
  - `notes`: Ghi chú

### 5. **showrooms** - Phòng trưng bày
- **Mục đích**: Quản lý các phòng trưng bày
- **Các trường chính**:
  - `id`: ID tự tăng
  - `code`: Mã phòng (SR01, SR02...) - UNIQUE
  - `name`: Tên phòng trưng bày
  - `phone`: Số điện thoại
  - `address`: Địa chỉ
  - `bank_name`: Tên ngân hàng
  - `bank_account`: Số tài khoản
  - `bank_holder`: Chủ tài khoản
  - `logo`: Logo phòng
  - `is_active`: Trạng thái hoạt động

### 6. **paintings** - Tranh
- **Mục đích**: Quản lý thông tin tranh
- **Các trường chính**:
  - `id`: ID tự tăng
  - `code`: Mã tranh - UNIQUE
  - `name`: Tên tranh / Tác tranh
  - `artist`: Họa sĩ
  - `material`: Chất liệu (sơn dầu, canvas, thủy mặc...)
  - `width`: Chiều rộng (cm)
  - `height`: Chiều cao (cm)
  - `paint_year`: Năm sản xuất
  - `price_usd`: Giá bán (USD)
  - `price_vnd`: Giá bán (VND)
  - `image`: Ảnh tranh
  - `quantity`: Số lượng tồn kho
  - `import_date`: Ngày nhập kho
  - `export_date`: Ngày xuất kho
  - `status`: Trạng thái (in_stock, sold, reserved)

### 7. **supplies** - Vật tư (Khung tranh)
- **Mục đích**: Quản lý vật tư khung tranh
- **Các trường chính**:
  - `id`: ID tự tăng
  - `code`: Mã vật tư - UNIQUE
  - `name`: Tên vật tư
  - `type`: Loại vật tư (frame, canvas, other)
  - `unit`: Đơn vị tính (m, cm, cái)
  - `quantity`: Số lượng tồn kho
  - `min_quantity`: Số lượng tối thiểu (cảnh báo)

### 8. **sales** - Hóa đơn bán hàng
- **Mục đích**: Lưu thông tin hóa đơn bán hàng
- **Các trường chính**:
  - `id`: ID tự tăng
  - `invoice_code`: Mã hóa đơn (HD001, HD002...) - UNIQUE
  - `customer_id`: ID khách hàng (FK → customers)
  - `showroom_id`: ID phòng trưng bày (FK → showrooms)
  - `user_id`: ID nhân viên bán hàng (FK → users)
  - `sale_date`: Ngày bán
  - `exchange_rate`: Tỷ giá USD/VND tại thời điểm bán
  - `subtotal_usd`: Tạm tính (USD)
  - `subtotal_vnd`: Tạm tính (VND)
  - `discount_percent`: Giảm giá (%)
  - `discount_usd`: Số tiền giảm (USD)
  - `discount_vnd`: Số tiền giảm (VND)
  - `total_usd`: Tổng cộng (USD)
  - `total_vnd`: Tổng cộng (VND)
  - `paid_amount`: Số tiền đã thanh toán (VND)
  - `debt_amount`: Số tiền còn nợ (VND)
  - `payment_status`: Trạng thái thanh toán (unpaid, partial, paid)

### 9. **sale_items** - Chi tiết hóa đơn
- **Mục đích**: Lưu chi tiết sản phẩm trong hóa đơn
- **Các trường chính**:
  - `id`: ID tự tăng
  - `sale_id`: ID hóa đơn (FK → sales)
  - `painting_id`: ID tranh (FK → paintings)
  - `description`: Mô tả sản phẩm
  - `quantity`: Số lượng
  - `supply_id`: ID vật tư khung sử dụng (FK → supplies)
  - `supply_length`: Số mét khung sử dụng cho 1 sản phẩm
  - `currency`: Loại tiền (USD, VND)
  - `price_usd`: Giá bán (USD)
  - `price_vnd`: Giá bán (VND)
  - `total_usd`: Thành tiền (USD)
  - `total_vnd`: Thành tiền (VND)

### 10. **payments** - Thanh toán
- **Mục đích**: Ghi nhận các lần thanh toán
- **Các trường chính**:
  - `id`: ID tự tăng
  - `sale_id`: ID hóa đơn (FK → sales)
  - `amount`: Số tiền thanh toán (VND)
  - `payment_method`: Phương thức thanh toán (cash, bank_transfer, card, other)
  - `payment_date`: Ngày thanh toán
  - `notes`: Ghi chú
  - `created_by`: Người tạo (FK → users)

### 11. **debts** - Công nợ
- **Mục đích**: Theo dõi công nợ khách hàng
- **Các trường chính**:
  - `id`: ID tự tăng
  - `sale_id`: ID hóa đơn (FK → sales)
  - `customer_id`: ID khách hàng (FK → customers)
  - `total_amount`: Tổng tiền hóa đơn (VND)
  - `paid_amount`: Số tiền đã trả (VND)
  - `debt_amount`: Số tiền còn nợ (VND)
  - `due_date`: Ngày đến hạn thanh toán
  - `status`: Trạng thái (pending, overdue, paid)

### 12. **returns** - Đổi/Trả hàng
- **Mục đích**: Xử lý đổi/trả hàng
- **Các trường chính**:
  - `id`: ID tự tăng
  - `return_code`: Mã phiếu trả (RT001, RT002...) - UNIQUE
  - `sale_id`: ID hóa đơn gốc (FK → sales)
  - `customer_id`: ID khách hàng (FK → customers)
  - `return_date`: Ngày trả hàng
  - `total_refund`: Tổng tiền hoàn (VND)
  - `reason`: Lý do trả hàng
  - `status`: Trạng thái (pending, completed, cancelled)
  - `processed_by`: Người xử lý (FK → users)

### 13. **inventory_transactions** - Lịch sử nhập/xuất kho
- **Mục đích**: Theo dõi lịch sử nhập/xuất kho
- **Các trường chính**:
  - `id`: ID tự tăng
  - `transaction_type`: Loại giao dịch (import, export, return, adjustment)
  - `item_type`: Loại sản phẩm (painting, supply)
  - `item_id`: ID sản phẩm
  - `quantity`: Số lượng
  - `reference_type`: Loại tham chiếu (sale, return, manual)
  - `reference_id`: ID tham chiếu
  - `transaction_date`: Ngày giao dịch
  - `created_by`: Người thực hiện (FK → users)

### 14. **exchange_rates** - Tỷ giá USD/VND
- **Mục đích**: Lưu lịch sử tỷ giá
- **Các trường chính**:
  - `id`: ID tự tăng
  - `rate`: Tỷ giá (1 USD = ? VND)
  - `effective_date`: Ngày áp dụng
  - `notes`: Ghi chú
  - `created_by`: Người tạo (FK → users)

### 15. **users** - Người dùng (Đã có sẵn)
- **Mục đích**: Quản lý người dùng hệ thống
- **Các trường chính**: Sử dụng bảng users mặc định của Laravel

## 🔗 Mối quan hệ giữa các bảng

```
users
├── sales (user_id)
├── payments (created_by)
├── returns (processed_by)
├── inventory_transactions (created_by)
└── exchange_rates (created_by)

roles
└── role_permissions (role_id)
    └── permissions (permission_id)

customers
├── sales (customer_id)
├── debts (customer_id)
└── returns (customer_id)

showrooms
└── sales (showroom_id)

paintings
├── sale_items (painting_id)
└── inventory_transactions (item_id where item_type='painting')

supplies
├── sale_items (supply_id)
└── inventory_transactions (item_id where item_type='supply')

sales
├── sale_items (sale_id)
├── payments (sale_id)
├── debts (sale_id)
└── returns (sale_id)
```

## 📋 Indexes đã tạo

### Primary Keys
- Tất cả 15 bảng đều có `id` AUTO_INCREMENT PRIMARY KEY

### Foreign Keys
- **role_permissions**: role_id, permission_id
- **sales**: customer_id, showroom_id, user_id
- **sale_items**: sale_id, painting_id, supply_id
- **payments**: sale_id, created_by
- **debts**: sale_id, customer_id
- **returns**: sale_id, customer_id, processed_by
- **inventory_transactions**: created_by
- **exchange_rates**: created_by

### Unique Keys
- **roles**: name
- **permissions**: module
- **role_permissions**: (role_id, permission_id)
- **customers**: -
- **showrooms**: code
- **paintings**: code
- **supplies**: code
- **sales**: invoice_code
- **returns**: return_code

### Search Indexes
- **customers**: phone, name, total_debt
- **showrooms**: code, is_active
- **paintings**: code, status, artist, material
- **supplies**: code, type, quantity
- **sales**: invoice_code, customer_id, showroom_id, sale_date, payment_status
- **sale_items**: sale_id, painting_id
- **payments**: sale_id, payment_date
- **debts**: sale_id, customer_id, status, due_date
- **returns**: return_code, sale_id, customer_id, return_date, status
- **inventory_transactions**: (item_type, item_id), transaction_type, transaction_date, (reference_type, reference_id)
- **exchange_rates**: effective_date

## 💾 Dung lượng ước tính

| Bảng | Ước tính số dòng/năm | Dung lượng/năm |
|------|---------------------|----------------|
| users | 10-50 | < 1 MB |
| roles | 5-10 | < 1 MB |
| permissions | 7 | < 1 MB |
| role_permissions | 20-50 | < 1 MB |
| customers | 500-1000 | 1-2 MB |
| showrooms | 5-10 | < 1 MB |
| paintings | 1000-5000 | 5-10 MB |
| supplies | 50-200 | < 1 MB |
| sales | 1000-5000 | 10-20 MB |
| sale_items | 2000-10000 | 20-40 MB |
| payments | 2000-10000 | 10-20 MB |
| debts | 500-2000 | 5-10 MB |
| returns | 100-500 | 2-5 MB |
| inventory_transactions | 5000-20000 | 20-50 MB |
| exchange_rates | 50-100 | < 1 MB |
| **TỔNG** | | **~100-200 MB/năm** |

## ✅ Trạng thái Migration

```bash
✅ 2025_10_08_022025_create_roles_table
✅ 2025_10_08_022045_create_permissions_table
✅ 2025_10_08_022303_create_role_permissions_table
✅ 2025_10_08_022304_create_customers_table
✅ 2025_10_08_022304_create_showrooms_table
✅ 2025_10_08_022305_create_paintings_table
✅ 2025_10_08_022305_create_supplies_table
✅ 2025_10_08_022306_create_sales_table
✅ 2025_10_08_022306_create_sale_items_table
✅ 2025_10_08_022307_create_payments_table
✅ 2025_10_08_022307_create_debts_table
✅ 2025_10_08_022308_create_returns_table
✅ 2025_10_08_022308_create_inventory_transactions_table
✅ 2025_10_08_022309_create_exchange_rates_table
```

## 🎯 Bước tiếp theo

1. ✅ **Database đã được tạo thành công**
2. ⏳ **Tạo Models cho từng bảng**
3. ⏳ **Tạo Seeders để insert dữ liệu mẫu**
4. ⏳ **Cập nhật Controllers để sử dụng database thực**
5. ⏳ **Tạo API endpoints**
6. ⏳ **Testing**

---

**Tạo bởi**: Kiro AI Assistant  
**Ngày tạo**: 08/10/2025  
**Trạng thái**: ✅ Hoàn thành
