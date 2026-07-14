<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'لوحة الإدارة') | BashaMob</title>
    <script>document.documentElement.dataset.theme=localStorage.getItem('basha-theme')||'light';document.documentElement.dataset.palette=localStorage.getItem('basha-palette')||'ocean';</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar glass-panel glass-panel--strong">
        <a class="admin-logo" href="{{ route('admin.dashboard') }}"><span class="brand-mark">B</span><span>Basha<b>Mob</b><small>نظام الإدارة</small></span></a>
        <nav>
            <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><x-admin-icon name="dashboard"/><span>لوحة المعلومات</span></a>
            @foreach(['store'=>'بيانات المحل','products'=>'المنتجات والبضاعة','sales'=>'المبيعات','purchases'=>'المشتريات','inventory'=>'المخزن','repairs'=>'الصيانة','customers'=>'العملاء','suppliers'=>'الموردون','expenses'=>'المصروفات والخزنة','reports'=>'التقارير','team'=>'الموظفون والصلاحيات','settings'=>'الإعدادات'] as $key=>$label)
                <a class="{{ request()->route('module') === $key ? 'active' : '' }}" href="{{ route('admin.module', $key) }}"><x-admin-icon :name="$key"/><span>{{ $label }}</span></a>
            @endforeach
        </nav>
        <a class="back-store" href="{{ route('home') }}">← العودة لواجهة العملاء</a>
    </aside>
    <div class="admin-content">
        <header class="admin-topbar glass-panel">
            <div class="topbar-page"><button class="sidebar-toggle" type="button">☰</button><span><small>لوحة الإدارة /</small><b>@yield('title', 'لوحة المعلومات')</b></span></div>
            <label class="topbar-search"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg><input type="search" placeholder="ابحث في النظام..."><kbd>⌘ K</kbd></label>
            <div class="topbar-actions">
                <button class="topbar-theme theme-toggle" type="button" aria-label="تبديل الوضع"><svg class="sun" viewBox="0 0 24 24"><path d="M12 4V2m0 20v-2m8-8h2M2 12h2m13.7-5.7 1.4-1.4M4.9 19.1l1.4-1.4m11.4 0 1.4 1.4M4.9 4.9l1.4 1.4M17 12a5 5 0 1 1-10 0 5 5 0 0 1 10 0Z"/></svg><svg class="moon" viewBox="0 0 24 24"><path d="M20 15.3A8.5 8.5 0 0 1 8.7 4a8.5 8.5 0 1 0 11.3 11.3Z"/></svg></button>
                <x-theme-picker />
                <button class="notification-button" type="button" aria-label="الإشعارات"><svg viewBox="0 0 24 24"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><i></i></button>
                <div class="admin-user"><span><b>مدير النظام</b><small>Owner</small></span><i>م</i></div>
                <form action="{{ route('staff.logout') }}" method="POST">@csrf<button class="logout-button" type="submit" title="تسجيل الخروج"><svg viewBox="0 0 24 24"><path d="M10 5H5v14h5m4-3 4-4-4-4m4 4H9"/></svg><span>خروج</span></button></form>
            </div>
        </header>
        <main>@yield('content')</main>
    </div>
</div>
<div class="crud-modal" id="crud-modal" aria-hidden="true">
    <div class="crud-backdrop" data-close-modal></div>
    <section class="crud-dialog glass-panel glass-panel--strong" role="dialog" aria-modal="true" aria-labelledby="crud-title">
        <button class="crud-close" type="button" data-close-modal>×</button>
        <span class="eyebrow">إدارة البيانات</span><h2 id="crud-title">إضافة سجل جديد</h2><p>أدخل البيانات المطلوبة، ويمكنك تعديلها لاحقًا.</p>
        <form id="crud-form"><input type="hidden" name="rowIndex"><label>البيان الرئيسي<input name="primary" required placeholder="الاسم أو رقم العملية"></label><label>التفاصيل<input name="details" required placeholder="تفاصيل السجل"></label><div><label>القيمة / التاريخ<input name="value" required placeholder="القيمة أو التاريخ"></label><label>الحالة<select name="status"><option>نشط</option><option>مكتملة</option><option>قيد التنفيذ</option><option>معلق</option></select></label></div><button class="btn btn-primary" type="submit">حفظ البيانات</button></form>
    </section>
</div>
<div class="admin-toast glass-panel" id="admin-toast"><span>تم حفظ البيانات بنجاح</span><button type="button" id="print-receipt">طباعة إيصال</button></div>
<article class="print-receipt" id="receipt-print-area">
    <header><b>BashaMob</b><span>إيصال إجراء</span></header>
    <div><span>رقم الإيصال</span><strong id="receipt-number">—</strong></div>
    <div><span>نوع الإجراء</span><strong id="receipt-action">—</strong></div>
    <div><span>التاريخ والوقت</span><strong id="receipt-date">—</strong></div>
    <div><span>القيمة</span><strong id="receipt-amount">—</strong></div>
    <div><span>الموظف</span><strong>مدير النظام</strong></div>
    <footer>شكرًا لاستخدام نظام BashaMob</footer>
</article>
</body>
</html>
