<?php
use App\Http\Controllers\{DashboardController,ProductController,ProfileController,InventoryReportController,InventoryTransactionController,InventoryApiController};
use Illuminate\Support\Facades\Route;
Route::get('/',fn()=>redirect()->route('login'));
Route::middleware('auth')->group(function(){
 Route::get('/dashboard',[DashboardController::class,'index'])->name('dashboard');
 Route::resource('products',ProductController::class);
 Route::get('/stock-adjustments/create',[InventoryTransactionController::class,'adjustment'])->name('adjustments.create');
 Route::post('/stock-adjustments',[InventoryTransactionController::class,'adjust'])->name('adjustments.store');
 Route::resource('transactions',InventoryTransactionController::class)->only(['index','create','store','show','destroy']);
 Route::post('/notifications/{notification}/read',[InventoryApiController::class,'read'])->name('notifications.read');
 Route::get('/reports/inventory',[InventoryReportController::class,'index'])->name('reports.inventory');
 Route::get('/reports/inventory/csv',[InventoryReportController::class,'csv'])->name('reports.csv');
 Route::get('/profile',[ProfileController::class,'edit'])->name('profile.edit');
 Route::patch('/profile',[ProfileController::class,'update'])->name('profile.update');
 Route::delete('/profile',[ProfileController::class,'destroy'])->name('profile.destroy');
 Route::prefix('api')->middleware('throttle:120,1')->group(function(){
  Route::get('/dashboard/stats',[InventoryApiController::class,'stats']);
  Route::get('/products/low-stock',[InventoryApiController::class,'lowStock']);
  Route::get('/products/{product}',[InventoryApiController::class,'product']);
  Route::get('/transactions/recent',[InventoryApiController::class,'recent']);
 });
});
require __DIR__.'/auth.php';
