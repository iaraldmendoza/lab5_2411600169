<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\InventoryApiController;
use App\Http\Controllers\InventoryReportController;
use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\ProcurementDashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [ProcurementDashboardController::class, 'index'])->name('dashboard');
    Route::get('/suppliers/performance', [ProcurementDashboardController::class, 'performance'])->name('suppliers.performance');
    Route::post('/suppliers/{supplier}/restore', [SupplierController::class, 'restore'])->whereNumber('supplier')->name('suppliers.restore');
    Route::post('/suppliers/{supplier}/agreements', [SupplierController::class, 'agreement'])->name('suppliers.agreement');
    Route::resource('suppliers', SupplierController::class);

    Route::get('/purchase-orders/{purchaseOrder}/pdf', [ExportController::class, 'purchaseOrder'])->name('purchase-orders.pdf');
    Route::post('/purchase-orders/{purchaseOrder}/action/{action}', [PurchaseOrderController::class, 'action'])->name('purchase-orders.action');
    Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::resource('purchase-orders', PurchaseOrderController::class)->parameters(['purchase-orders' => 'purchaseOrder']);

    Route::get('/replenishment', [ProcurementDashboardController::class, 'replenishment'])->name('replenishment.index');
    Route::post('/replenishment/check', [ProcurementDashboardController::class, 'run'])->name('replenishment.run');
    Route::get('/exports/{type}/{format}', [ExportController::class, 'download'])
        ->where('type', 'products|purchase-orders|transactions|supplier-performance')->where('format', 'csv|pdf')->name('exports.download');

    Route::resource('products', ProductController::class);
    Route::get('/stock-adjustments/create', [InventoryTransactionController::class, 'adjustment'])->name('adjustments.create');
    Route::post('/stock-adjustments', [InventoryTransactionController::class, 'adjust'])->name('adjustments.store');
    Route::resource('transactions', InventoryTransactionController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::post('/notifications/{notification}/read', [InventoryApiController::class, 'read'])->name('notifications.read');
    Route::get('/reports/inventory', [InventoryReportController::class, 'index'])->name('reports.inventory');
    Route::get('/reports/inventory/csv', [InventoryReportController::class, 'csv'])->name('reports.csv');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('api')->middleware('throttle:120,1')->group(function () {
        Route::get('/dashboard/stats', [InventoryApiController::class, 'stats']);
        Route::get('/dashboard/{widget}', [ProcurementDashboardController::class, 'widget'])
            ->where('widget', 'pending-orders|recent-orders|supplier-performance|reorder-alerts');
        Route::get('/suppliers/{supplier}/catalog', [SupplierController::class, 'catalog']);
        Route::get('/products/low-stock', [InventoryApiController::class, 'lowStock']);
        Route::get('/products/{product}', [InventoryApiController::class, 'product']);
        Route::get('/transactions/recent', [InventoryApiController::class, 'recent']);
    });
});

require __DIR__.'/auth.php';
