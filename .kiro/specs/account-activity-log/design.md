# Design Document - Account Activity Log System

## Overview

The Account Activity Log System is designed to provide comprehensive audit trail functionality for the painting gallery management system. It will automatically capture and store all significant user activities, providing administrators with visibility into system usage, security monitoring capabilities, and compliance support.

The system will be implemented as a Laravel-based module that integrates seamlessly with existing controllers and models. It will use event listeners and middleware to automatically capture activities without requiring extensive code changes throughout the application.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Application Layer                        │
│  (Controllers, Models, Services)                            │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│              Activity Logging Middleware                     │
│  - Capture HTTP Request Context                             │
│  - Extract IP, User Agent                                   │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│              Activity Logger Service                         │
│  - Log Activity API                                         │
│  - Change Detection                                         │
│  - Suspicious Activity Detection                            │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│              ActivityLog Model                               │
│  - Database Persistence                                     │
│  - Query Scopes                                             │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│              Database (activity_logs table)                  │
└─────────────────────────────────────────────────────────────┘
```

### Component Interaction Flow

1. **User Action**: User performs an action (login, create sale, update customer, etc.)
2. **Controller**: Processes the request and calls ActivityLogger service
3. **ActivityLogger Service**: 
   - Captures context (user, IP, user agent)
   - Detects changes (for update operations)
   - Creates log entry
   - Checks for suspicious patterns
4. **ActivityLog Model**: Persists data to database
5. **View Layer**: Administrators can view, filter, and export logs

## Components and Interfaces

### 1. ActivityLog Model

**File**: `app/Models/ActivityLog.php`

**Responsibilities**:
- Represent activity log entries in the database
- Provide query scopes for filtering
- Define relationships with User model
- Handle polymorphic relationship with subject models

**Key Methods**:
```php
// Query scopes
public function scopeByUser($query, $userId)
public function scopeByActivityType($query, $type)
public function scopeByModule($query, $module)
public function scopeByDateRange($query, $from, $to)
public function scopeByIpAddress($query, $ip)
public function scopeSuspicious($query)

// Relationships
public function user()
public function subject()

// Accessors
public function getPropertiesAttribute($value)
public function getChangesAttribute($value)
```

### 2. ActivityLogger Service

**File**: `app/Services/ActivityLogger.php`

**Responsibilities**:
- Provide simple API for logging activities
- Automatically capture request context
- Detect and track changes for update operations
- Identify suspicious activity patterns
- Handle logging failures gracefully

**Key Methods**:
```php
public function log(string $activityType, string $module, ?Model $subject = null, array $properties = [])
public function logLogin(User $user)
public function logLogout(User $user, $sessionDuration)
public function logCreate(string $module, Model $subject)
public function logUpdate(string $module, Model $subject, array $changes)
public function logDelete(string $module, Model $subject, array $deletedData)
public function logApprove(string $module, Model $subject, ?string $reason = null)
public function logCancel(string $module, Model $subject, ?string $reason = null)
private function captureContext()
private function detectChanges(Model $model)
private function checkSuspiciousActivity(string $activityType, string $module)
```

### 3. ActivityLogController

**File**: `app/Http/Controllers/ActivityLogController.php`

**Responsibilities**:
- Handle HTTP requests for viewing activity logs
- Implement filtering and search functionality
- Generate exports (Excel, PDF)
- Display log details

**Key Methods**:
```php
public function index(Request $request)
public function show($id)
public function myActivity(Request $request)
public function exportExcel(Request $request)
public function exportPdf(Request $request)
```

### 4. LogActivity Middleware

**File**: `app/Http/Middleware/LogActivity.php`

**Responsibilities**:
- Automatically log certain HTTP requests
- Capture request/response context
- Handle authentication events

**Key Methods**:
```php
public function handle(Request $request, Closure $next)
private function shouldLog(Request $request)
```

### 5. CleanupOldLogs Command

**File**: `app/Console/Commands/CleanupOldLogs.php`

**Responsibilities**:
- Delete activity logs older than retention period
- Preserve important/security-related logs
- Log cleanup statistics

**Key Methods**:
```php
public function handle()
private function getRetentionPeriod()
private function deleteOldLogs()
```

## Data Models

### ActivityLog Table Schema

```sql
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    activity_type VARCHAR(50) NOT NULL,
    module VARCHAR(50) NOT NULL,
    description TEXT NULL,
    subject_type VARCHAR(255) NULL,
    subject_id BIGINT UNSIGNED NULL,
    properties JSON NULL,
    changes JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    is_suspicious BOOLEAN DEFAULT FALSE,
    is_important BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_module (module),
    INDEX idx_subject (subject_type, subject_id),
    INDEX idx_created_at (created_at),
    INDEX idx_ip_address (ip_address),
    INDEX idx_suspicious (is_suspicious),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

