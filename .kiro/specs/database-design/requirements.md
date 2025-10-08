# Database Design Requirements - Hệ thống Quản lý Tranh & Khung

## Introduction

Thiết kế cơ sở dữ liệu cho hệ thống quản lý tranh và khung tranh, hỗ trợ đầy đủ các chức năng: bán hàng, quản lý kho, công nợ, đổi/trả hàng, phòng trưng bày và phân quyền.

## Requirements

### Requirement 1: Quản lý người dùng và phân quyền

**User Story:** Là một admin, tôi muốn quản lý người dùng và phân quyền truy cập, để kiểm soát ai có thể làm gì trong hệ thống.

#### Acceptance Criteria

1. WHEN tạo người dùng mới THEN hệ thống SHALL lưu thông tin cơ bản (tên, email, mật khẩu, vai trò)
2. WHEN gán vai trò cho người dùng THEN hệ thống SHALL áp dụng các quyền tương ứng
3. WHEN người dùng đăng nhập THEN hệ thống SHALL kiểm tra quyền truy cập module

### Requirement 2: Quản lý tranh (Paintings)

**User Story:** Là một nhân viên kho, tôi muốn quản lý thông tin tranh, để theo dõi tồn kho và giá bán.

#### Acceptance Criteria

1. WHEN nhập tranh mới THEN hệ thống SHALL lưu thông tin (mã, tên, họa sĩ, chất liệu, kích thước, năm, giá USD, ảnh)
2. WHEN cập nhật tranh THEN hệ thống SHALL lưu lịch sử thay đổi giá
3. WHEN xóa tranh THEN hệ thống SHALL kiểm tra tranh có trong đơn hàng hay không

### Requirement 3: Quản lý vật tư (Supplies - Khung tranh)

**User Story:** Là một nhân viên kho, tôi muốn quản lý vật tư khung tranh, để biết số lượng tồn kho.

#### Acceptance Criteria

1. WHEN nhập vật tư THEN hệ thống SHALL lưu thông tin (mã, tên, loại, đơn vị tính, số lượng)
2. WHEN xuất vật tư THEN hệ thống SHALL trừ số lượng tồn kho
3. WHEN số lượng < mức tối thiểu THEN hệ thống SHALL cảnh báo

### Requirement 4: Quản lý khách hàng

**User Story:** Là một nhân viên bán hàng, tôi muốn lưu thông tin khách hàng, để tra cứu lịch sử mua hàng và công nợ.

#### Acceptance Criteria

1. WHEN tạo khách hàng mới THEN hệ thống SHALL lưu thông tin (tên, SĐT, địa chỉ, email)
2. WHEN khách hàng mua hàng THEN hệ thống SHALL cập nhật tổng giá trị mua
3. WHEN khách hàng có công nợ THEN hệ thống SHALL hiển thị cảnh báo

### Requirement 5: Quản lý phòng trưng bày (Showrooms)

**User Story:** Là một admin, tôi muốn quản lý các phòng trưng bày, để phân bổ hàng hóa và doanh thu.

#### Acceptance Criteria

1. WHEN tạo phòng trưng bày THEN hệ thống SHALL lưu thông tin (mã, tên, địa chỉ, SĐT, thông tin ngân hàng, logo)
2. WHEN gán hóa đơn cho phòng THEN hệ thống SHALL tính doanh thu theo phòng
3. WHEN xóa phòng THEN hệ thống SHALL kiểm tra có hóa đơn liên quan không

### Requirement 6: Quản lý hóa đơn bán hàng (Sales)

**User Story:** Là một nhân viên bán hàng, tôi muốn tạo hóa đơn bán hàng, để ghi nhận giao dịch và tính doanh thu.

#### Acceptance Criteria

1. WHEN tạo hóa đơn THEN hệ thống SHALL lưu thông tin (khách hàng, phòng trưng bày, ngày bán, tỷ giá, giảm giá)
2. WHEN thêm sản phẩm vào hóa đơn THEN hệ thống SHALL tính tổng tiền USD và VND
3. WHEN thanh toán một phần THEN hệ thống SHALL tạo bản ghi công nợ
4. WHEN sử dụng vật tư khung THEN hệ thống SHALL trừ số lượng tồn kho
5. WHEN in hóa đơn THEN hệ thống SHALL hiển thị đầy đủ thông tin USD/VND

### Requirement 7: Quản lý chi tiết hóa đơn (Sale Items)

