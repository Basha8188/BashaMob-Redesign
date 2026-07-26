<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <title>@yield('title', 'الإدارة') | BashaMob</title>
    <script>(()=>{const l=localStorage.getItem('basha-admin-language')||'ar';document.documentElement.lang=l;document.documentElement.dir=l==='ar'?'rtl':'ltr';document.documentElement.dataset.locale=l;document.documentElement.dataset.adminUi='cosmic';if(localStorage.getItem('basha-sidebar')==='pinned'&&innerWidth>760)document.documentElement.classList.add('sidebar-pinned')})();</script>
    <x-theme-bootstrap />
    @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/css/violet.css', 'resources/js/admin.js'])
</head>
<body class="admin-body">
<x-aurora-background variant="admin" />
<div class="admin-device-rain" aria-hidden="true">
    @for($device = 1; $device <= 18; $device++)
        <i class="{{ $device % 3 === 0 ? 'falling-laptop' : 'falling-phone' }}" style="--rain-x:{{ ($device * 37) % 101 }}%;--rain-delay:-{{ $device * 2 }}s;--rain-duration:{{ 15 + ($device % 7) }}s;--rain-scale:{{ (70 + (($device * 11) % 45)) / 100 }};"></i>
    @endfor
    @for($brand = 1; $brand <= 6; $brand++)
        <span class="falling-brand" style="--rain-x:{{ ($brand * 29 + 7) % 96 }}%;--rain-delay:-{{ $brand * 4 }}s;--rain-duration:{{ 19 + ($brand % 4) }}s;--rain-scale:{{ (62 + ($brand * 7)) / 100 }};">BashaMob</span>
    @endfor
</div>
<div class="admin-shell">
    <x-admin.sidebar />
    <div class="admin-content">
        <x-admin.topbar :title="trim($__env->yieldContent('title', 'الصفحة الرئيسية'))" />
        <main>@yield('content')</main>
    </div>
</div>
<div class="crud-modal" id="crud-modal" aria-hidden="true">
    <div class="crud-backdrop" data-close-modal></div>
    <section class="crud-dialog glass-panel glass-panel--strong" role="dialog" aria-modal="true" aria-labelledby="crud-title">
        <button class="crud-close" type="button" data-close-modal>×</button>
        <span class="eyebrow" id="crud-kicker">إدارة البيانات</span><h2 id="crud-title">إضافة سجل جديد</h2><p id="crud-description">أدخل البيانات المطلوبة، ويمكنك تعديلها لاحقًا.</p>
        <form id="crud-form"><div class="crud-fields" id="crud-fields"></div><button class="btn btn-primary" id="crud-submit" type="submit">حفظ البيانات</button></form>
    </section>
</div>
<div class="admin-toast glass-panel" id="admin-toast"><span>تم حفظ البيانات بنجاح</span><button type="button" id="print-receipt" hidden>طباعة إيصال</button></div>
<article class="print-receipt" id="receipt-print-area">
    <header><b>BashaMob</b><span>إيصال إجراء</span></header>
    <div><span>رقم الإيصال</span><strong id="receipt-number">—</strong></div>
    <div><span>نوع الإجراء</span><strong id="receipt-action">—</strong></div>
    <div><span>التاريخ والوقت</span><strong id="receipt-date">—</strong></div>
    <div><span>القيمة</span><strong id="receipt-amount">—</strong></div>
    <div><span>الموظف</span><strong>{{ auth()->user()->name }}</strong></div>
    <footer>شكرًا لاستخدام نظام BashaMob</footer>
</article>
<x-ai-assistant />
</body>
</html>
