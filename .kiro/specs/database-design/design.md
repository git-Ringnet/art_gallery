# Database Design - Hệ thống Quản lý Tranh & Khung

## Overview

Thiết kế database cho hệ thống quản lý tranh và khung tranh với 15 bảng chính, hỗ trợ đầy đủ các chức năng nghiệp vụ.

## Database Schema

### 1. users - Người dùng hệ thống
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Tên người dùng',
    email VARCHAR(255) UNIQUE NOT NULL COMMENT 'Email đăng nhập',
    password VARCHAR(255) NOT NULL COMMENT 'Mật khẩu đã mã hóa',
    role_id BIGINT UNSIGNED COMMENT 'ID vai trò',
    phone VARCHAR(20) COMMENT 'Số điện thoại',
    avatar VARCHAR(255) COMMENT 'Ảnh đại diện',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Trạng thái hoạt động',
    last_login_at TIMESTAMP NULL COMMENT 'Lần đăng nhập cuối',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_role (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng người dùng';
```

### 2. roles - Vai trò
```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL COMMENT 'Tên vai trò (Admin, Nhân viên bán hàng, Thủ kho)',
    description TEXT COMMENT 'Mô tả vai trò',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng vai trò';
```

### 3. permissions - Quyền truy cập
```sql
CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(50) NOT NULL COMMENT 'Tên module (dashboard, sales, debt, returns, inventory, showrooms, permissions)',
    name VARCHAR(100) NOT NULL COMMENT 'Tên quyền',
    description TEXT COMMENT 'Mô tả quyền',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_module (module),
    INDEX idx_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng quyền truy cập module';
```

### 4. role_permissions - Liên kết vai trò và quyền
```sql
CREATE TABLE role_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL COMMENT 'ID vai trò',
    permission_id BIGINT UNSIGNED NOT NULL COMMENT 'ID quyền',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    INDEX idx_role (role_id),
    INDEX idx_permission (permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng liên kết vai trò và quyền';
```

### 5. customers - Khách hàng
```sql
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Tên khách hàng',
    phone VARCHAR(20) NOT NULL COMMENT 'Số điện thoại',
    email VARCHAR(255) COMMENT 'Email',
    address TEXT COMMENT 'Địa chỉ',
    total_purchased DECIMAL(15,2) DEFAULT 0 COMMENT 'Tổng giá trị đã mua (VND)',
    total_debt DECIMAL(15,2) DEFAULT 0 COMMENT 'Tổng công nợ hiện tại (VND)',
    notes TEXT COMMENT 'Ghi chú',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_phone (phone),
    INDEX idx_name (name),
    INDEX idx_debt (total_debt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng khách hàng';
```

### 6. showrooms - Phòng trưng bày
```sql
CREATE TABLE showrooms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Mã phòng (SR01, SR02...)',
    name VARCHAR(255) NOT NULL COMMENT 'Tên phòng trưng bày',
    phone VARCHAR(20) COMMENT 'Số điện thoại',
    address TEXT COMMENT 'Địa chỉ',
    bank_name VARCHAR(100) COMMENT 'Tên ngân hàng',
    bank_account VARCHAR(50) COMMENT 'Số tài khoản',
    bank_holder VARCHAR(255) COMMENT 'Chủ tài khoản',
    logo VARCHAR(255) COMMENT 'Logo phòng trưng bày',
    notes TEXT COMMENT 'Ghi chú',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Trạng thái hoạt động',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng phòng trưng bày';
```

### 7. paintings - Tranh
```sql
CREATE TABLE paintings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Mã tranh',
    name VARCHAR(255) NOT NULL COMMENT 'Tên tranh / Tác tranh',
    artist VARCHAR(255) NOT NULL COMMENT 'Họa sĩ',
    material VARCHAR(100) NOT NULL COMMENT 'Chất liệu (sơn dầu, canvas, thủy mặc...)',
    width DECIMAL(8,2) COMMENT 'Chiều rộng (cm)',
    height DECIMAL(8,2) COMMENT 'Chiều cao (cm)',
    paint_year VARCHAR(20) COMMENT 'Năm sản xuất',
    price_usd DECIMAL(10,2) NOT NULL COMMENT 'Giá bán (USD)',
    price_vnd DECIMAL(15,2) COMMENT 'Giá bán (VND) - tính theo tỷ giá',
    image VARCHAR(255) COMMENT 'Ảnh tranh',
    quantity INT DEFAULT 1 COMMENT 'Số lượng tồn kho',
    import_date DATE COMMENT 'Ngày nhập kho',
    export_date DATE COMMENT 'Ngày xuất kho (dự kiến)',
    notes TEXT COMMENT 'Ghi chú',
    status ENUM('in_stock', 'sold', 'reserved') DEFAULT 'in_stock' COMMENT 'Trạng thái',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (code),
    INDEX idx_status (status),
    INDEX idx_artist (artist),
    INDEX idx_material (material)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng tranh';
```

### 8. supplies - Vật tư (Khung tranh)
```sql
CREATE TABLE supplies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Mã vật tư',
    name VARCHAR(255) NOT NULL COMMENT 'Tên vật tư',
    type ENUM('frame', 'canvas', 'other') DEFAULT 'frame' COMMENT 'Loại vật tư',
    unit VARCHAR(20) NOT NULL COMMENT 'Đơn vị tính (m, cm, cái)',
    quantity DECIMAL(10,2) DEFAULT 0 COMMENT 'Số lượng tồn kho',
    min_quantity DECIMAL(10,2) DEFAULT 0 COMMENT 'Số lượng tối thiểu (cảnh báo)',
    notes TEXT COMMENT 'Ghi chú',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (code),
    INDEX idx_type (type),
    INDEX idx_quantity (quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng vật tư (khung tranh)';
```

### 9. sales - Hóa đơn bán hàng
```sql
CREATE TABLE sales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Mã hóa đơn (HD001, HD002...)',
    customer_id BIGINT UNSIGNED NOT NULL COMMENT 'ID khách hàng',
    showroom_id BIGINT UNSIGNED COMMENT 'ID phòng trưng bày',
    user_id BIGINT UNSIGNED COMMENT 'ID nhân viên bán hàng',
    sale_date DATE NOT NULL COMMENT 'Ngày bán',
    exchange_rate DECIMAL(10,2) NOT NULL COMMENT 'Tỷ giá USD/VND tại thời điểm bán',
    subtotal_usd DECIMAL(10,2) DEFAULT 0 COMMENT 'Tạm tính (USD)',
    subtotal_vnd DECIMAL(15,2) DEFAULT 0 COMMENT 'Tạm tính (VND)',
    discount_percent DECIMAL(5,2) DEFAULT 0 COMMENT 'Giảm giá (%)',
    discount_usd DECIMAL(10,2) DEFAULT 0 COMMENT 'Số tiền giảm (USD)',
    discount_vnd DECIMAL(15,2) DEFAULT 0 COMMENT 'Số tiền giảm (VND)',
    total_usd DECIMAL(10,2) NOT NULL COMMENT 'Tổng cộng (USD)',
    total_vnd DECIMAL(15,2) NOT NULL COMMENT 'Tổng cộng (VND)',
    paid_amount DECIMAL(15,2) DEFAULT 0 COMMENT 'Số tiền đã thanh toán (VND)',
    debt_amount DECIMAL(15,2) DEFAULT 0 COMMENT 'Số tiền còn nợ (VND)',
    payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid' COMMENT 'Trạng thái thanh toán',
    notes TEXT COMMENT 'Ghi chú',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (showroom_id) REFERENCES showrooms(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_invoice_code (invoice_code),
    INDEX idx_customer (customer_id),
    INDEX idx_showroom (showroom_id),
    INDEX idx_sale_date (sale_date),
    INDEX idx_payment_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng hóa đơn bán hàng';
```

### 10. sale_items - Chi tiết hóa đơn
```sql
CREATE TABLE sale_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL COMMENT 'ID hóa đơn',
    painting_id BIGINT UNSIGNED COMMENT 'ID tranh',
    description TEXT COMMENT 'Mô tả sản phẩm',
    quantity INT NOT NULL DEFAULT 1 COMMENT 'Số lượng',
    supply_id BIGINT UNSIGNED COMMENT 'ID vật tư khung sử dụng',
    supply_length DECIMAL(8,2) DEFAULT 0 COMMENT 'Số mét khung sử dụng cho 1 sản phẩm',
    currency ENUM('USD', 'VND') DEFAULT 'USD' COMMENT 'Loại tiền',
    price_usd DECIMAL(10,2) COMMENT 'Giá bán (USD)',
    price_vnd DECIMAL(15,2) COMMENT 'Giá bán (VND)',
    total_usd DECIMAL(10,2) COMMENT 'Thành tiền (USD)',
    total_vnd DECIMAL(15,2) COMMENT 'Thành tiền (VND)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (painting_id) REFERENCES paintings(id) ON DELETE SET NULL,
    FOREIGN KEY (supply_id) REFERENCES supplies(id) ON DELETE SET NULL,
    INDEX idx_sale (sale_id),
    INDEX idx_painting (painting_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng chi tiết hóa đơn';
```

### 11. payments - Thanh toán
```sql
CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL COMMENT 'ID hóa đơn',
    amount DECIMAL(15,2) NOT NULL COMMENT 'Số tiền thanh toán (VND)',
    payment_method ENUM('cash', 'bank_transfer', 'card', 'other') DEFAULT 'cash' COMMENT 'Phương thức thanh toán',
    payment_date DATE NOT NULL COMMENT 'Ngày thanh toán',
    notes TEXT COMMENT 'Ghi chú',
    created_by BIGINT UNSIGNED COMMENT 'Người tạo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sale (sale_id),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng thanh toán';
```

### 12. debts - Công nợ
```sql
CREATE TABLE debts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL COMMENT 'ID hóa đơn',
    customer_id BIGINT UNSIGNED NOT NULL COMMENT 'ID khách hàng',
    total_amount DECIMAL(15,2) NOT NULL COMMENT 'Tổng tiền hóa đơn (VND)',
    paid_amount DECIMAL(15,2) DEFAULT 0 COMMENT 'Số tiền đã trả (VND)',
    debt_amount DECIMAL(15,2) NOT NULL COMMENT 'Số tiền còn nợ (VND)',
    due_date DATE COMMENT 'Ngày đến hạn thanh toán',
    status ENUM('pending', 'overdue', 'paid') DEFAULT 'pending' COMMENT 'Trạng thái',
    notes TEXT COMMENT 'Ghi chú',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_sale (sale_id),
    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng công nợ';
```

### 13. returns - Đổi/Trả hàng
```sql
CREATE TABLE returns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    return_code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Mã phiếu trả (RT001, RT002...)',
    sale_id BIGINT UNSIGNED NOT NULL COMMENT 'ID hóa đơn gốc',
    customer_id BIGINT UNSIGNED NOT NULL COMMENT 'ID khách hàng',
    return_date DATE NOT NULL COMMENT 'Ngày trả hàng',
    total_refund DECIMAL(15,2) NOT NULL COMMENT 'Tổng tiền hoàn (VND)',
    reason TEXT COMMENT 'Lý do trả hàng',
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending' COMMENT 'Trạng thái',
    processed_by BIGINT UNSIGNED COMMENT 'Người xử lý',
    notes TEXT COMMENT 'Ghi chú',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE RESTRICT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_return_code (return_code),
    INDEX idx_sale (sale_id),
    INDEX idx_customer (customer_id),
    INDEX idx_return_date (return_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng đổi/trả hàng';
```

### 14. inventory_transactions - Lịch sử nhập/xuất kho
```sql
CREATE TABLE inventory_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_type ENUM('import', 'export', 'return', 'adjustment') NOT NULL COMMENT 'Loại giao dịch',
    item_type ENUM('painting', 'supply') NOT NULL COMMENT 'Loại sản phẩm',
    item_id BIGINT UNSIGNED NOT NULL COMMENT 'ID sản phẩm (painting_id hoặc supply_id)',
    quantity DECIMAL(10,2) NOT NULL COMMENT 'Số lượng',
    reference_type VARCHAR(50) COMMENT 'Loại tham chiếu (sale, return, manual)',
    reference_id BIGINT UNSIGNED COMMENT 'ID tham chiếu',
    transaction_date DATE NOT NULL COMMENT 'Ngày giao dịch',
    notes TEXT COMMENT 'Ghi chú',
    created_by BIGINT UNSIGNED COMMENT 'Người thực hiện',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_item (item_type, item_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_reference (reference_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng lịch sử nhập/xuất kho';
```

### 15. exchange_rates - Tỷ giá USD/VND
```sql
CREATE TABLE exchange_rates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rate DECIMAL(10,2) NOT NULL COMMENT 'Tỷ giá (1 USD = ? VND)',
    effective_date DATE NOT NULL COMMENT 'Ngày áp dụng',
    notes TEXT COMMENT 'Ghi chú',
    created_by BIGINT UNSIGNED COMMENT 'Người t��o',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_effective_date (effective_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng tỷ giá USD/VND';
```

## Relationships Diagram

```
users ──┬─── sales (user_id)
        ├─── payments (created_by)
        ├─── returns (processed_by)
        ├─── inventory_transactions (created_by)
        └─── exchange_rates (created_by)

roles ──── role_permissions ──── permissions

customers ──┬─── sales (customer_id)
            ├─── debts (customer_id)
            └─── returns (customer_id)

showrooms ──── sales (showroom_id)

paintings ──┬─── sale_items (painting_id)
            └─── inventory_transactions (item_id where item_type='painting')

supplies ──┬─── sale_items (supply_id)
           └─── inventory_transactions (item_id where item_type='supply')

sales ──┬─── sale_items (sale_id)
        ├─── payments (sale_id)
        ├─── debts (sale_id)
        └─── returns (sale_id)
```

## Indexes Strategy

- **Primary Keys**: Tất cả bảng đều có AUTO_INCREMENT PRIMARY KEY
- **Foreign Keys**: Đảm bảo tính toàn vẹn dữ liệu
- **Unique Keys**: Các mã code (invoice_code, return_code, painting code, supply code)
- **Search Indexes**: Các trường thường xuyên tìm kiếm (name, phone, date, status)
- **Composite Indexes**: role_permissions (role_id, permission_id)

## Data Types Rationale

- **DECIMAL**: Dùng cho tiền tệ để tránh lỗi làm tròn
- **ENUM**: Dùng cho các trường có giá trị cố định
- **TEXT**: Dùng cho các trường mô tả, ghi chú
- **TIMESTAMP**: Tự động cập nhật created_at, updated_at
- **BOOLEAN**: Dùng cho trạng thái true/false

## Performance Considerations

1. **Indexes**: Đánh index cho các trường thường xuyên query
2. **Foreign Keys**: Sử dụng ON DELETE CASCADE/RESTRICT/SET NULL phù hợp
3. **Partitioning**: Có thể partition bảng sales, payments theo năm nếu dữ liệu lớn
4. **Caching**: Cache tỷ giá hiện tại, thông tin showrooms