**User Story:** Là một nhân viên bán hàng, tôi muốn thêm nhiều sản phẩm vào hóa đơn, để bán nhiều tranh cùng lúc.

#### Acceptance Criteria

1. WHEN thêm tranh vào hóa đơn THEN hệ thống SHALL lưu (tranh, số lượng, giá USD, giá VND, vật tư sử dụng, số mét khung)
2. WHEN thay đổi số lượng THEN hệ thống SHALL tính lại tổng tiền
3. WHEN xóa sản phẩm THEN hệ thống SHALL cập nhật lại tổng hóa đơn

### Requirement 8: Quản lý thanh toán (Payments)

**User Story:** Là một nhân viên bán hàng, tôi muốn ghi nhận các lần thanh toán, để theo dõi công nợ.

#### Acceptance Criteria

1. WHEN khách thanh toán THEN hệ thống SHALL lưu (hóa đơn, số tiền, phương thức, ngày thanh toán, ghi chú)
2. WHEN thanh toán đủ THEN hệ thống SHALL cập nhật trạng thái hóa đơn = "Đã thanh toán"
3. WHEN thanh toán một phần THEN hệ thống SHALL tính số tiền còn nợ

### Requirement 9: Quản lý công nợ (Debts)

**User Story:** Là một kế toán, tôi muốn theo dõi công nợ khách hàng, để nhắc nhở thu tiền.

#### Acceptance Criteria

1. WHEN có hóa đơn chưa thanh toán đủ THEN hệ thống SHALL tự động tạo bản ghi công nợ
2. WHEN khách thanh toán THEN hệ thống SHALL cập nhật số tiền còn nợ
3. WHEN quá hạn thanh toán THEN hệ thống SHALL đánh dấu "Quá hạn"

### Requirement 10: Quản lý đổi/trả hàng (Returns)

**User Story:** Là một nhân viên bán hàng, tôi muốn xử lý đổi/trả hàng, để hoàn tiền cho khách.

#### Acceptance Criteria

1. WHEN khách trả hàng THEN hệ thống SHALL lưu (hóa đơn gốc, sản phẩm trả, số lượng, lý do, số tiền hoàn)
2. WHEN xác nhận trả hàng THEN hệ thống SHALL cập nhật lại công nợ
3. WHEN trả hàng THEN hệ thống SHALL cộng lại số lượng vào kho

### Requirement 11: Quản lý lịch sử nhập/xuất kho (Inventory Transactions)

**User Story:** Là một thủ kho, tôi muốn theo dõi lịch sử nhập/xuất, để kiểm soát hàng tồn.

#### Acceptance Criteria

1. WHEN nhập tranh/vật tư THEN hệ thống SHALL ghi nhận (loại, sản phẩm, số lượng, ngày, người thực hiện)
2. WHEN xuất hàng (bán/trả) THEN hệ thống SHALL ghi nhận lịch sử
3. WHEN xem báo cáo THEN hệ thống SHALL hiển thị lịch sử đầy đủ

### Requirement 12: Quản lý tỷ giá (Exchange Rates)

**User Story:** Là một admin, tôi muốn cập nhật tỷ giá USD/VND, để tính toán chính xác.

#### Acceptance Criteria

1. WHEN cập nhật tỷ giá THEN hệ thống SHALL lưu lịch sử thay đổi
2. WHEN tạo hóa đơn THEN hệ thống SHALL sử dụng tỷ giá hiện tại
3. WHEN xem hóa đơn cũ THEN hệ thống SHALL hiển thị tỷ giá tại thời điểm bán

## Database Tables Summary

Hệ thống sẽ có **15 bảng chính**:

1. **users** - Người dùng hệ thống
2. **roles** - Vai trò (Admin, Nhân viên bán hàng, Thủ kho)
3. **permissions** - Quyền truy cập module
4. **role_permissions** - Liên kết vai trò và quyền
5. **customers** - Khách hàng
6. **showrooms** - Phòng trưng bày
7. **paintings** - Tranh
8. **supplies** - Vật tư (khung tranh)
9. **sales** - Hóa đơn bán hàng
10. **sale_items** - Chi tiết hóa đơn
11. **payments** - Thanh toán
12. **debts** - Công nợ
13. **returns** - Đổi/trả hàng
14. **inventory_transactions** - Lịch sử nhập/xuất kho
15. **exchange_rates** - Tỷ giá USD/VND
