<?php

use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Auth\StaffRecoveryController;
use App\Http\Controllers\Auth\StaffSessionController;
use App\Http\Controllers\MaintenanceRequestController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');
Route::view('/shop', 'pages.shop')->name('shop');
Route::view('/maintenance', 'pages.maintenance')->name('maintenance');
Route::post('/maintenance/requests', [MaintenanceRequestController::class, 'store'])->middleware('throttle:5,1')->name('maintenance.requests.store');
Route::view('/computers', 'pages.computers')->name('computers');
Route::redirect('/customer/device-request', '/maintenance')->name('customer.device-request');

Route::middleware('guest')->group(function () {
    Route::get('/staff/login', [StaffSessionController::class, 'create'])->name('staff.login');
    Route::post('/staff/login', [StaffSessionController::class, 'store'])->name('staff.login.store');
    Route::post('/staff/recovery/send', [StaffRecoveryController::class, 'send'])->middleware('throttle:5,1')->name('staff.recovery.send');
    Route::post('/staff/recovery/verify', [StaffRecoveryController::class, 'verify'])->middleware('throttle:8,1')->name('staff.recovery.verify');
    Route::post('/staff/recovery/reset', [StaffRecoveryController::class, 'reset'])->middleware('throttle:5,1')->name('staff.recovery.reset');
});
Route::post('/staff/logout', [StaffSessionController::class, 'destroy'])->middleware('auth')->name('staff.logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'staff'])->group(function () {
    Route::get('/', DashboardController::class)->middleware('permission:view-dashboard')->name('dashboard');
    Route::post('/products', [ProductController::class, 'store']);
    Route::patch('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::patch('/customers/{customer}', [CustomerController::class, 'update']);
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::patch('/suppliers/{supplier}', [SupplierController::class, 'update']);
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy']);
    Route::post('/team', [StaffController::class, 'store']);
    Route::patch('/team/{user}', [StaffController::class, 'update']);
    Route::delete('/team/{user}', [StaffController::class, 'destroy']);
    Route::get('/{module}', ModuleController::class)->middleware('module.permission')->name('module');
});
