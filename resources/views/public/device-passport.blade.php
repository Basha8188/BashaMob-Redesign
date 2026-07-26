<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>التحقق من الجهاز | BashaMob</title>
    <x-theme-bootstrap />
    @vite(['resources/css/app.css','resources/css/violet.css','resources/js/theme-manager.js'])
</head>
<body class="storefront-body public-portal">
<x-aurora-background/>
<x-theme-picker/>
<main class="public-card glass-panel">
    <header><strong class="luminous-brand">Basha<span>Mob</span></strong><small>Device Passport Verification</small></header>
    <h1>جواز جهاز موثّق</h1>
    <dl class="public-quote-data">
        <dt>كود الجهاز</dt><dd>{{ $device->internal_code }}</dd>
        <dt>الجهاز</dt><dd>{{ $device->brand }} {{ $device->model }}</dd>
        <dt>اللون والسعة</dt><dd>{{ $device->color ?: '—' }} / {{ $device->storage ?: '—' }}</dd>
        <dt>IMEI</dt><dd>{{ $device->imei_1 ? '••••••'.substr($device->imei_1,-4) : '—' }}</dd>
        <dt>مرات الصيانة</dt><dd>{{ $device->repairs->count() }}</dd>
        <dt>آخر حالة</dt><dd>{{ $device->repairs->first()?->status->value ?? 'لا توجد صيانة' }}</dd>
    </dl>
    <p>تم التحقق من السجل داخل نظام BashaMob. لا تعرض هذه الصفحة بيانات المالك الحساسة.</p>
</main>
</body>
</html>
