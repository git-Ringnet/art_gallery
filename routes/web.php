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
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Dashboard routes
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/stats', [DashboardController::class, 'getStats'])->name('stats');
    });

    // Sales routes
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [SalesController::class, 'index'])->name('index');
        Route::get('/create', [SalesController::class, 'create'])->name('create');
        Route::post('/', [SalesController::class, 'store'])->name('store');
        Route::get('/{id}', [SalesController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [SalesController::class, 'edit'])->name('edit');
        Route::put('/{id}', [SalesController::class, 'update'])->name('update');
        Route::delete('/{id}', [SalesController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/print', [SalesController::class, 'print'])->name('print');

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
        Route::get('/', [DebtController::class, 'index'])->name('index');
        Route::get('/api/search/suggestions', [DebtController::class, 'searchSuggestions'])->name('api.search.suggestions');
        Route::get('/{id}', [DebtController::class, 'show'])->name('show');
        Route::post('/{id}/collect', [DebtController::class, 'collect'])->name('collect');
        Route::get('/export', [DebtController::class, 'export'])->name('export');
    });

    // Returns routes
    Route::prefix('returns')->name('returns.')->group(function () {
        Route::get('/', [App\Http\Controllers\ReturnsController::class, 'index'])->name('index');
        Route::get('/search', [App\Http\Controllers\ReturnsController::class, 'searchInvoice'])->name('search');
        Route::post('/process', [App\Http\Controllers\ReturnsController::class, 'process'])->name('process');
    });

    // Inventory routes
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [App\Http\Controllers\InventoryController::class, 'index'])->name('index');
        Route::get('/import', [App\Http\Controllers\InventoryController::class, 'import'])->name('import');
        Route::post('/import/painting', [App\Http\Controllers\InventoryController::class, 'importPainting'])->name('import.painting');
        Route::post('/import/supply', [App\Http\Controllers\InventoryController::class, 'importSupply'])->name('import.supply');

        Route::get('/paintings/{id}/show', [App\Http\Controllers\InventoryController::class, 'showPainting'])->name('paintings.show');
        Route::get('/paintings/{id}/edit', [App\Http\Controllers\InventoryController::class, 'editPainting'])->name('paintings.edit');
        Route::put('/paintings/{id}', [App\Http\Controllers\InventoryController::class, 'updatePainting'])->name('paintings.update');
        Route::delete('/paintings/{id}', [App\Http\Controllers\InventoryController::class, 'destroyPainting'])->name('paintings.destroy');

        // Supplies CRUD (edit/update/destroy)
        Route::get('/supplies/{id}/show', [App\Http\Controllers\InventoryController::class, 'showSupply'])->name('supplies.show');
        Route::get('/supplies/{id}/edit', [App\Http\Controllers\InventoryController::class, 'editSupply'])->name('supplies.edit');
        Route::put('/supplies/{id}', [App\Http\Controllers\InventoryController::class, 'updateSupply'])->name('supplies.update');
        Route::delete('/supplies/{id}', [App\Http\Controllers\InventoryController::class, 'destroySupply'])->name('supplies.destroy');
    });

    // Showrooms routes
    Route::prefix('showrooms')->name('showrooms.')->group(function () {
        Route::get('/', [App\Http\Controllers\ShowroomController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\ShowroomController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\ShowroomController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [App\Http\Controllers\ShowroomController::class, 'edit'])->name('edit');
        Route::put('/{id}', [App\Http\Controllers\ShowroomController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\ShowroomController::class, 'destroy'])->name('destroy');
    });

    // Customers routes
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [App\Http\Controllers\CustomerController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\CustomerController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\CustomerController::class, 'store'])->name('store');
        Route::get('/{id}', [App\Http\Controllers\CustomerController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [App\Http\Controllers\CustomerController::class, 'edit'])->name('edit');
        Route::put('/{id}', [App\Http\Controllers\CustomerController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\CustomerController::class, 'destroy'])->name('destroy');
    });

    // Permissions routes
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [App\Http\Controllers\PermissionController::class, 'index'])->name('index');
        Route::post('/roles', [App\Http\Controllers\PermissionController::class, 'storeRole'])->name('roles.store');
        Route::put('/roles/{name}', [App\Http\Controllers\PermissionController::class, 'updateRole'])->name('roles.update');
    });

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
