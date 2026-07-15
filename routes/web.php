<?php

use App\Http\Controllers\Auth\StaffSessionController;
use App\Http\Controllers\Auth\StaffRecoveryController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');
Route::view('/shop', 'pages.shop')->name('shop');
Route::view('/maintenance', 'pages.maintenance')->name('maintenance');
Route::view('/computers', 'pages.computers')->name('computers');
Route::middleware('guest')->group(function () {
    Route::get('/staff/login', [StaffSessionController::class, 'create'])->name('staff.login');
    Route::post('/staff/login', [StaffSessionController::class, 'store'])->name('staff.login.store');
    Route::post('/staff/recovery/send', [StaffRecoveryController::class, 'send'])->middleware('throttle:5,1')->name('staff.recovery.send');
    Route::post('/staff/recovery/verify', [StaffRecoveryController::class, 'verify'])->middleware('throttle:8,1')->name('staff.recovery.verify');
});
Route::post('/staff/logout', [StaffSessionController::class, 'destroy'])->middleware('auth')->name('staff.logout');
Route::view('/customer/device-request', 'pages.customer-device-request')->name('customer.device-request');

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');

    Route::get('/{module}', function (string $module) {
        $modules = [
            'store' => ['بيانات المحل', 'حدّث اسم المحل وبيانات التواصل والفواتير.'],
            'products' => ['المنتجات والبضاعة', 'أضف المنتجات والأسعار والكميات والأرقام التسلسلية.'],
            'sales' => ['المبيعات', 'أنشئ فواتير البيع وتابع المدفوعات والمرتجعات.'],
            'purchases' => ['المشتريات', 'سجّل فواتير الموردين واستلام البضاعة.'],
            'inventory' => ['المخزن', 'تابع الأرصدة وحركات المخزون والجرد.'],
            'repairs' => ['الصيانة', 'أدر تذاكر الصيانة والحالات وقطع الغيار.'],
            'customers' => ['العملاء', 'بيانات العملاء وسجل المبيعات والصيانة.'],
            'suppliers' => ['الموردون', 'بيانات الموردين والحسابات والمشتريات.'],
            'expenses' => ['المصروفات والخزنة', 'سجّل المصروفات وحركات النقدية والورديات.'],
            'reports' => ['التقارير', 'تقارير المبيعات والأرباح والمخزون والصيانة.'],
            'team' => ['الموظفون والصلاحيات', 'إدارة الموظفين والأدوار وصلاحيات الوصول.'],
            'settings' => ['الإعدادات', 'إعدادات النظام والإشعارات والنسخ الاحتياطي.'],
        ];

        abort_unless(isset($modules[$module]), 404);

        $demoRows = [
            'products' => [['IPH-16-PRO','iPhone 16 Pro','Apple','62,900','8'],['SAM-S25-U','Galaxy S25 Ultra','Samsung','54,500','3'],['XIA-15','Xiaomi 15','Xiaomi','31,999','12']],
            'sales' => [['SAL-2026-000128','أحمد محمد','12 يوليو 2026','64,250','مكتملة'],['SAL-2026-000127','عميل نقدي','12 يوليو 2026','2,150','مكتملة'],['SAL-2026-000126','سارة علي','11 يوليو 2026','18,999','جزئي']],
            'purchases' => [['PUR-2026-000041','شركة النور','10 يوليو 2026','185,000','مستلمة'],['PUR-2026-000040','موبايل تك','8 يوليو 2026','92,500','جزئي'],['PUR-2026-000039','دلتا تريد','5 يوليو 2026','44,700','مستلمة']],
            'inventory' => [['المخزن الرئيسي','iPhone 16 Pro','8','2','متوفر'],['المخزن الرئيسي','Galaxy S25 Ultra','3','4','منخفض'],['مخزن الصيانة','شاشة iPhone 13','1','3','حرج']],
            'repairs' => [['REP-2026-000087','محمود حسن','iPhone 13','تغيير شاشة','قيد الإصلاح'],['REP-2026-000086','منى سمير','Dell Latitude','لا يعمل','بانتظار قطعة'],['REP-2026-000085','عمر خالد','Samsung A55','سوفت وير','جاهز للاستلام']],
            'customers' => [['أحمد محمد','01012345678','ahmed@example.com','5 فواتير','نشط'],['سارة علي','01123456789','sara@example.com','2 فاتورة','نشط'],['محمود حسن','01234567890','—','3 صيانات','نشط']],
            'suppliers' => [['شركة النور','01098765432','185,000','مسدد'],['موبايل تك','01187654321','28,500','مستحق'],['دلتا تريد','01276543210','44,700','مسدد']],
            'expenses' => [['EXP-00091','إيجار المحل','10 يوليو 2026','12,000','مصروف'],['EXP-00090','شحن بضاعة','9 يوليو 2026','1,450','مصروف'],['CSH-00125','مبيعات نقدية','12 يوليو 2026','64,250','إيداع']],
            'reports' => [['مبيعات يوليو','128 فاتورة','425,600','عرض التقرير','جاهز'],['أرباح يونيو','310 فاتورة','98,750','عرض التقرير','جاهز'],['تقرير المخزون','246 منتج','1,840,000','تصدير Excel','محدث']],
            'team' => [['محمد الباشا','admin@bashamob.local','Owner','كل الصلاحيات','نشط'],['كريم أحمد','cashier@bashamob.local','Cashier','المبيعات والعملاء','نشط'],['يوسف علي','tech@bashamob.local','Technician','الصيانة فقط','نشط']],
            'settings' => [['الفواتير','بادئة رقم البيع','SAL-2026','تعديل','فعال'],['الإشعارات','بريد جاهزية الصيانة','مفعّل','تعديل','فعال'],['النسخ الاحتياطي','آخر نسخة','اليوم 03:00','تشغيل الآن','سليم']],
        ];

        return view('admin.module', ['module' => $module, 'meta' => $modules[$module], 'rows' => $demoRows[$module] ?? []]);
    })->name('module');
});
