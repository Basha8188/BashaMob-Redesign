@extends('layouts.admin')

@section('title', $isOwn ? 'حوافزي' : 'حوافز '.$employee->name)

@section('content')
<div class="admin-page-head">
    <div class="page-title-with-icon">
        <x-admin-icon name="commission"/>
        <span>
            <span class="eyebrow">{{ $isOwn ? 'بياناتك المالية فقط' : 'عرض مصرح لبيانات الموظف' }}</span>
            <h1>{{ $isOwn ? 'حوافزي' : 'حوافز '.$employee->name }}</h1>
            <p>المبيعات والمرتجعات والهدف والعمولة كما سجلها محرك الحوافز لكل فترة.</p>
        </span>
    </div>
    <div class="page-actions">
        @can('incentives.view')
            <a class="btn btn-glass" href="{{ route('admin.incentives.index') }}">لوحة الحوافز</a>
        @endcan
    </div>
</div>

<section class="admin-panel glass-panel module-panel">
    <div class="panel-head">
        <div><h2>الفترة المعروضة</h2><p>اختر فترة تم حسابها لهذا الموظف.</p></div>
        <form method="GET" action="{{ $isOwn ? route('admin.incentives.mine') : route('admin.incentives.employees.show', $employee) }}">
            <label>
                <span class="sr-only">الفترة</span>
                <select name="period" onchange="this.form.submit()">
                    @forelse($periods as $period)
                        <option value="{{ $period->id }}" @selected($selectedPeriod?->is($period))>{{ $period->name }} — {{ $period->starts_on->format('Y-m-d') }}</option>
                    @empty
                        <option value="">لا توجد فترة محسوبة</option>
                    @endforelse
                </select>
            </label>
        </form>
    </div>
</section>

<section class="stats-grid" aria-label="ملخص حوافز الموظف">
    <x-admin.stat-card tone="cyan" icon="sales" title="إجمالي المبيعات" subtitle="قبل المرتجعات" :value="number_format($metrics['gross_sales'], 2)" unit="ج.م" />
    <x-admin.stat-card tone="purple" icon="sales" title="صافي المبيعات" subtitle="بعد المرتجعات" :value="number_format($metrics['net_sales'], 2)" unit="ج.م" />
    <x-admin.stat-card tone="amber" icon="purchases" title="المرتجعات" subtitle="داخل الفترة" :value="number_format($metrics['returns_total'], 2)" unit="ج.م" />
    <x-admin.stat-card tone="gold" icon="reports" title="الربح المؤهل" subtitle="أساس الربح المحتسب" :value="number_format($metrics['eligible_profit'], 2)" unit="ج.م" />
    <x-admin.stat-card tone="violet" icon="incentives" title="الهدف" subtitle="قيمة الهدف" :value="number_format($metrics['target_amount'], 2)" unit="ج.م" />
    <x-admin.stat-card tone="blue" icon="commission" title="العمولة المقدرة" subtitle="قبل الاعتماد" :value="number_format($metrics['estimated_commission'], 2)" unit="ج.م" />
    <x-admin.stat-card tone="orange" icon="commission" title="العمولة المعتمدة" subtitle="القيمة المعتمدة" :value="number_format($metrics['approved_commission'], 2)" unit="ج.م" />
    <x-admin.stat-card tone="pink" icon="expenses" title="المكافآت / الخصومات" subtitle="داخل الفترة" :value="number_format($metrics['bonuses'] - $metrics['penalties'], 2)" unit="ج.م" />
</section>

<section class="admin-panel glass-panel module-panel">
    <div class="panel-head"><div><h2>تقدم الأهداف</h2><p>تفاصيل كل خطة مرتبطة بالفترة المعروضة.</p></div></div>
    @forelse($summaries as $summary)
        <article class="notification-card">
            <header>
                <div><strong>{{ $summary->plan->name }}</strong><small>{{ $summary->period->name }}</small></div>
                <x-admin.status-badge :tone="$summary->status === 'approved' ? 'success' : 'warning'">{{ $summary->status }}</x-admin.status-badge>
            </header>
            <p>نسبة الإنجاز: <strong>{{ $summary->achievement_percent !== null ? number_format((float) $summary->achievement_percent, 2).'%' : 'لا يوجد هدف' }}</strong></p>
            @if($summary->achievement_percent !== null)
                <progress max="100" value="{{ min(100, max(0, (float) $summary->achievement_percent)) }}">{{ $summary->achievement_percent }}%</progress>
            @endif
        </article>
    @empty
        <div class="empty-admin"><span>∅</span><h3>لا توجد نتيجة محسوبة</h3><p>بعد ربط خطة فعالة وتشغيل الفترة ستظهر الأرقام هنا.</p></div>
    @endforelse
</section>

<section class="admin-panel glass-panel module-panel">
    <div class="panel-head"><div><h2>تفاصيل العمليات</h2><p>كل حركة والقاعدة التي تم تطبيقها عليها، بدون إظهار بيانات موظفين آخرين.</p></div></div>
    @if($transactions->count())
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>المصدر</th><th>الخطة</th><th>القاعدة</th><th>أساس الحساب</th><th>القيمة المؤهلة</th><th>العمولة</th><th>أثر المرتجع</th><th>الحالة</th></tr></thead>
                <tbody>
                    @foreach($transactions as $transaction)
                    <tr>
                        <td>{{ data_get($transaction->snapshot, 'invoice_number', data_get($transaction->snapshot, 'return_number', '#'.$transaction->source_id)) }}</td>
                        <td>{{ $transaction->plan->name }}</td>
                        <td>{{ $transaction->rule->name }}</td>
                        <td>{{ $transaction->calculation_basis }}</td>
                        <td>{{ number_format((float) $transaction->base_amount, 2) }} ج.م</td>
                        <td>{{ number_format((float) $transaction->calculated_amount, 2) }} ج.م</td>
                        <td>{{ number_format((float) $transaction->return_impact, 2) }} ج.م</td>
                        <td><x-admin.status-badge :tone="$transaction->status === 'approved' ? 'success' : 'warning'">{{ $transaction->status }}</x-admin.status-badge></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $transactions->links() }}
    @else
        <div class="empty-admin"><span>∅</span><h3>لا توجد عمليات في الفترة</h3><p>قد تكون الفترة لم تُحسب بعد أو لا توجد مبيعات مؤهلة.</p></div>
    @endif
</section>
@endsection
