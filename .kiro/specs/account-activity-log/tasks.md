# Implementation Plan - Account Activity Log System

- [x] 1. Set up database schema and model


  - Create migration for activity_logs table with all required fields and indexes
  - Create ActivityLog model with relationships, query scopes, and JSON casting
  - _Requirements: 1.1, 8.3, 8.4_

- [ ]* 1.1 Write property test for ActivityLog model query scopes
  - **Property 5: Filter result accuracy**
  - **Validates: Requirements 2.2, 2.3, 2.4, 2.5, 2.6**



- [ ] 2. Implement ActivityLogger service
  - Create ActivityLogger service class with log() method
  - Implement context capture (IP address, user agent, timestamp)
  - Implement specialized logging methods (logLogin, logLogout, logCreate, logUpdate, logDelete, logApprove, logCancel)
  - Implement change detection for update operations
  - Add error handling to prevent exceptions from propagating
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 8.1, 8.2, 8.5_

- [ ]* 2.1 Write property test for activity logging completeness
  - **Property 1: Activity logging completeness**
  - **Validates: Requirements 1.1, 1.4, 1.5, 1.6, 1.7**

- [ ]* 2.2 Write property test for change tracking accuracy
  - **Property 3: Change tracking accuracy**
  - **Validates: Requirements 1.5, 3.2**

- [ ]* 2.3 Write property test for context capture consistency
  - **Property 4: Context capture consistency**
  - **Validates: Requirements 1.1, 1.2, 8.2**

- [x]* 2.4 Write property test for logging failure resilience


  - **Property 11: Logging failure resilience**
  - **Validates: Requirements 8.5**

- [ ] 3. Integrate logging into authentication flow
  - Update AuthenticatedSessionController to log login events
  - Update AuthenticatedSessionController to log logout events with session duration
  - _Requirements: 1.2, 1.3_


- [ ]* 3.1 Write property test for login/logout event recording
  - **Property 2: Login/logout event recording**
  - **Validates: Requirements 1.2, 1.3**

- [x] 4. Integrate logging into CRUD operations

  - Update SalesController to log create/update/delete/approve/cancel operations
  - Update CustomerController to log create/update/delete operations
  - Update EmployeeController to log create/update/delete operations
  - Update InventoryController to log create/update/delete operations
  - Update other controllers as needed
  - _Requirements: 1.4, 1.5, 1.6, 1.7_

- [x] 5. Create ActivityLogController for viewing logs


  - Implement index method with filtering (user, activity type, module, date range, IP address)
  - Implement search functionality
  - Implement pagination (most recent first)
  - Implement show method for log detail view
  - Implement myActivity method for users to view their own logs
  - Add permission checks (admin only for full logs, users for own logs)
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 3.1, 3.2, 3.3, 3.4, 3.5, 7.1, 7.2, 7.3, 7.4_

- [ ]* 5.1 Write property test for pagination consistency
  - **Property 6: Pagination consistency**
  - **Validates: Requirements 2.1, 7.5**

- [ ]* 5.2 Write property test for user activity isolation
  - **Property 10: User activity isolation**
  - **Validates: Requirements 7.1, 7.4**

- [ ]* 5.3 Write property test for subject reference integrity
  - **Property 12: Subject reference integrity**
  - **Validates: Requirements 3.4**

- [x] 6. Create views for activity logs


  - Create index view with filter form and log table
  - Create show view for log detail
  - Create myActivity view for user's own activity history
  - Add Vietnamese translations for all UI text
  - _Requirements: 2.1, 3.1, 7.1, 7.2_

- [x] 7. Implement export functionality


  - Create ActivityLogExport class for Excel export
  - Implement exportExcel method in ActivityLogController
  - Implement exportPdf method in ActivityLogController with PDF view
  - Ensure exports respect current filters
  - Format dates and data properly in exports
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [ ]* 7.1 Write property test for export completeness
  - **Property 7: Export completeness**
  - **Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5**

- [x] 8. Implement suspicious activity detection

  - Add checkSuspiciousActivity method to ActivityLogger service
  - Implement failed login attempt detection
  - Implement excessive delete operation detection
  - Implement new IP address detection
  - Flag suspicious activities in database
  - _Requirements: 6.1, 6.2, 6.3, 6.4_

