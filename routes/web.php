<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\DebtController;

// Auth routes
require __DIR__ . '/auth.php';

// Protected routes - require authentication
// Middleware 'archive.readonly' sẽ block các action POST/PUT/DELETE khi đang xem năm cũ
Route::middleware(['auth', 'archive.readonly'])->group(function () {
    // Route gốc - Controller sẽ xử lý redirect dựa vào quyền
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Dashboard routes
    Route::prefix('dashboard')->name('dashboard.')->middleware('permission:dashboard,can_view')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/stats', [DashboardController::class, 'getStats'])->name('stats');
    });

    // Sales routes
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [SalesController::class, 'index'])->middleware('permission:sales,can_view')->name('index');
        Route::get('/export', [SalesController::class, 'export'])->middleware('permission:sales,can_view')->name('export');
        Route::get('/create', [SalesController::class, 'create'])->middleware('permission:sales,can_create')->name('create');
        Route::post('/', [SalesController::class, 'store'])->middleware('permission:sales,can_create')->name('store');
        Route::get('/{id}', [SalesController::class, 'show'])->middleware('permission:sales,can_view')->name('show');
        Route::get('/{id}/edit', [SalesController::class, 'edit'])->middleware('permission:sales,can_edit')->name('edit');
        Route::put('/{id}', [SalesController::class, 'update'])->middleware('permission:sales,can_edit')->name('update');
        Route::delete('/{id}', [SalesController::class, 'destroy'])->middleware('permission:sales,can_delete')->name('destroy');
        Route::get('/{id}/print', [SalesController::class, 'print'])->middleware('permission:sales,can_print')->name('print');
        Route::post('/{id}/approve', [SalesController::class, 'approve'])->middleware('permission:sales,can_approve')->name('approve');
        Route::post('/{id}/cancel', [SalesController::class, 'cancel'])->middleware('permission:sales,can_cancel')->name('cancel');

        // API routes for search
        Route::get('/api/paintings/{id}', [SalesController::class, 'getPainting'])->name('api.painting');
        Route::get('/api/supplies/{id}', [SalesController::class, 'getSupply'])->name('api.supply');
        Route::get('/api/customers/{id}', [SalesController::class, 'getCustomer'])->name('api.customer');
        Route::get('/api/customers/{id}/debt', [SalesController::class, 'getCustomerDebt'])->name('api.customer.debt');
        Route::get('/api/search/paintings', [SalesController::class, 'searchPaintings'])->name('api.search.paintings');
        Route::get('/api/search/supplies', [SalesController::class, 'searchSupplies'])->name('api.search.supplies');
        Route::get('/api/search/frames', [SalesController::class, 'searchFrames'])->name('api.search.frames');
        Route::get('/api/search/suggestions', [SalesController::class, 'searchSuggestions'])->name('api.search.suggestions');
        Route::get('/api/generate-invoice-code', [SalesController::class, 'generateInvoiceCodeApi'])->name('api.generate-invoice-code');
    });

    // Debt routes
    Route::prefix('debt')->name('debt.')->group(function () {
        Route::get('/', [DebtController::class, 'index'])->middleware('permission:debt,can_view')->name('index');
        Route::get('/api/search/suggestions', [DebtController::class, 'searchSuggestions'])->name('api.search.suggestions');
        Route::get('/export/excel', [DebtController::class, 'exportExcel'])->middleware('permission:debt,can_export')->name('export.excel');
        Route::get('/export/pdf', [DebtController::class, 'exportPdf'])->middleware('permission:debt,can_export')->name('export.pdf');
        Route::get('/{id}', [DebtController::class, 'show'])->middleware('permission:debt,can_view')->name('show');
        Route::post('/{id}/collect', [DebtController::class, 'collect'])->middleware('permission:debt,can_edit')->name('collect');
    });

    // Returns routes
    Route::prefix('returns')->name('returns.')->group(function () {
        Route::get('/', [App\Http\Controllers\ReturnController::class, 'index'])->middleware('permission:returns,can_view')->name('index');
        Route::get('/create', [App\Http\Controllers\ReturnController::class, 'create'])->middleware('permission:returns,can_create')->name('create');
        Route::get('/search-invoice', [App\Http\Controllers\ReturnController::class, 'searchInvoice'])->name('searchInvoice');
        Route::post('/', [App\Http\Controllers\ReturnController::class, 'store'])->middleware('permission:returns,can_create')->name('store');
        Route::get('/{id}', [App\Http\Controllers\ReturnController::class, 'show'])->middleware('permission:returns,can_view')->name('show');
        Route::get('/{id}/edit', [App\Http\Controllers\ReturnController::class, 'edit'])->middleware('permission:returns,can_edit')->name('edit');
        Route::put('/{id}', [App\Http\Controllers\ReturnController::class, 'update'])->middleware('permission:returns,can_edit')->name('update');
        Route::put('/{id}/approve', [App\Http\Controllers\ReturnController::class, 'approve'])->middleware('permission:returns,can_approve')->name('approve');
        Route::put('/{id}/complete', [App\Http\Controllers\ReturnController::class, 'complete'])->middleware('permission:returns,can_edit')->name('complete');
        Route::put('/{id}/cancel', [App\Http\Controllers\ReturnController::class, 'cancel'])->middleware('permission:returns,can_cancel')->name('cancel');
        Route::delete('/{id}', [App\Http\Controllers\ReturnController::class, 'destroy'])->middleware('permission:returns,can_delete')->name('destroy');
        Route::post('/recalculate-sale-totals', [App\Http\Controllers\ReturnController::class, 'recalculateSaleTotals'])->name('recalculateSaleTotals');
    });

    // Inventory routes
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [App\Http\Controllers\InventoryController::class, 'index'])->middleware('permission:inventory,can_view')->name('index');
        Route::get('/export/excel', [App\Http\Controllers\InventoryController::class, 'exportExcel'])->middleware('permission:inventory,can_export')->name('export.excel');
        Route::get('/export/pdf', [App\Http\Controllers\InventoryController::class, 'exportPdf'])->middleware('permission:inventory,can_export')->name('export.pdf');
        
        // Template downloads
        Route::get('/template/painting', [App\Http\Controllers\InventoryController::class, 'downloadPaintingTemplate'])->middleware('permission:inventory,can_create')->name('template.painting');
        Route::get('/template/supply', [App\Http\Controllers\InventoryController::class, 'downloadSupplyTemplate'])->middleware('permission:inventory,can_create')->name('template.supply');
        
        // Import routes
        Route::get('/import', [App\Http\Controllers\InventoryController::class, 'import'])->middleware('permission:inventory,can_create')->name('import');
        Route::get('/import/painting', [App\Http\Controllers\InventoryController::class, 'importPaintingForm'])->middleware('permission:inventory,can_create')->name('import.painting.form');
        Route::get('/import/supply', [App\Http\Controllers\InventoryController::class, 'importSupplyForm'])->middleware('permission:inventory,can_create')->name('import.supply.form');
        Route::post('/import/painting', [App\Http\Controllers\InventoryController::class, 'importPainting'])->middleware('permission:inventory,can_create')->name('import.painting');
        Route::post('/import/supply', [App\Http\Controllers\InventoryController::class, 'importSupply'])->middleware('permission:inventory,can_create')->name('import.supply');
        Route::post('/import/painting/excel', [App\Http\Controllers\InventoryController::class, 'importPaintingExcel'])->middleware('permission:inventory,can_create')->name('import.painting.excel');
        Route::post('/import/supply/excel', [App\Http\Controllers\InventoryController::class, 'importSupplyExcel'])->middleware('permission:inventory,can_create')->name('import.supply.excel');

        Route::get('/paintings/{id}/show', [App\Http\Controllers\InventoryController::class, 'showPainting'])->middleware('permission:inventory,can_view')->name('paintings.show');
        Route::get('/paintings/{id}/edit', [App\Http\Controllers\InventoryController::class, 'editPainting'])->middleware('permission:inventory,can_edit')->name('paintings.edit');
        Route::put('/paintings/{id}', [App\Http\Controllers\InventoryController::class, 'updatePainting'])->middleware('permission:inventory,can_edit')->name('paintings.update');
        Route::delete('/paintings/{id}', [App\Http\Controllers\InventoryController::class, 'destroyPainting'])->middleware('permission:inventory,can_delete')->name('paintings.destroy');

        // Supplies CRUD (edit/update/destroy)
        Route::get('/supplies/{id}/show', [App\Http\Controllers\InventoryController::class, 'showSupply'])->middleware('permission:inventory,can_view')->name('supplies.show');
        Route::get('/supplies/{id}/edit', [App\Http\Controllers\InventoryController::class, 'editSupply'])->middleware('permission:inventory,can_edit')->name('supplies.edit');
        Route::put('/supplies/{id}', [App\Http\Controllers\InventoryController::class, 'updateSupply'])->middleware('permission:inventory,can_edit')->name('supplies.update');
        Route::delete('/supplies/{id}', [App\Http\Controllers\InventoryController::class, 'destroySupply'])->middleware('permission:inventory,can_delete')->name('supplies.destroy');
        
        // Bulk delete
        Route::delete('/bulk-delete', [App\Http\Controllers\InventoryController::class, 'bulkDelete'])->middleware('permission:inventory,can_delete')->name('bulk-delete');
    });

    // Showrooms routes
    Route::prefix('showrooms')->name('showrooms.')->group(function () {
        Route::get('/', [App\Http\Controllers\ShowroomController::class, 'index'])->middleware('permission:showrooms,can_view')->name('index');
        Route::get('/create', [App\Http\Controllers\ShowroomController::class, 'create'])->middleware('permission:showrooms,can_create')->name('create');
        Route::post('/', [App\Http\Controllers\ShowroomController::class, 'store'])->middleware('permission:showrooms,can_create')->name('store');
        Route::get('/{id}/edit', [App\Http\Controllers\ShowroomController::class, 'edit'])->middleware('permission:showrooms,can_edit')->name('edit');
        Route::put('/{id}', [App\Http\Controllers\ShowroomController::class, 'update'])->middleware('permission:showrooms,can_edit')->name('update');
        Route::delete('/{id}', [App\Http\Controllers\ShowroomController::class, 'destroy'])->middleware('permission:showrooms,can_delete')->name('destroy');
    });

    // Customers routes
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [App\Http\Controllers\CustomerController::class, 'index'])->middleware('permission:customers,can_view')->name('index');
        Route::get('/create', [App\Http\Controllers\CustomerController::class, 'create'])->middleware('permission:customers,can_create')->name('create');
        Route::post('/', [App\Http\Controllers\CustomerController::class, 'store'])->middleware('permission:customers,can_create')->name('store');
        Route::get('/{id}', [App\Http\Controllers\CustomerController::class, 'show'])->middleware('permission:customers,can_view')->name('show');
        Route::get('/{id}/edit', [App\Http\Controllers\CustomerController::class, 'edit'])->middleware('permission:customers,can_edit')->name('edit');
        Route::put('/{id}', [App\Http\Controllers\CustomerController::class, 'update'])->middleware('permission:customers,can_edit')->name('update');
        Route::delete('/{id}', [App\Http\Controllers\CustomerController::class, 'destroy'])->middleware('permission:customers,can_delete')->name('destroy');
    });

    // Permissions routes
    Route::prefix('permissions')->name('permissions.')->middleware('permission:permissions,can_view')->group(function () {
        Route::get('/', [App\Http\Controllers\PermissionController::class, 'index'])->name('index');
        Route::post('/roles', [App\Http\Controllers\PermissionController::class, 'storeRole'])->middleware('permission:permissions,can_create')->name('roles.store');
        Route::put('/roles/{id}', [App\Http\Controllers\PermissionController::class, 'updateRole'])->middleware('permission:permissions,can_edit')->name('roles.update');
        Route::delete('/roles/{id}', [App\Http\Controllers\PermissionController::class, 'deleteRole'])->middleware('permission:permissions,can_delete')->name('roles.delete');
        Route::get('/roles/{id}', [App\Http\Controllers\PermissionController::class, 'getRole'])->name('roles.get');
        Route::put('/roles/{id}/permissions', [App\Http\Controllers\PermissionController::class, 'updatePermissions'])->middleware('permission:permissions,can_edit')->name('roles.permissions.update');
        Route::put('/roles/{id}/field-permissions', [App\Http\Controllers\PermissionController::class, 'updateFieldPermissions'])->middleware('permission:permissions,can_edit')->name('roles.field-permissions.update');
        Route::put('/users/{userId}/assign-role', [App\Http\Controllers\PermissionController::class, 'assignRole'])->middleware('permission:permissions,can_edit')->name('users.assign-role');
        Route::get('/modules/{module}/fields', [App\Http\Controllers\PermissionController::class, 'getModuleFields'])->name('modules.fields');
        Route::get('/modules/{module}/sections', [App\Http\Controllers\PermissionController::class, 'getDisplaySections'])->name('modules.sections');
        Route::post('/custom-fields', [App\Http\Controllers\PermissionController::class, 'storeCustomField'])->middleware('permission:permissions,can_create')->name('custom-fields.store');
        Route::delete('/custom-fields/{id}', [App\Http\Controllers\PermissionController::class, 'deleteCustomField'])->middleware('permission:permissions,can_delete')->name('custom-fields.delete');
    });

    // Employees routes
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', [App\Http\Controllers\EmployeeController::class, 'index'])->middleware('permission:employees,can_view')->name('index');
        Route::get('/create', [App\Http\Controllers\EmployeeController::class, 'create'])->middleware('permission:employees,can_create')->name('create');
        Route::post('/', [App\Http\Controllers\EmployeeController::class, 'store'])->middleware('permission:employees,can_create')->name('store');
        Route::get('/export/excel', [App\Http\Controllers\EmployeeController::class, 'exportExcel'])->middleware('permission:employees,can_export')->name('export.excel');
        Route::get('/export/pdf', [App\Http\Controllers\EmployeeController::class, 'exportPdf'])->middleware('permission:employees,can_export')->name('export.pdf');
        Route::post('/{id}/toggle-status', [App\Http\Controllers\EmployeeController::class, 'toggleStatus'])->middleware('permission:employees,can_edit')->name('toggle-status');
        Route::get('/{id}', [App\Http\Controllers\EmployeeController::class, 'show'])->middleware('permission:employees,can_view')->name('show');
        Route::get('/{id}/edit', [App\Http\Controllers\EmployeeController::class, 'edit'])->middleware('permission:employees,can_edit')->name('edit');
        Route::put('/{id}', [App\Http\Controllers\EmployeeController::class, 'update'])->middleware('permission:employees,can_edit')->name('update');
        Route::delete('/{id}', [App\Http\Controllers\EmployeeController::class, 'destroy'])->middleware('permission:employees,can_delete')->name('destroy');
    });

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Year Database routes
    Route::prefix('year')->name('year.')->group(function () {
        Route::get('/', [App\Http\Controllers\YearDatabaseController::class, 'index'])->middleware('permission:year_database,can_view')->name('index');
        Route::get('/manage', [App\Http\Controllers\YearDatabaseController::class, 'manage'])->middleware('permission:year_database,can_view')->name('manage');
        Route::post('/switch', [App\Http\Controllers\YearDatabaseController::class, 'switchYear'])->middleware('permission:year_database,can_view')->name('switch');
        Route::post('/reset', [App\Http\Controllers\YearDatabaseController::class, 'resetYear'])->middleware('permission:year_database,can_view')->name('reset');
        Route::get('/info', [App\Http\Controllers\YearDatabaseController::class, 'getCurrentInfo'])->middleware('permission:year_database,can_view')->name('info');

        // Export & Import
        Route::post('/export', [App\Http\Controllers\YearDatabaseController::class, 'exportDatabase'])->middleware('permission:year_database,can_export')->name('export');
        Route::post('/export-with-images', [App\Http\Controllers\YearDatabaseController::class, 'exportWithImages'])->middleware('permission:year_database,can_export')->name('export.with-images');
        Route::post('/import', [App\Http\Controllers\YearDatabaseController::class, 'importDatabase'])->middleware('permission:year_database,can_import')->name('import');
        Route::post('/import-with-images', [App\Http\Controllers\YearDatabaseController::class, 'importWithImages'])->middleware('permission:year_database,can_import')->name('import.with-images');
        Route::get('/export/{id}/download', [App\Http\Controllers\YearDatabaseController::class, 'downloadExport'])->middleware('permission:year_database,can_view')->name('export.download');
        Route::delete('/export/{id}', [App\Http\Controllers\YearDatabaseController::class, 'deleteExport'])->middleware('permission:year_database,can_delete')->name('export.delete');
        
        // Import với progress (batch)
        Route::post('/import/prepare', [App\Http\Controllers\YearDatabaseController::class, 'prepareImportImages'])->middleware('permission:year_database,can_import')->name('import.prepare');
        Route::post('/import/sql', [App\Http\Controllers\YearDatabaseController::class, 'importSqlFromSession'])->middleware('permission:year_database,can_import')->name('import.sql');
        Route::post('/import/images-batch', [App\Http\Controllers\YearDatabaseController::class, 'copyImagesBatch'])->middleware('permission:year_database,can_import')->name('import.images-batch');
        Route::post('/import/cleanup', [App\Http\Controllers\YearDatabaseController::class, 'cleanupImportSession'])->middleware('permission:year_database,can_import')->name('import.cleanup');
        Route::post('/upload-images-batch', [App\Http\Controllers\YearDatabaseController::class, 'uploadImagesBatch'])->middleware('permission:year_database,can_import')->name('upload-images-batch');

        // Year management
        Route::get('/stats/{year}', [App\Http\Controllers\YearDatabaseController::class, 'getYearStats'])->middleware('permission:year_database,can_view')->name('stats');
        Route::post('/cleanup', [App\Http\Controllers\YearDatabaseController::class, 'cleanupYear'])->middleware('permission:year_database,can_delete')->name('cleanup');
        Route::post('/prepare', [App\Http\Controllers\YearDatabaseController::class, 'prepareNewYear'])->middleware('permission:year_database,can_create')->name('prepare');
    });

    // Reports routes
    Route::prefix('reports')->name('reports.')->middleware('permission:reports,can_view')->group(function () {
        Route::get('/', [App\Http\Controllers\ReportsController::class, 'dailyCashCollection'])->name('daily-cash-collection');
    });

    // Frames routes
    Route::prefix('frames')->name('frames.')->group(function () {
        Route::get('/', [App\Http\Controllers\FrameController::class, 'index'])->middleware('permission:frames,can_view')->name('index');
        Route::get('/create', [App\Http\Controllers\FrameController::class, 'create'])->middleware('permission:frames,can_create')->name('create');
        Route::post('/', [App\Http\Controllers\FrameController::class, 'store'])->middleware('permission:frames,can_create')->name('store');
        Route::get('/api/frame/{id}', [App\Http\Controllers\FrameController::class, 'getFrameJson'])->name('api.frame');
        Route::get('/api/supply/{id}', [App\Http\Controllers\FrameController::class, 'getSupplyInfo'])->name('api.supply');
        Route::get('/{frame}', [App\Http\Controllers\FrameController::class, 'show'])->middleware('permission:frames,can_view')->name('show');
        Route::get('/{frame}/edit', [App\Http\Controllers\FrameController::class, 'edit'])->middleware('permission:frames,can_edit')->name('edit');
        Route::put('/{frame}', [App\Http\Controllers\FrameController::class, 'update'])->middleware('permission:frames,can_edit')->name('update');
        Route::delete('/{frame}', [App\Http\Controllers\FrameController::class, 'destroy'])->middleware('permission:frames,can_delete')->name('destroy');
    });

    // Activity Logs routes
    Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
        Route::get('/', [App\Http\Controllers\ActivityLogController::class, 'index'])->name('index');
        Route::get('/my-activity', [App\Http\Controllers\ActivityLogController::class, 'myActivity'])->name('my-activity');
        Route::get('/export/excel', [App\Http\Controllers\ActivityLogController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [App\Http\Controllers\ActivityLogController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/{id}', [App\Http\Controllers\ActivityLogController::class, 'show'])->name('show');
    });
});
