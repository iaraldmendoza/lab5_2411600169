<?php
use App\Http\Controllers\{DashboardController,ProductController,ProfileController,InventoryReportController};
use Illuminate\Support\Facades\Route;
Route::get('/',fn()=>redirect()->route('login'));
Route::middleware('auth')->group(function() {
    Route::get('/dashboard',[DashboardController::class,'index'])->name('dashboard');
    Route::resource('products',ProductController::class);
    Route::get('/reports/inventory',[InventoryReportController::class,'index'])->name('reports.inventory');
    Route::get('/reports/inventory/csv',[InventoryReportController::class,'csv'])->name('reports.csv');
    Route::get('/profile',[ProfileController::class,'edit'])->name('profile.edit');
    Route::patch('/profile',[ProfileController::class,'update'])->name('profile.update');
    Route::delete('/profile',[ProfileController::class,'destroy'])->name('profile.destroy');
});
require __DIR__.'/auth.php';