- [ ]* 8.1 Write property test for suspicious activity detection
  - **Property 9: Suspicious activity detection**
  - **Validates: Requirements 6.1**

- [x] 9. Create cleanup command for old logs


  - Create CleanupOldLogs artisan command
  - Implement retention period configuration
  - Implement deletion logic that preserves important/suspicious logs
  - Log cleanup statistics
  - Add error handling
  - Schedule command in Kernel.php
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ]* 9.1 Write property test for retention policy enforcement
  - **Property 8: Retention policy enforcement**
  - **Validates: Requirements 5.2, 5.3**

- [x] 10. Create configuration file

  - Create config/activitylog.php with all configuration options
  - Add environment variables to .env.example
  - Document configuration options
  - _Requirements: 5.1_

- [x] 11. Add routes and permissions


  - Add routes for activity log viewing and export
  - Add permission checks to routes
  - Update navigation menu to include activity logs link (admin only)
  - _Requirements: 2.1, 4.1, 4.2, 7.1_

- [x] 12. Checkpoint - Ensure all tests pass


  - Ensure all tests pass, ask the user if questions arise.

- [x] 13. Create documentation



  - Document ActivityLogger service API
  - Document configuration options
  - Create user guide for viewing activity logs
  - Document cleanup command usage
  - _Requirements: 8.1_


## Additional Tasks - Complete Logging Integration

- [x] 14. Add logging to InventoryController
  - [x] Add logging to importPainting method (create)
  - [x] Add logging to importSupply method (create)
  - [x] Add logging to importPaintingExcel method (import)
  - [x] Add logging to importSupplyExcel method (import)
  - [x] Add logging to updatePainting method (update)
  - [x] Add logging to updateSupply method (update)
  - [x] Add logging to destroyPainting method (delete)
  - [x] Add logging to destroySupply method (delete)
  - [x] Add logging to bulkDelete method (delete multiple)
  - _Requirements: 1.4, 1.5, 1.6_

- [x] 15. Add logging to ShowroomController
  - [x] Add logging to store method (create)
  - [x] Add logging to update method (update)
  - [x] Add logging to destroy method (delete)
  - _Requirements: 1.4, 1.5, 1.6_

- [x] 16. Add logging to ReturnController
  - [x] Add logging to store method (create return)
  - [x] Add logging to update method (update return)
  - [x] Add logging to approve method (approve return)
  - [x] Add logging to complete method (complete return)
  - [x] Add logging to cancel method (cancel return)
  - [x] Add logging to destroy method (delete return)
  - _Requirements: 1.4, 1.5, 1.6, 1.7_

- [x] 17. Add logging to DebtController
  - [x] Add logging to collect method (collect debt payment)
  - _Requirements: 1.4_

- [x] 18. Add logging to FrameController
  - [x] Add logging to store method (create frame)
  - [x] Add logging to destroy method (delete frame)
  - _Requirements: 1.4, 1.5, 1.6_

- [x] 19. Add logging to PermissionController
  - [x] Add logging to storeRole method (create role)
  - [x] Add logging to updateRole method (update role)
  - [x] Add logging to deleteRole method (delete role)
  - [x] Add logging to updatePermissions method (update permissions)
  - [x] Add logging to updateFieldPermissions method (update field permissions)
  - [x] Add logging to assignRole method (assign role to user)
  - [x] Add logging to storeCustomField method (create custom field)
  - [x] Add logging to deleteCustomField method (delete custom field)
  - _Requirements: 1.4, 1.5, 1.6_

- [x] 20. Add logging to YearDatabaseController
  - [x] Add logging to switchYear method (switch year)
  - [x] Add logging to exportDatabase method (export database)
  - [x] Add logging to exportWithImages method (export with images)
  - [x] Add logging to importDatabase method (import database)
  - [x] Add logging to importWithImages method (import with images)
  - [x] Add logging to cleanupYear method (cleanup year)
  - [x] Add logging to prepareNewYear method (prepare new year)
  - _Requirements: 1.4, 1.5, 1.6_

- [x] 21. Final checkpoint - Test all logging
  - Test logging in all modules
  - Verify all logs appear correctly in activity log view
  - Test filtering and search functionality
  - Test export functionality with real data
  - Ensure all tests pass, ask the user if questions arise.
