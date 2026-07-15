@extends('layouts.admin')
@section('title', 'الصفحة الرئيسية')
@section('content')
<div class="reference-dashboard">
    <header class="reference-page-head">
        <div><small class="blessing-line">صلّيت على النبي؟</small><h1>الصفحة الرئيسية</h1><p>إدارة ومتابعة جميع عمليات BashaMob من مكان واحد.</p></div>
        <span class="reference-status"><i></i> نشط الآن</span>
    </header>

    <section class="stats-grid dashboard-stats reference-stats">
        <x-admin.stat-card tone="cyan" icon="repairs" title="طلبات صيانة اليوم" subtitle="قيد المتابعة" value="0" />
        <x-admin.stat-card tone="pink" icon="purchases" title="إجمالي المشتريات" subtitle="منذ بداية الشهر" value="2,481,051.72" unit="ج.م" />
        <x-admin.stat-card tone="purple" icon="sales" title="فواتير اليوم" subtitle="إجمالي الفواتير" value="0" />
        <x-admin.stat-card tone="gold" icon="sales" title="صافي مبيعات اليوم" subtitle="بعد الخصومات" value="150,687.01" unit="ج.م" />
        <x-admin.stat-card tone="orange" icon="inventory" title="قيمة المخزون" subtitle="التكلفة الحالية" value="3,729,742.62" unit="ج.م" />
        <x-admin.stat-card tone="violet" icon="reports" title="متوسط قيمة الفاتورة" subtitle="متوسط اليوم" value="87.728" unit="ج.م" />
        <x-admin.stat-card tone="blue" icon="expenses" title="صافي الربح" subtitle="ربح اليوم" value="23,902.61" unit="ج.م" />
        <x-admin.stat-card tone="amber" icon="purchases" title="المرتجعات المسجلة" subtitle="خلال اليوم" value="0" />
    </section>

    <section class="reference-notice glass-panel">
        <span class="notice-icon">▣</span>
        <div><strong>فواتير معلقة بدون سداد: 2</strong><p>إجمالي المبالغ المستحقة 18,955.27 ج.م — راجع حسابات العملاء والتحصيل.</p></div>
        <a href="{{ route('admin.module','sales') }}">عرض الفواتير</a>
    </section>

    <x-admin.dashboard-panel class="collection-panel">
        <div class="compact-panel-head"><div><span>▣</span><h2>تنبيهات التحصيل والاستلام</h2></div><small>آخر تحديث: الآن</small></div>
        <div class="collection-grid">
            <article class="collection-card purple"><span>2</span><div><strong>غير محصل منذ فترة</strong><small>فواتير تحتاج متابعة</small></div><b>18,955.27 ج.م</b></article>
            <article class="collection-card red"><span>1</span><div><strong>جهاز دون قطع غيار</strong><small>بانتظار التوريد</small></div><b>منذ 4 أيام</b></article>
            <article class="collection-card yellow"><span>3</span><div><strong>مستحقات اليوم</strong><small>دفعات موردين</small></div><b>44,700 ج.م</b></article>
            <article class="collection-card teal"><span>8</span><div><strong>جاهز للتسليم الآن</strong><small>أجهزة الصيانة</small></div><b>إرسال تنبيه للعملاء</b></article>
        </div>
    </x-admin.dashboard-panel>
</div>
@endsection
