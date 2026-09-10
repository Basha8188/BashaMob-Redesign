<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CashRegisterController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CustomerRequestController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DevicePassportController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\Incentives\EmployeeIncentiveController;
use App\Http\Controllers\Admin\Incentives\IncentiveDashboardController;
use App\Http\Controllers\Admin\Incentives\IncentivePeriodController;
use App\Http\Controllers\Admin\Incentives\IncentivePlanController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\RepairController;
use App\Http\Controllers\Admin\RepairInspectionController;
use App\Http\Controllers\Admin\RepairQuotationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\AiActionController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\Auth\StaffRecoveryController;
use App\Http\Controllers\Auth\StaffSessionController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\PublicDevicePassportController;
use App\Http\Controllers\PublicMaintenancePortalController;
use App\Http\Controllers\PublicRepairQuotationController;
use App\Http\Controllers\ThemePreferenceController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');
Route::view('/shop', 'pages.shop')->name('shop');
Route::view('/maintenance', 'pages.maintenance')->name('maintenance');
Route::post('/maintenance/requests', [MaintenanceRequestController::class, 'store'])->middleware('throttle:5,1')->name('maintenance.requests.store');
Route::post('/maintenance/track', [MaintenanceRequestController::class, 'track'])->middleware('throttle:10,1')->name('maintenance.requests.track');
Route::post('/shop/orders', [CustomerOrderController::class, 'store'])->middleware('throttle:8,1')->name('customer.orders.store');
Route::view('/computers', 'pages.computers')->name('computers');
Route::redirect('/customer/device-request', '/maintenance')->name('customer.device-request');
Route::post('/assistant/ask', AiAssistantController::class)->middleware('throttle:20,1')->name('assistant.ask');
Route::post('/assistant/actions/{token}/confirm', [AiActionController::class, 'confirm'])
    ->whereUuid('token')
    ->middleware(['auth', 'staff', 'throttle:10,1'])
    ->name('assistant.actions.confirm');
Route::get('/device-passports/verify/{code}', PublicDevicePassportController::class)->middleware(['signed', 'throttle:30,1'])->name('device-passports.verify');
Route::get('/maintenance/portal/{ticket}', PublicMaintenancePortalController::class)->middleware(['signed', 'throttle:30,1'])->name('maintenance.portal');
Route::get('/repair-quotation/{token}', [PublicRepairQuotationController::class, 'show'])->middleware('throttle:30,1')->name('repair-quotations.public.show');
Route::post('/repair-quotation/{token}/respond', [PublicRepairQuotationController::class, 'respond'])->middleware('throttle:8,1')->name('repair-quotations.public.respond');

