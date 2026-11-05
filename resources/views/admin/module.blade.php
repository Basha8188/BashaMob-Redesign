@extends('layouts.admin')
@section('title', $meta[0])
@section('content')
<div class="admin-page-head"><div class="page-title-with-icon"><x-admin-icon :name="$module"/><span><span class="eyebrow">إدارة المحل</span><h1>{{ $meta[0] }}</h1><p>{{ $meta[1] }}</p></span></div><div class="page-actions">@if($module === 'sales')<button class="btn btn-glass" type="button" data-print-invoice><x-admin-icon name="sales"/> طباعة فاتورة</button>@endif<button class="btn btn-primary" type="button" @if($module === 'store') data-store-focus @elseif($module === 'reports') data-report-summary @else data-crud-add data-module="{{ $module }}" @endif>+ {{ $module === 'reports' ? 'عرض التقرير الحالي' : $addLabels[$module] }}</button></div></div>
@if($module === 'sales')
<section class="module-overview" aria-label="ملخص نقطة البيع">
    <article><small>وضع نقطة البيع</small><strong>جاهز</strong></article>
    <article><small>الفواتير المعروضة</small><strong>{{ number_format(count($rows)) }}</strong></article>
    <article><small>إجمالي الصفحة</small><strong>{{ number_format(collect($rows)->sum(fn($row) => (float) str_replace(',', '', $row['cells'][3] ?? 0)), 2) }}</strong></article>
    <article><small>الدفع والطباعة</small><strong>فوري</strong></article>
</section>
<section class="pos-workspace" data-pos-workspace>
    <div class="admin-panel glass-panel pos-catalog">
        <header><div><span class="eyebrow">اختيار سريع</span><h2>كتالوج نقطة البيع</h2></div><x-admin.search-bar class="pos-search" placeholder="ابحث بالاسم أو SKU..." /></header>
        <div class="pos-products">
            @forelse($posProducts as $product)
                <button type="button" data-pos-product data-name="{{ $product->name }}" data-sku="{{ $product->sku }}" data-price="{{ (float) $product->sale_price }}" data-stock="{{ (float) ($product->stocks_sum_quantity ?? 0) }}">
                    <span><x-admin-icon name="products"/></span><b>{{ $product->name }}</b><small>{{ $product->sku }} · متاح {{ number_format((float) ($product->stocks_sum_quantity ?? 0)) }}</small><strong>{{ number_format((float) $product->sale_price,2) }} ج.م</strong>
                </button>
            @empty
                <p class="pos-empty">أضف منتجات للمخزون لتظهر هنا في نقطة البيع.</p>
            @endforelse
        </div>
    </div>
    <aside class="admin-panel glass-panel pos-cart">
        <span class="eyebrow">السلة الحالية</span><h2>فاتورة سريعة</h2>
        <div class="pos-cart-empty" data-pos-cart-empty><x-admin-icon name="sales"/><p>اختر منتجًا من الكتالوج لبدء الفاتورة.</p></div>
        <div class="pos-cart-selection" data-pos-cart-selection hidden><div><b data-pos-name></b><small data-pos-sku></small></div><label>الكمية<input type="number" value="1" min="1" step="1" data-pos-quantity></label><dl><div><dt>الإجمالي</dt><dd data-pos-total>0 ج.م</dd></div><div><dt>الخصم</dt><dd>0 ج.م</dd></div><div><dt>الضريبة</dt><dd>مدمجة</dd></div></dl></div>
        <button class="btn btn-primary" type="button" data-pos-checkout disabled>متابعة الدفع والطباعة</button>
    </aside>
</section>
@elseif($module === 'repairs')
<section class="repair-pipeline" aria-label="مراحل دورة الصيانة">
    @foreach([['01','الاستلام'],['02','الفحص'],['03','الإصلاح'],['04','التسليم']] as $stage)<article><b>{{ $stage[0] }}</b><span>{{ $stage[1] }}</span></article>@endforeach
</section>
@elseif(in_array($module,['products','inventory','customers','suppliers']))
<section class="module-overview" aria-label="ملخص القسم">
    <article><small>السجلات الظاهرة</small><strong>{{ number_format(count($rows)) }}</strong></article>
    <article><small>البحث والتصفية</small><strong>نشط</strong></article>
    <article><small>آخر مزامنة</small><strong>الآن</strong></article>
    <article><small>حالة النظام</small><strong>مستقر</strong></article>