**Field Descriptions**:
- `user_id`: ID of the user who performed the action (nullable for system actions)
- `activity_type`: Type of activity (login, logout, create, update, delete, approve, cancel, view)
- `module`: Module where activity occurred (sales, customers, inventory, employees, etc.)
- `description`: Human-readable description of the activity
- `subject_type`: Model class name of the affected resource (polymorphic)
- `subject_id`: ID of the affected resource (polymorphic)
- `properties`: Additional data about the activity (JSON)
- `changes`: For update operations, stores old and new values (JSON)
- `ip_address`: IP address of the user
- `user_agent`: Browser/device information
- `is_suspicious`: Flag for suspicious activities
- `is_important`: Flag to prevent deletion during cleanup
- `created_at`: Timestamp when activity occurred
- `updated_at`: Timestamp when log entry was last updated

### Activity Types

```php
const ACTIVITY_TYPES = [
    'login' => 'Đăng nhập',
    'logout' => 'Đăng xuất',
    'create' => 'Tạo mới',
    'update' => 'Cập nhật',
    'delete' => 'Xóa',
    'approve' => 'Duyệt',
    'cancel' => 'Hủy',
    'view' => 'Xem',
    'export' => 'Xuất dữ liệu',
    'import' => 'Nhập dữ liệu',
];
```

### Modules