Route::middleware('guest')->group(function () {
    Route::get('/staff/login', [StaffSessionController::class, 'create'])->name('staff.login');
    Route::post('/staff/login', [StaffSessionController::class, 'store'])->name('staff.login.store');
    Route::post('/staff/recovery/send', [StaffRecoveryController::class, 'send'])->middleware('throttle:5,1')->name('staff.recovery.send');
    Route::post('/staff/recovery/verify', [StaffRecoveryController::class, 'verify'])->middleware('throttle:8,1')->name('staff.recovery.verify');
    Route::post('/staff/recovery/reset', [StaffRecoveryController::class, 'reset'])->middleware('throttle:5,1')->name('staff.recovery.reset');
});
Route::post('/staff/logout', [StaffSessionController::class, 'destroy'])->middleware('auth')->name('staff.logout');
Route::post('/theme-preference', [ThemePreferenceController::class, 'store'])->middleware(['auth', 'throttle:20,1'])->name('theme-preference.store');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'staff'])->group(function () {
    Route::get('/', DashboardController::class)->middleware('permission:view-dashboard')->name('dashboard');
    Route::get('/device-passports', [DevicePassportController::class, 'index'])->middleware('permission:device_passports.view')->name('device-passports.index');
    Route::post('/device-passports', [DevicePassportController::class, 'store'])->middleware('permission:device_passports.create')->name('device-passports.store');
    Route::get('/device-passports/{device}', [DevicePassportController::class, 'show'])->middleware('permission:device_passports.view')->name('device-passports.show');
    Route::patch('/device-passports/{device}', [DevicePassportController::class, 'update'])->middleware('permission:device_passports.update')->name('device-passports.update');
    Route::get('/repairs/{repair}/inspection', [RepairInspectionController::class, 'create'])->middleware('permission:repair_inspections.view')->name('repairs.inspections.create');
    Route::post('/repairs/{repair}/inspection', [RepairInspectionController::class, 'store'])->middleware('permission:repair_inspections.create')->name('repairs.inspections.store');
    Route::get('/repairs/{repair}/inspection/receipt', [RepairInspectionController::class, 'receipt'])->middleware('permission:repair_inspections.view')->name('repairs.inspections.receipt');
    Route::get('/repairs/{repair}/quotation', [RepairQuotationController::class, 'create'])->middleware('permission:repair_quotations.create')->name('repairs.quotations.create');
    Route::post('/repairs/{repair}/quotation', [RepairQuotationController::class, 'store'])->middleware('permission:repair_quotations.create')->name('repairs.quotations.store');
    Route::get('/repair-quotations/{quotation}', [RepairQuotationController::class, 'show'])->middleware('permission:repair_quotations.create')->name('repairs.quotations.show');
    Route::post('/products', [ProductController::class, 'store']);
    Route::patch('/products/{product}', [ProductController::class, 'update']);
    Route::get('/products/{product}/barcode', [ProductController::class, 'barcode'])->name('products.barcode');
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::patch('/customers/{customer}', [CustomerController::class, 'update']);
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
    Route::patch('/customer-requests/{type}/{id}', [CustomerRequestController::class, 'update'])->name('customer-requests.update');
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::patch('/suppliers/{supplier}', [SupplierController::class, 'update']);
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy']);
    Route::post('/team', [StaffController::class, 'store']);
    Route::patch('/team/{user}', [StaffController::class, 'update']);
    Route::delete('/team/{user}', [StaffController::class, 'destroy']);
    Route::post('/sales', [SaleController::class, 'store']);
    Route::delete('/sales/{sale}', [SaleController::class, 'destroy']);
    Route::post('/sales/{sale}/pay', [SaleController::class, 'pay']);
    Route::post('/sales/{sale}/refund', [SaleController::class, 'refund']);
    Route::post('/purchases', [PurchaseController::class, 'store']);
    Route::delete('/purchases/{purchase}', [PurchaseController::class, 'destroy']);
    Route::post('/purchases/{purchase}/receive', [PurchaseController::class, 'receive']);
    Route::post('/purchases/{purchase}/pay', [PurchaseController::class, 'pay']);
    Route::post('/inventory', [InventoryController::class, 'store']);
    Route::post('/repairs', [RepairController::class, 'store']);
    Route::patch('/repairs/{repair}', [RepairController::class, 'update']);
    Route::post('/expenses', [ExpenseController::class, 'store']);
    Route::patch('/expenses/{expense}', [ExpenseController::class, 'update']);
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy']);
    Route::post('/settings', [SettingController::class, 'store']);
    Route::post('/store', [SettingController::class, 'storeDetails']);
    Route::patch('/settings/{setting}', [SettingController::class, 'update']);
    Route::delete('/settings/{setting}', [SettingController::class, 'destroy']);
    Route::post('/cash-registers/{cashRegister}/open', [CashRegisterController::class, 'open']);
    Route::post('/cash-sessions/{cashSession}/close', [CashRegisterController::class, 'close']);
    Route::get('/reports/summary', [ReportController::class, 'summary']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::patch('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    Route::post('/brands', [BrandController::class, 'store']);
    Route::patch('/brands/{brand}', [BrandController::class, 'update']);
    Route::delete('/brands/{brand}', [BrandController::class, 'destroy']);
    Route::post('/warehouses', [WarehouseController::class, 'store']);
    Route::patch('/warehouses/{warehouse}', [WarehouseController::class, 'update']);
    Route::prefix('incentives')->name('incentives.')->group(function () {
        Route::get('/', IncentiveDashboardController::class)
            ->middleware('permission:incentives.view')
            ->name('index');
        Route::post('/plans', [IncentivePlanController::class, 'store'])
            ->middleware([
                'permission:incentives.manage_plans',
                'permission:incentives.manage_rules',
            ])
            ->name('plans.store');
        Route::post('/periods', [IncentivePeriodController::class, 'store'])
            ->middleware('permission:incentives.calculate')
            ->name('periods.store');
        Route::post('/periods/{period}/calculate', [IncentivePeriodController::class, 'calculate'])
            ->middleware('permission:incentives.calculate')
            ->name('periods.calculate');
        Route::get('/me', [EmployeeIncentiveController::class, 'mine'])
            ->middleware('permission:incentives.view_own|incentives.view_all_employees')
            ->name('mine');
        Route::get('/employees/{employee}', [EmployeeIncentiveController::class, 'show'])
            ->whereNumber('employee')
            ->middleware('permission:incentives.view_all_employees')
            ->name('employees.show');
    });
    Route::get('/{module}', ModuleController::class)->middleware('module.permission')->name('module');
});
