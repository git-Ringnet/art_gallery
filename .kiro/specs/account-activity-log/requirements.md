# Requirements Document

## Introduction

Hệ thống ghi log các thao tác của account (Activity Log) là một module quan trọng để theo dõi, kiểm tra và kiểm toán các hoạt động của người dùng trong hệ thống quản lý bán tranh. Module này sẽ ghi lại tất cả các thao tác quan trọng như đăng nhập, đăng xuất, tạo/sửa/xóa dữ liệu, duyệt phiếu, và các thay đổi quan trọng khác. Điều này giúp quản trị viên có thể theo dõi lịch sử hoạt động, phát hiện hành vi bất thường, và đảm bảo tính minh bạch trong quản lý.

## Glossary

- **Activity Log System**: Hệ thống ghi log các thao tác của người dùng
- **User**: Người dùng trong hệ thống (nhân viên, quản trị viên)
- **Activity**: Một thao tác cụ thể được thực hiện bởi người dùng
- **Log Entry**: Một bản ghi log chứa thông tin về một activity
- **Subject**: Đối tượng bị tác động bởi activity (ví dụ: Sale, Customer, Product)
- **IP Address**: Địa chỉ IP của người dùng khi thực hiện thao tác
- **User Agent**: Thông tin trình duyệt/thiết bị của người dùng
- **Audit Trail**: Chuỗi các log entries tạo thành lịch sử kiểm toán
- **Activity Type**: Loại thao tác (login, create, update, delete, approve, cancel)
- **Module**: Phân hệ trong hệ thống (sales, customers, inventory, employees, etc.)

## Requirements

### Requirement 1

**User Story:** As a system administrator, I want to automatically log all user activities, so that I can track what actions users perform in the system.

#### Acceptance Criteria

1. WHEN a user performs any significant action THEN the Activity Log System SHALL create a log entry with user information, action type, timestamp, and affected resource
2. WHEN a user logs in successfully THEN the Activity Log System SHALL record the login event with IP address and user agent
3. WHEN a user logs out THEN the Activity Log System SHALL record the logout event with session duration
4. WHEN a user creates a new record THEN the Activity Log System SHALL record the creation event with the record type and identifier
5. WHEN a user updates an existing record THEN the Activity Log System SHALL record the update event with changed fields and old/new values
6. WHEN a user deletes a record THEN the Activity Log System SHALL record the deletion event with the deleted record information
7. WHEN a user approves or cancels a transaction THEN the Activity Log System SHALL record the approval or cancellation event with reason if provided

### Requirement 2

**User Story:** As a system administrator, I want to view activity logs with filtering and search capabilities, so that I can quickly find specific activities or investigate issues.

#### Acceptance Criteria

1. WHEN an administrator accesses the activity log page THEN the Activity Log System SHALL display a paginated list of log entries ordered by most recent first
2. WHEN an administrator searches by user name or email THEN the Activity Log System SHALL return all log entries for that user
3. WHEN an administrator filters by activity type THEN the Activity Log System SHALL return only log entries matching that activity type
4. WHEN an administrator filters by module THEN the Activity Log System SHALL return only log entries for that module
5. WHEN an administrator filters by date range THEN the Activity Log System SHALL return only log entries within that date range
6. WHEN an administrator filters by IP address THEN the Activity Log System SHALL return all log entries from that IP address

### Requirement 3

**User Story:** As a system administrator, I want to see detailed information about each activity, so that I can understand exactly what changes were made.

#### Acceptance Criteria

1. WHEN an administrator views a log entry detail THEN the Activity Log System SHALL display the user who performed the action, timestamp, IP address, and user agent
2. WHEN a log entry is for an update action THEN the Activity Log System SHALL display the old and new values for each changed field
3. WHEN a log entry is for a delete action THEN the Activity Log System SHALL display the deleted record data
4. WHEN a log entry references a subject record THEN the Activity Log System SHALL provide a link to view that record if it still exists
5. WHEN a log entry contains additional properties THEN the Activity Log System SHALL display all properties in a readable format

### Requirement 4

**User Story:** As a system administrator, I want to export activity logs, so that I can perform offline analysis or archive logs for compliance.

#### Acceptance Criteria

1. WHEN an administrator requests to export logs THEN the Activity Log System SHALL generate an Excel file containing all filtered log entries
2. WHEN an administrator requests to export logs THEN the Activity Log System SHALL generate a PDF file containing all filtered log entries
3. WHEN exporting logs THEN the Activity Log System SHALL include all relevant fields: timestamp, user, activity type, module, description, and IP address
4. WHEN exporting logs with date range filter THEN the Activity Log System SHALL include only log entries within that date range
5. WHEN exporting logs THEN the Activity Log System SHALL format the export file with proper headers and readable date/time format

### Requirement 5

**User Story:** As a system administrator, I want the system to automatically clean up old logs, so that the database does not grow indefinitely.

#### Acceptance Criteria

1. WHEN the log retention period is configured THEN the Activity Log System SHALL store that configuration value
2. WHEN a scheduled cleanup task runs THEN the Activity Log System SHALL delete log entries older than the retention period
3. WHEN deleting old logs THEN the Activity Log System SHALL preserve logs marked as important or related to security events
4. WHEN cleanup completes THEN the Activity Log System SHALL log the number of entries deleted
5. WHEN cleanup fails THEN the Activity Log System SHALL log the error without affecting system operation

### Requirement 6

**User Story:** As a system administrator, I want to receive alerts for suspicious activities, so that I can respond quickly to potential security threats.

#### Acceptance Criteria

1. WHEN multiple failed login attempts occur from the same IP within a short time THEN the Activity Log System SHALL flag this as suspicious activity
2. WHEN a user performs an unusually high number of delete operations THEN the Activity Log System SHALL flag this as suspicious activity
3. WHEN a user accesses the system from a new IP address or location THEN the Activity Log System SHALL record this as a notable event
4. WHEN suspicious activity is detected THEN the Activity Log System SHALL create a high-priority log entry
5. WHEN suspicious activity is detected THEN the Activity Log System SHALL optionally send a notification to administrators

### Requirement 7

**User Story:** As a user, I want to view my own activity history, so that I can review my recent actions and verify my work.

#### Acceptance Criteria

1. WHEN a user accesses their activity history page THEN the Activity Log System SHALL display only that user's log entries
2. WHEN displaying user's own logs THEN the Activity Log System SHALL show activity type, module, description, and timestamp
3. WHEN a user views their activity history THEN the Activity Log System SHALL allow filtering by date range and activity type
4. WHEN a user views their activity history THEN the Activity Log System SHALL not display sensitive information like IP addresses of other users
5. WHEN a user views their activity history THEN the Activity Log System SHALL paginate results with 20 entries per page

### Requirement 8

**User Story:** As a developer, I want a simple API to log activities from any part of the application, so that logging can be consistently implemented across all modules.

#### Acceptance Criteria

1. WHEN a developer calls the log activity method THEN the Activity Log System SHALL accept parameters for user, activity type, module, subject, and properties
2. WHEN logging an activity THEN the Activity Log System SHALL automatically capture IP address, user agent, and timestamp
3. WHEN the subject parameter is provided THEN the Activity Log System SHALL store the subject type and identifier
4. WHEN the properties parameter is provided THEN the Activity Log System SHALL serialize and store the properties as JSON
5. WHEN logging fails THEN the Activity Log System SHALL not throw exceptions that would interrupt the main application flow
