<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\DebtController;

// Auth routes
require __DIR__ . '/auth.php';

// Protected routes - require authentication
Route::middleware(['auth'])->group(function () {
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
        Route::get('/api/search/paintings', [SalesController::class, 'searchPaintings'])->name('api.search.paintings');
        Route::get('/api/search/supplies', [SalesController::class, 'searchSupplies'])->name('api.search.supplies');
        Route::get('/api/search/suggestions', [SalesController::class, 'searchSuggestions'])->name('api.search.suggestions');
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
        Route::get('/import', [App\Http\Controllers\InventoryController::class, 'import'])->middleware('permission:inventory,can_create')->name('import');
        Route::get('/import/painting', [App\Http\Controllers\InventoryController::class, 'importPaintingForm'])->middleware('permission:inventory,can_create')->name('import.painting.form');
        Route::get('/import/supply', [App\Http\Controllers\InventoryController::class, 'importSupplyForm'])->middleware('permission:inventory,can_create')->name('import.supply.form');
        Route::post('/import/painting', [App\Http\Controllers\InventoryController::class, 'importPainting'])->middleware('permission:inventory,can_create')->name('import.painting');
        Route::post('/import/supply', [App\Http\Controllers\InventoryController::class, 'importSupply'])->middleware('permission:inventory,can_create')->name('import.supply');

        Route::get('/paintings/{id}/show', [App\Http\Controllers\InventoryController::class, 'showPainting'])->middleware('permission:inventory,can_view')->name('paintings.show');
        Route::get('/paintings/{id}/edit', [App\Http\Controllers\InventoryController::class, 'editPainting'])->middleware('permission:inventory,can_edit')->name('paintings.edit');
        Route::put('/paintings/{id}', [App\Http\Controllers\InventoryController::class, 'updatePainting'])->middleware('permission:inventory,can_edit')->name('paintings.update');
        Route::delete('/paintings/{id}', [App\Http\Controllers\InventoryController::class, 'destroyPainting'])->middleware('permission:inventory,can_delete')->name('paintings.destroy');

        // Supplies CRUD (edit/update/destroy)
        Route::get('/supplies/{id}/show', [App\Http\Controllers\InventoryController::class, 'showSupply'])->middleware('permission:inventory,can_view')->name('supplies.show');
        Route::get('/supplies/{id}/edit', [App\Http\Controllers\InventoryController::class, 'editSupply'])->middleware('permission:inventory,can_edit')->name('supplies.edit');
        Route::put('/supplies/{id}', [App\Http\Controllers\InventoryController::class, 'updateSupply'])->middleware('permission:inventory,can_edit')->name('supplies.update');
        Route::delete('/supplies/{id}', [App\Http\Controllers\InventoryController::class, 'destroySupply'])->middleware('permission:inventory,can_delete')->name('supplies.destroy');
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
        Route::post('/switch', [App\Http\Controllers\YearDatabaseController::class, 'switchYear'])->middleware('permission:year_database,can_view')->name('switch');
        Route::post('/reset', [App\Http\Controllers\YearDatabaseController::class, 'resetYear'])->middleware('permission:year_database,can_view')->name('reset');
        Route::get('/info', [App\Http\Controllers\YearDatabaseController::class, 'getCurrentInfo'])->middleware('permission:year_database,can_view')->name('info');

        // Export & Import
        Route::post('/export', [App\Http\Controllers\YearDatabaseController::class, 'exportDatabase'])->middleware('permission:year_database,can_export')->name('export');
        Route::post('/import', [App\Http\Controllers\YearDatabaseController::class, 'importDatabase'])->middleware('permission:year_database,can_import')->name('import');
        Route::get('/export/{id}/download', [App\Http\Controllers\YearDatabaseController::class, 'downloadExport'])->middleware('permission:year_database,can_view')->name('export.download');
        Route::delete('/export/{id}', [App\Http\Controllers\YearDatabaseController::class, 'deleteExport'])->middleware('permission:year_database,can_delete')->name('export.delete');
    });
});
