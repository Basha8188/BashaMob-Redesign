@extends('layouts.admin')
@section('title', $meta[0])
@section('content')
<div class="admin-page-head"><div class="page-title-with-icon"><x-admin-icon :name="$module"/><span><span class="eyebrow">إدارة المحل</span><h1>{{ $meta[0] }}</h1><p>{{ $meta[1] }}</p></span></div><div class="page-actions">@if($module === 'sales')<button class="btn btn-glass" type="button" data-print-invoice><x-admin-icon name="sales"/> طباعة فاتورة</button>@endif<button class="btn btn-primary" type="button" data-crud-add data-module="{{ $module }}">+ إضافة جديد</button></div></div>
<section class="admin-panel module-panel glass-panel">
    <div class="panel-head"><div><h2>{{ $meta[0] }}</h2><p>لم تتم إضافة بيانات في هذا القسم بعد.</p></div><label class="admin-search">⌕ <input type="search" placeholder="بحث..."></label></div>
    @if($module === 'store')
        <form class="admin-form" data-demo-form><div><label>اسم المحل<input value="BashaMob"></label><label>رقم الهاتف<input value="01012345678"></label></div><div><label>البريد الإلكتروني<input type="email" value="info@bashamob.local"></label><label>الرقم الضريبي<input value="123-456-789"></label></div><label>العنوان<textarea>القاهرة، مصر — شارع التقنية، محل 12</textarea></label><button class="btn btn-primary" type="submit">حفظ بيانات المحل</button></form>
    @elseif(count($rows))
        <div class="demo-notice"><b>بيانات تجريبية</b><span>هذه الأمثلة للمعاينة فقط ويمكن استبدالها ببيانات المحل الحقيقية لاحقًا.</span></div>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>#</th><th>البيان الرئيسي</th><th>التفاصيل</th><th>القيمة / التاريخ</th><th>الحالة</th><th></th></tr></thead><tbody>
        @foreach($rows as $row)<tr data-demo-row>@foreach($row as $index=>$cell)<td class="{{ $index === 4 ? 'status-cell' : '' }}">{{ $cell }}</td>@endforeach<td><div class="row-actions">@if($module === 'sales')<button type="button" data-row-print title="طباعة الفاتورة">▣</button>@endif<button type="button" data-row-edit title="تعديل">✎</button><button type="button" data-row-delete title="حذف">×</button></div></td></tr>@endforeach
        </tbody></table></div>
    @else
        <div class="empty-admin"><span>＋</span><h3>القسم جاهز لاستقبال البيانات</h3><p>اضغط «إضافة جديد» لبدء إدخال {{ $meta[0] }}.</p><button class="btn btn-glass" type="button">إضافة أول سجل</button></div>
    @endif
</section>
@endsection
