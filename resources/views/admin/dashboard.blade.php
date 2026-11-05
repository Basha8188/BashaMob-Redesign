@extends('layouts.admin')
@section('title', 'الصفحة الرئيسية')
@section('content')
<div class="reference-dashboard">
    <header class="reference-page-head">
        <div><small class="blessing-line">صلّيت على النبي؟</small><h1>الصفحة الرئيسية</h1><p>إدارة ومتابعة جميع عمليات BashaMob من مكان واحد.</p></div>
        <span class="reference-status"><i></i> نشط الآن</span>
    </header>

    <section class="stats-grid dashboard-stats reference-stats">
        <x-admin.stat-card tone="cyan" icon="repairs" title="طلبات الصيانة المفتوحة" subtitle="قيد المتابعة" :value="number_format($metrics['repairs'])" />
        <x-admin.stat-card tone="pink" icon="purchases" title="إجمالي المشتريات" subtitle="منذ بداية الشهر" :value="number_format($metrics['purchases'],2)" unit="ج.م" />
        <x-admin.stat-card tone="purple" icon="sales" title="فواتير اليوم" subtitle="إجمالي الفواتير" :value="number_format($metrics['invoices_today'])" />
        <x-admin.stat-card tone="gold" icon="sales" title="صافي مبيعات اليوم" subtitle="بعد الخصومات" :value="number_format($metrics['sales_today'],2)" unit="ج.م" />
        <x-admin.stat-card tone="orange" icon="inventory" title="قيمة المخزون" :subtitle="$metrics['stock_value'] === null ? 'التكلفة غير مؤكدة' : 'التكلفة الحالية'" :value="$metrics['stock_value'] === null ? 'غير معروفة' : number_format((float) $metrics['stock_value'],2)" unit="ج.م" />
        <x-admin.stat-card tone="violet" icon="reports" title="متوسط قيمة الفاتورة" subtitle="متوسط اليوم" :value="number_format($metrics['average_invoice'],2)" unit="ج.م" />
        <x-admin.stat-card tone="blue" icon="expenses" title="المبالغ المستحقة" subtitle="غير محصلة" :value="number_format($metrics['due'],2)" unit="ج.م" />
        <x-admin.stat-card tone="amber" icon="purchases" title="المرتجعات المسجلة" subtitle="خلال اليوم" :value="number_format($metrics['returns_today'])" />
    </section>

    <section class="dashboard-insights">
        <article class="admin-panel glass-panel violet-chart">
            <header class="violet-chart__head"><div><span class="eyebrow">أداء آخر 7 أيام</span><h2>حركة المبيعات</h2><p>قراءة سريعة لإجمالي الفواتير المكتملة يوميًا.</p></div><a class="btn btn-glass" href="{{ route('admin.module','reports') }}">التقرير الكامل</a></header>
            <div class="chart-bars" aria-label="رسم مبيعات آخر سبعة أيام">
                @foreach($salesTrend as $point)<span style="--value:{{ $point['height'] }}%" data-day="{{ $point['day'] }}" title="{{ number_format($point['total'],2) }} ج.م"></span>@endforeach
            </div>
        </article>
        <article class="admin-panel glass-panel dashboard-feed">
            <h2>أحدث المبيعات</h2>
            @forelse($recentSales as $sale)
                <div class="dashboard-feed__item"><span><x-admin-icon name="sales"/></span><div><b>{{ $sale->invoice_number }}</b><small>{{ $sale->customer?->name ?? 'عميل نقدي' }} · {{ $sale->created_at->diffForHumans() }}</small></div><strong>{{ number_format((float) $sale->total,2) }} ج.م</strong></div>
            @empty
                <div class="dashboard-feed__item"><span><x-admin-icon name="sales"/></span><div><b>لا توجد فواتير بعد</b><small>ستظهر أحدث عملية بيع هنا.</small></div></div>
            @endforelse
        </article>
    </section>

    <section class="module-overview" aria-label="ملخص الصيانة والمخزون">
        @foreach($repairStages as $stage)<article><small>{{ $stage['label'] }}</small><strong>{{ number_format($stage['count']) }}</strong></article>@endforeach
    </section>

    <section class="reference-notice glass-panel">
        <span class="notice-icon">▣</span>
        <div><strong>فواتير معلقة بدون سداد: {{ $metrics['due_count'] }}</strong><p>إجمالي المبالغ المستحقة {{ number_format($metrics['due'],2) }} ج.م — راجع حسابات العملاء والتحصيل.</p></div>
        <a href="{{ route('admin.module','sales') }}">عرض الفواتير</a>
    </section>

    <x-admin.dashboard-panel class="collection-panel">
        <div class="compact-panel-head"><div><span>▣</span><h2>تنبيهات التحصيل والاستلام</h2></div><small>آخر تحديث: الآن</small></div>
        <div class="collection-grid">
            <article class="collection-card purple"><span>{{ $metrics['due_count'] }}</span><div><strong>فواتير غير محصلة</strong><small>تحتاج متابعة التحصيل</small></div><b>{{ number_format($metrics['due'],2) }} ج.م</b></article>
            <article class="collection-card red"><span>{{ $lowStock->count() }}</span><div><strong>مخزون تحت حد الطلب</strong><small>{{ $lowStock->pluck('product.name')->filter()->join('، ') ?: 'لا توجد تنبيهات حالية' }}</small></div><b>مراجعة المخزون</b></article>
            <article class="collection-card yellow"><span>{{ $metrics['pending_purchases'] }}</span><div><strong>مشتريات قيد الاستلام</strong><small>طلبات موردين لم تكتمل</small></div><b>متابعة التوريد</b></article>
            <article class="collection-card teal"><span>{{ $metrics['ready_repairs'] }}</span><div><strong>جاهز للتسليم الآن</strong><small>أجهزة الصيانة المكتملة</small></div><b>إرسال تنبيه للعملاء</b></article>
        </div>
    </x-admin.dashboard-panel>
</div>
@endsection
