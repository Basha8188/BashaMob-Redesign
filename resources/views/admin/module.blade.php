@extends('layouts.admin')
@section('title', $meta[0])
@section('content')
@php
    $addLabels = ['store'=>'تعديل بيانات المحل','products'=>'إضافة منتج','sales'=>'فاتورة بيع جديدة','purchases'=>'فاتورة شراء جديدة','inventory'=>'إضافة حركة مخزون','repairs'=>'تذكرة صيانة جديدة','customers'=>'إضافة عميل','suppliers'=>'إضافة مورد','expenses'=>'إضافة حركة مالية','reports'=>'إنشاء تقرير','team'=>'إضافة موظف','settings'=>'إضافة إعداد'];
    $headers = [
        'products'=>['كود المنتج','اسم المنتج','الماركة','سعر البيع','الكمية'], 'sales'=>['رقم الفاتورة','العميل','التاريخ','الإجمالي','الحالة'],
        'purchases'=>['رقم الفاتورة','المورد','التاريخ','الإجمالي','الحالة'], 'inventory'=>['المخزن','المنتج','الرصيد الحالي','حد الطلب','الحالة'],
        'repairs'=>['رقم التذكرة','العميل','الجهاز','العطل','الحالة'], 'customers'=>['اسم العميل','رقم الهاتف','البريد الإلكتروني','السجل','الحالة'],
        'suppliers'=>['اسم المورد','رقم الهاتف','الرصيد','حالة الحساب'], 'expenses'=>['رقم الحركة','البيان','التاريخ','القيمة','النوع'],
        'reports'=>['اسم التقرير','النطاق','الإجمالي','الإجراء','الحالة'], 'team'=>['اسم الموظف','البريد الإلكتروني','الدور','الصلاحيات','الحالة'],
        'settings'=>['القسم','الإعداد','القيمة','الإجراء','الحالة'],
    ];
@endphp
<div class="admin-page-head"><div class="page-title-with-icon"><x-admin-icon :name="$module"/><span><span class="eyebrow">إدارة المحل</span><h1>{{ $meta[0] }}</h1><p>{{ $meta[1] }}</p></span></div><div class="page-actions">@if($module === 'sales')<button class="btn btn-glass" type="button" data-print-invoice><x-admin-icon name="sales"/> طباعة فاتورة</button>@endif<button class="btn btn-primary" type="button" @if($module === 'store') data-store-focus @else data-crud-add data-module="{{ $module }}" @endif>+ {{ $addLabels[$module] }}</button></div></div>
<section class="admin-panel module-panel glass-panel">
    <div class="panel-head"><div><h2>{{ $meta[0] }}</h2><p>ابحث وأضف وعدّل بيانات هذا القسم من مكان واحد.</p></div><x-admin.search-bar class="module-search" placeholder="ابحث في النظام..." /></div>
    @if($module === 'store')
        <form class="admin-form" data-demo-form><div><label>اسم المحل<input value="BashaMob"></label><label>رقم الهاتف<input value="01012345678"></label></div><div><label>البريد الإلكتروني<input type="email" value="info@bashamob.local"></label><label>الرقم الضريبي<input value="123-456-789"></label></div><label>العنوان<textarea>القاهرة، مصر — شارع التقنية، محل 12</textarea></label><button class="btn btn-primary" type="submit">حفظ بيانات المحل</button></form>
    @elseif(count($rows))
        <div class="demo-notice"><b>بيانات تجريبية</b><span>هذه الأمثلة للمعاينة فقط ويمكن استبدالها ببيانات المحل الحقيقية لاحقًا.</span></div>
        <div class="admin-table-wrap"><table class="admin-table" data-module="{{ $module }}"><thead><tr>@foreach($headers[$module] as $header)<th>{{ $header }}</th>@endforeach<th></th></tr></thead><tbody>
        @foreach($rows as $row)<tr data-demo-row>@foreach($row as $cell)<td class="{{ $loop->last ? 'status-cell' : '' }}">{{ $cell }}</td>@endforeach<td><div class="row-actions">@if($module === 'sales')<button type="button" data-row-print title="طباعة الفاتورة">▣</button>@endif<button type="button" data-row-edit title="تعديل">✎</button><button type="button" data-row-delete title="حذف">×</button></div></td></tr>@endforeach
        </tbody></table></div>
    @else
        <div class="empty-admin"><span>＋</span><h3>القسم جاهز لاستقبال البيانات</h3><p>اضغط «{{ $addLabels[$module] }}» لبدء إدخال {{ $meta[0] }}.</p><button class="btn btn-glass" type="button" data-crud-add data-module="{{ $module }}">{{ $addLabels[$module] }}</button></div>
    @endif
</section>
@endsection
