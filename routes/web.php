<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\MaterialStockController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockHistoryController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/stock', [MaterialStockController::class, 'index'])->name('stock.index');
Route::post('/stock/materials/{material}', [MaterialStockController::class, 'addMaterial'])->name('stock.materials.add');
Route::post('/stock/materials/{material}/use', [MaterialStockController::class, 'useMaterial'])->name('stock.materials.use');
Route::post('/stock/inks/{inkStock}', [MaterialStockController::class, 'addInk'])->name('stock.inks.add');
Route::post('/stock/inks/{inkStock}/use', [MaterialStockController::class, 'useInk'])->name('stock.inks.use');
Route::get('/stock/history', [StockHistoryController::class, 'index'])->name('stock.history');

Route::get('/sales/export', [SaleController::class, 'export'])->name('sales.export');
Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
Route::view('/expenses', 'expenses.index')->name('expenses.index');

Route::resource('customers', CustomerController::class)->except('destroy');

Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::patch('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
Route::get('/artworks/{artwork}', [OrderController::class, 'artwork'])->name('artworks.download');