```php
const MODULES = [
    'auth' => 'Xác thực',
    'sales' => 'Bán hàng',
    'customers' => 'Khách hàng',
    'inventory' => 'Kho hàng',
    'employees' => 'Nhân viên',
    'showrooms' => 'Showroom',
    'payments' => 'Thanh toán',
    'debts' => 'Công nợ',
    'returns' => 'Trả hàng',
    'reports' => 'Báo cáo',
    'permissions' => 'Phân quyền',
    'settings' => 'Cài đặt',
];
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Activity logging completeness

*For any* significant user action (create, update, delete, approve, cancel), when that action completes successfully, the system should have created exactly one corresponding activity log entry with all required fields populated (user_id, activity_type, module, timestamp).

**Validates: Requirements 1.1, 1.4, 1.5, 1.6, 1.7**

### Property 2: Login/logout event recording

*For any* successful user login, the system should create a log entry with activity_type='login', and for any logout, the system should create a log entry with activity_type='logout'.

**Validates: Requirements 1.2, 1.3**

### Property 3: Change tracking accuracy

*For any* update operation on a model, when changes are detected, the stored changes JSON should contain all modified fields with their old and new values matching the actual database changes.

**Validates: Requirements 1.5, 3.2**

### Property 4: Context capture consistency

*For any* logged activity, the log entry should contain the IP address and user agent from the HTTP request context at the time of the activity.

**Validates: Requirements 1.1, 1.2, 8.2**

### Property 5: Filter result accuracy

*For any* combination of filters (user, activity type, module, date range, IP address), the returned log entries should match all specified filter criteria.

**Validates: Requirements 2.2, 2.3, 2.4, 2.5, 2.6**

### Property 6: Pagination consistency

*For any* page number and page size, the paginated results should contain the correct subset of log entries in the correct order (most recent first), with no duplicates or missing entries across pages.

**Validates: Requirements 2.1, 7.5**

### Property 7: Export completeness

*For any* export request (Excel or PDF), the exported file should contain all log entries matching the current filters, with all required fields present and properly formatted.

**Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5**

### Property 8: Retention policy enforcement

*For any* configured retention period, when the cleanup task runs, all log entries older than the retention period should be deleted, except those marked as important or suspicious.

**Validates: Requirements 5.2, 5.3**

### Property 9: Suspicious activity detection

*For any* sequence of activities from the same IP address, when multiple failed login attempts occur within a short time window (e.g., 5 attempts in 5 minutes), the system should flag at least one log entry as suspicious.

**Validates: Requirements 6.1**

### Property 10: User activity isolation

*For any* user viewing their own activity history, the returned log entries should contain only that user's activities and should not include sensitive information from other users' logs.

**Validates: Requirements 7.1, 7.4**

### Property 11: Logging failure resilience

*For any* activity logging operation that fails (due to database error, validation error, etc.), the failure should not throw an exception that interrupts the main application flow.

**Validates: Requirements 8.5**

### Property 12: Subject reference integrity

*For any* log entry with a subject_type and subject_id, if the subject still exists in the database, the polymorphic relationship should resolve to the correct model instance.

**Validates: Requirements 3.4**

## Error Handling

### Logging Failures

The ActivityLogger service must handle all errors gracefully to prevent logging failures from affecting the main application:

1. **Database Connection Errors**: Catch and log to Laravel's error log, but don't throw
2. **Validation Errors**: Log warning and continue
3. **JSON Serialization Errors**: Store simplified version or error message
4. **Missing Context**: Use default values (e.g., "Unknown" for IP if not available)

### User-Facing Errors

For the activity log viewing interface:

1. **Invalid Filter Parameters**: Show validation error message, reset to defaults
2. **Export Failures**: Show error message with option to retry
3. **Permission Denied**: Redirect to 403 page with explanation
4. **Record Not Found**: Show 404 page

### Cleanup Command Errors

1. **Database Errors**: Log error and exit with non-zero status code
2. **Configuration Errors**: Log warning and use default retention period
3. **Partial Failures**: Continue processing, log summary of failures

## Testing Strategy

### Unit Tests

Unit tests will verify individual components in isolation:

1. **ActivityLog Model Tests**:
   - Test query scopes return correct filtered results
   - Test relationships are properly defined
   - Test JSON attribute casting works correctly
   - Test accessor methods format data correctly

2. **ActivityLogger Service Tests**:
   - Test each log method creates correct log entry
   - Test change detection identifies all modified fields
   - Test context capture extracts IP and user agent
   - Test suspicious activity detection logic
   - Test error handling doesn't throw exceptions

3. **ActivityLogController Tests**:
   - Test index method applies filters correctly
   - Test pagination works as expected
   - Test export methods generate correct files
   - Test permission checks are enforced

4. **CleanupOldLogs Command Tests**:
   - Test old logs are deleted correctly
   - Test important logs are preserved
   - Test retention period configuration is respected

### Property-Based Tests

Property-based tests will verify universal properties across many randomly generated inputs using PHPUnit with the Eris library for property-based testing:

1. **Property 1 Test**: Generate random user actions, verify log entry is created with all required fields
2. **Property 3 Test**: Generate random model updates, verify changes JSON matches actual changes
3. **Property 5 Test**: Generate random filter combinations, verify all results match filters
4. **Property 6 Test**: Generate random pagination parameters, verify no duplicates or gaps
5. **Property 7 Test**: Generate random export requests, verify all filtered entries are included
6. **Property 8 Test**: Generate random log entries with various ages, verify cleanup respects retention
7. **Property 9 Test**: Generate sequences of login attempts, verify suspicious detection triggers
8. **Property 10 Test**: Generate multi-user activity logs, verify user isolation
9. **Property 11 Test**: Simulate various failure scenarios, verify no exceptions propagate

Each property-based test will run a minimum of 100 iterations with randomly generated test data.

### Integration Tests

Integration tests will verify the system works correctly with real database and HTTP requests:

1. Test activity logging through actual HTTP requests
2. Test middleware captures activities correctly
3. Test event listeners trigger logging
4. Test exports generate valid files
5. Test cleanup command with real database

### Manual Testing Checklist

1. Verify logs appear in real-time as actions are performed
2. Verify all filters work correctly in the UI
3. Verify exports download and open correctly
4. Verify suspicious activity alerts appear
5. Verify user activity history shows correct data
6. Verify performance with large number of log entries

## Implementation Notes

### Performance Considerations

1. **Asynchronous Logging**: Consider using Laravel queues for logging to avoid slowing down requests
2. **Database Indexing**: Ensure all filter columns are properly indexed
3. **Pagination**: Always paginate log results to handle large datasets
4. **Archive Strategy**: Consider archiving very old logs to separate table or file storage

### Security Considerations

1. **Sensitive Data**: Never log passwords, tokens, or other sensitive credentials
2. **Access Control**: Restrict activity log viewing to administrators only
3. **Data Retention**: Comply with data protection regulations (GDPR, etc.)
4. **Audit Trail Integrity**: Prevent modification or deletion of log entries (except automated cleanup)

### Integration Points

The ActivityLogger service should be called from:

1. **AuthenticatedSessionController**: Log login/logout
2. **All CRUD Controllers**: Log create/update/delete operations
3. **SalesController**: Log approve/cancel operations
4. **Custom Middleware**: Log suspicious requests
5. **Model Observers**: Automatically log model events

### Configuration

Add to `config/activitylog.php`:

```php
return [
    'enabled' => env('ACTIVITY_LOG_ENABLED', true),
    'retention_days' => env('ACTIVITY_LOG_RETENTION_DAYS', 365),
    'log_views' => env('ACTIVITY_LOG_VIEWS', false),
    'suspicious_login_attempts' => env('ACTIVITY_LOG_SUSPICIOUS_LOGIN_ATTEMPTS', 5),
    'suspicious_login_window' => env('ACTIVITY_LOG_SUSPICIOUS_LOGIN_WINDOW', 300), // seconds
    'suspicious_delete_threshold' => env('ACTIVITY_LOG_SUSPICIOUS_DELETE_THRESHOLD', 10),
];
```
