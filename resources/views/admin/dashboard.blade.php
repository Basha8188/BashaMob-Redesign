@extends('layouts.admin')
@section('title', 'لوحة المعلومات')
@section('content')
<div class="admin-page-head"><div class="page-title-with-icon"><x-admin-icon name="dashboard"/><span><span class="eyebrow">نظرة عامة</span><h1>لوحة المعلومات</h1><p>كل عمليات المحل من مكان واحد.</p></span></div><a class="btn btn-primary" href="{{ route('admin.module','sales') }}"><x-admin-icon name="sales"/> فاتورة بيع جديدة</a></div>
<section class="stats-grid">
    <article class="glass-panel glass-panel--strong glass-panel--interactive"><span>مبيعات اليوم</span><strong>—</strong><small>تظهر بعد تسجيل أول فاتورة</small></article>
    <article class="glass-panel glass-panel--strong glass-panel--interactive"><span>أجهزة الصيانة</span><strong>—</strong><small>لا توجد بيانات حتى الآن</small></article>
    <article class="glass-panel glass-panel--strong glass-panel--interactive"><span>تنبيهات المخزون</span><strong>—</strong><small>تظهر بعد إضافة المنتجات</small></article>
    <article class="glass-panel glass-panel--strong glass-panel--interactive"><span>رصيد الخزنة</span><strong>—</strong><small>يُحسب من الحركات المسجلة</small></article>
</section>
<section class="admin-panel glass-panel"><div class="panel-head"><div><h2>ابدأ إعداد المحل</h2><p>اتبع الخطوات بالترتيب لتجهيز النظام.</p></div></div><div class="setup-grid">
    <a href="{{ route('admin.module','store') }}"><x-admin-icon name="store"/><span><strong>بيانات المحل</strong><small>الاسم والعنوان والهواتف</small></span></a>
    <a href="{{ route('admin.module','products') }}"><x-admin-icon name="products"/><span><strong>إضافة البضاعة</strong><small>المنتجات والأسعار والكميات</small></span></a>
    <a href="{{ route('admin.module','suppliers') }}"><x-admin-icon name="suppliers"/><span><strong>إضافة الموردين</strong><small>بيانات وحسابات الموردين</small></span></a>
    <a href="{{ route('admin.module','sales') }}"><x-admin-icon name="sales"/><span><strong>ابدأ البيع</strong><small>إنشاء أول فاتورة</small></span></a>
</div></section>
<section class="quick-modules"><h2>كل أقسام النظام</h2><div>@foreach(['products'=>['المنتجات','البضاعة والأسعار'],'sales'=>['المبيعات','الفواتير والمدفوعات'],'purchases'=>['المشتريات','الموردون والاستلام'],'inventory'=>['المخزن','الكميات والحركات'],'repairs'=>['الصيانة','التذاكر والحالات'],'customers'=>['العملاء','الملفات والحسابات'],'expenses'=>['الخزنة','المصروفات والورديات'],'reports'=>['التقارير','الأداء والأرباح']] as $key=>$item)<a href="{{ route('admin.module',$key) }}"><x-admin-icon :name="$key"/><strong>{{ $item[0] }}</strong><small>{{ $item[1] }}</small><b>←</b></a>@endforeach</div></section>
@endsection