</section>
@endif
<section class="admin-panel module-panel glass-panel">
    <div class="panel-head"><div><h2>{{ $meta[0] }}</h2><p>ابحث وأضف وعدّل بيانات هذا القسم من مكان واحد.</p></div><x-admin.search-bar class="module-search" data-search-scope="module" :placeholder="'بحث في '.$meta[0].'...'" /></div>
    @if($module === 'store')
        <form class="admin-form" data-demo-form action="/admin/store" method="POST">@csrf<div><label>اسم المحل<input name="store_name" required value="{{ $storeSettings['store_name'] ?? 'BashaMob' }}"></label><label>رقم الهاتف<input name="store_phone" required value="{{ $storeSettings['store_phone'] ?? '01012345678' }}"></label></div><div><label>البريد الإلكتروني<input name="store_email" type="email" required value="{{ $storeSettings['store_email'] ?? 'info@bashamob.local' }}"></label><label>الرقم الضريبي<input name="tax_number" value="{{ $storeSettings['tax_number'] ?? '' }}"></label></div><label>العنوان<textarea name="store_address" required>{{ $storeSettings['store_address'] ?? 'القاهرة، مصر' }}</textarea></label><button class="btn btn-primary" type="submit">حفظ بيانات المحل</button></form>
    @elseif(count($rows))
        <div class="demo-notice"><b>بيانات النظام</b><span>البيانات المعروضة محفوظة فعليًا ويمكن إدارتها حسب صلاحيات حسابك.</span></div>
        <div class="admin-table-wrap"><table class="admin-table" data-module="{{ $module }}"><thead><tr>@foreach($headers[$module] as $header)<th>{{ $header }}</th>@endforeach<th></th></tr></thead><tbody>
        @foreach($rows as $row)<tr data-record-id="{{ $row['id'] }}">@foreach($row['cells'] as $cell)<td class="{{ $loop->last ? 'status-cell' : '' }}">@if($module === 'products' && $loop->index === 2)<span class="product-table-name">@if(!empty($row['image_url']))<img src="{{ $row['image_url'] }}" alt="" width="38" height="38" loading="lazy" decoding="async">@else<span class="product-image-placeholder"><x-admin-icon name="products"/></span>@endif<b>{{ $cell }}</b></span>@else{{ $cell }}@endif</td>@endforeach<td><div class="row-actions">@if($module === 'products')<a href="{{ route('admin.products.barcode', $row['id']) }}" target="_blank" data-row-barcode title="عرض وطباعة الباركود">▥</a>@endif @if($module === 'sales')<button type="button" data-row-print title="طباعة الفاتورة">▣</button>@endif @can('receive-purchases')@if($module === 'purchases' && !in_array($row['cells'][count($row['cells']) - 1], ['received','cancelled']))<button type="button" data-row-receive title="استلام البضاعة">استلام</button>@endif @endcan @if($module === 'repairs')<a href="{{ route('admin.repairs.inspections.create',$row['id']) }}" title="فحص الاستلام">فحص</a><a href="{{ route('admin.repairs.quotations.create',$row['id']) }}" title="عرض سعر">عرض</a>@endif @if($module === 'team')@can('incentives.view_all_employees')<a href="{{ route('admin.incentives.employees.show', $row['id']) }}" title="عرض حوافز الموظف"><x-admin-icon name="commission"/></a>@endcan @endif @unless(in_array($module,['sales','purchases','inventory','reports']))<button type="button" data-row-edit title="تعديل">✎</button>@endunless @unless(in_array($module,['inventory','reports','repairs']))<button type="button" data-row-delete title="{{ in_array($module,['sales','purchases']) ? 'إلغاء' : 'حذف' }}">×</button>@endunless</div></td></tr>@endforeach
        </tbody></table></div>
    @else
        <div class="empty-admin"><span>＋</span><h3>القسم جاهز لاستقبال البيانات</h3><p>اضغط «{{ $addLabels[$module] }}» لبدء إدخال {{ $meta[0] }}.</p><button class="btn btn-glass" type="button" @if($module === 'reports') data-report-summary @else data-crud-add data-module="{{ $module }}" @endif>{{ $module === 'reports' ? 'عرض التقرير الحالي' : $addLabels[$module] }}</button></div>
    @endif
</section>
@if($module === 'customers' && $customerRequests->isNotEmpty())
<section class="admin-panel glass-panel customer-request-panel">
    <div class="panel-head"><div><h2>طلبات العملاء</h2><p>راجع طلبات الشراء والصيانة ثم وافق عليها أو ارفضها.</p></div></div>
    <div class="customer-request-grid">
        @foreach($customerRequests as $requestItem)
        <article class="customer-request-card" data-customer-request-card>
            <header><span>{{ $requestItem['type'] === 'maintenance' ? 'طلب صيانة' : 'طلب شراء' }}</span><b>{{ $requestItem['number'] }}</b></header>
            <strong>{{ $requestItem['customer'] }}</strong><p>{{ $requestItem['details'] }}</p>
            <footer><i data-request-status>{{ $requestItem['status'] }}</i>@if($requestItem['status'] === 'pending_review')<span><button class="btn btn-primary" type="button" data-request-decision="approved" data-request-type="{{ $requestItem['type'] }}" data-request-id="{{ $requestItem['id'] }}">موافقة</button><button class="btn btn-glass" type="button" data-request-decision="rejected" data-request-type="{{ $requestItem['type'] }}" data-request-id="{{ $requestItem['id'] }}">رفض</button></span>@endif</footer>
        </article>
        @endforeach
    </div>
</section>
@endif
@endsection
