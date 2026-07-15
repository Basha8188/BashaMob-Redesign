@extends('layouts.admin')
@section('title', $meta[0])
@section('content')
<div class="admin-page-head"><div class="page-title-with-icon"><x-admin-icon :name="$module"/><span><span class="eyebrow">إدارة المحل</span><h1>{{ $meta[0] }}</h1><p>{{ $meta[1] }}</p></span></div><div class="page-actions">@if($module === 'sales')<button class="btn btn-glass" type="button" data-print-invoice><x-admin-icon name="sales"/> طباعة فاتورة</button>@endif<button class="btn btn-primary" type="button" @if($module === 'store') data-store-focus @elseif($module === 'reports') data-report-summary @else data-crud-add data-module="{{ $module }}" @endif>+ {{ $module === 'reports' ? 'عرض التقرير الحالي' : $addLabels[$module] }}</button></div></div>
<section class="admin-panel module-panel glass-panel">
    <div class="panel-head"><div><h2>{{ $meta[0] }}</h2><p>ابحث وأضف وعدّل بيانات هذا القسم من مكان واحد.</p></div><x-admin.search-bar class="module-search" data-search-scope="module" :placeholder="'بحث في '.$meta[0].'...'" /></div>
    @if($module === 'store')
        <form class="admin-form" data-demo-form action="/admin/store" method="POST">@csrf<div><label>اسم المحل<input name="store_name" required value="{{ $storeSettings['store_name'] ?? 'BashaMob' }}"></label><label>رقم الهاتف<input name="store_phone" required value="{{ $storeSettings['store_phone'] ?? '01012345678' }}"></label></div><div><label>البريد الإلكتروني<input name="store_email" type="email" required value="{{ $storeSettings['store_email'] ?? 'info@bashamob.local' }}"></label><label>الرقم الضريبي<input name="tax_number" value="{{ $storeSettings['tax_number'] ?? '' }}"></label></div><label>العنوان<textarea name="store_address" required>{{ $storeSettings['store_address'] ?? 'القاهرة، مصر' }}</textarea></label><button class="btn btn-primary" type="submit">حفظ بيانات المحل</button></form>
    @elseif(count($rows))
        <div class="demo-notice"><b>بيانات النظام</b><span>البيانات المعروضة محفوظة فعليًا ويمكن إدارتها حسب صلاحيات حسابك.</span></div>
        <div class="admin-table-wrap"><table class="admin-table" data-module="{{ $module }}"><thead><tr>@foreach($headers[$module] as $header)<th>{{ $header }}</th>@endforeach<th></th></tr></thead><tbody>
        @foreach($rows as $row)<tr data-record-id="{{ $row['id'] }}">@foreach($row['cells'] as $cell)<td class="{{ $loop->last ? 'status-cell' : '' }}">{{ $cell }}</td>@endforeach<td><div class="row-actions">@if($module === 'sales')<button type="button" data-row-print title="طباعة الفاتورة">▣</button>@endif @unless(in_array($module,['sales','purchases','inventory','reports']))<button type="button" data-row-edit title="تعديل">✎</button>@endunless @unless(in_array($module,['inventory','reports','repairs']))<button type="button" data-row-delete title="{{ in_array($module,['sales','purchases']) ? 'إلغاء' : 'حذف' }}">×</button>@endunless</div></td></tr>@endforeach
        </tbody></table></div>
    @else
        <div class="empty-admin"><span>＋</span><h3>القسم جاهز لاستقبال البيانات</h3><p>اضغط «{{ $addLabels[$module] }}» لبدء إدخال {{ $meta[0] }}.</p><button class="btn btn-glass" type="button" @if($module === 'reports') data-report-summary @else data-crud-add data-module="{{ $module }}" @endif>{{ $module === 'reports' ? 'عرض التقرير الحالي' : $addLabels[$module] }}</button></div>
    @endif
</section>
@endsection
