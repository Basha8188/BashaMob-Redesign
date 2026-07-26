@extends('layouts.admin')

@section('title', 'الحوافز والعمولات')

@section('content')
<div class="admin-page-head">
    <div class="page-title-with-icon">
        <x-admin-icon name="incentives"/>
        <span>
            <span class="eyebrow">محرك مالي قابل للتدقيق</span>
            <h1>الحوافز والعمولات</h1>
            <p>أنشئ خطة وقاعدة حساب، اربطها بالموظفين، ثم احسب كل فترة دون نسب ثابتة داخل الكود.</p>
        </span>
    </div>
    <div class="page-actions">
        @canany(['incentives.view_own', 'incentives.view_all_employees'])
            <a class="btn btn-glass" href="{{ route('admin.incentives.mine') }}"><x-admin-icon name="commission"/> حوافزي</a>
        @endcanany
    </div>
</div>

@if(session('success'))
    <div class="demo-notice" role="status"><b>{{ session('success') }}</b></div>
@endif

@if($errors->any())
    <div class="demo-notice" role="alert">
        <b>راجع البيانات التالية:</b>
        <ul>
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<section class="module-overview" aria-label="ملخص الحوافز">
    <article><small>إجمالي الخطط</small><strong>{{ number_format($planCounts['all']) }}</strong></article>
    <article><small>الخطط الفعالة</small><strong>{{ number_format($planCounts['active']) }}</strong></article>
    <article><small>قواعد الحساب</small><strong>{{ number_format($planCounts['rules']) }}</strong></article>
    <article><small>فترات الاحتساب</small><strong>{{ number_format($planCounts['periods']) }}</strong></article>
</section>

<section class="stats-grid" aria-label="قيم العمولات">
    <x-admin.stat-card tone="purple" icon="commission" title="العمولة المقدرة" subtitle="كل الفترات المحسوبة" :value="number_format((float) $commissionTotals['estimated'], 2)" unit="ج.م" />
    <x-admin.stat-card tone="cyan" icon="incentives" title="العمولة المعتمدة" subtitle="قيم معتمدة حتى الآن" :value="number_format((float) $commissionTotals['approved'], 2)" unit="ج.م" />
</section>

@can('incentives.manage_plans')
@can('incentives.manage_rules')
<section class="admin-panel glass-panel module-panel">
    <div class="panel-head">
        <div><span class="eyebrow">إعداد متكامل في خطوة واحدة</span><h2>إنشاء خطة وربطها وإضافة أول قاعدة</h2><p>كل الأرقام قابلة للتعديل. لا توجد نسبة افتراضية مفروضة.</p></div>
    </div>
    <form class="admin-form" method="POST" action="{{ route('admin.incentives.plans.store') }}">
        @csrf
        <x-admin.section-title kicker="01" title="بيانات الخطة"/>
        <div>
            <label>اسم الخطة<input name="name" value="{{ old('name') }}" required maxlength="160" placeholder="مثال: خطة مبيعات الفرع الرئيسي"></label>
            <label>كود الخطة<input name="code" value="{{ old('code') }}" required maxlength="60" pattern="[A-Za-z0-9_-]+" placeholder="MAIN_SALES_2026" dir="ltr"></label>
        </div>
        <label>الوصف<textarea name="description" maxlength="3000" placeholder="اشرح الغرض من الخطة وطريقة استخدامها">{{ old('description') }}</textarea></label>
        <div>
            <label>الحالة
                <select name="status" required>
                    <option value="draft" @selected(old('status', 'draft') === 'draft')>مسودة</option>
                    <option value="active" @selected(old('status') === 'active')>فعالة</option>
                    <option value="inactive" @selected(old('status') === 'inactive')>متوقفة</option>
                </select>
            </label>
            <label>دورية الخطة
                <select name="period_type" required>
                    @foreach(['daily'=>'يومي','weekly'=>'أسبوعي','monthly'=>'شهري','quarterly'=>'ربع سنوي','yearly'=>'سنوي','custom'=>'مخصص'] as $value=>$label)
                        <option value="{{ $value }}" @selected(old('period_type', 'monthly') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div>
            <label>حل تعارض القواعد
                <select name="conflict_resolution" required>
                    <option value="sum_all" @selected(old('conflict_resolution', 'sum_all') === 'sum_all')>جمع كل القواعد</option>
                    <option value="highest_only" @selected(old('conflict_resolution') === 'highest_only')>الأعلى فقط</option>
                    <option value="first_match" @selected(old('conflict_resolution') === 'first_match')>أول قاعدة مطابقة</option>
                    <option value="exclusive_rule" @selected(old('conflict_resolution') === 'exclusive_rule')>قاعدة حصرية</option>
                </select>
            </label>
            <label>أساس الهدف
                <select name="target_basis">
                    <option value="">بدون هدف حاليًا</option>
                    @foreach(['gross_sales'=>'إجمالي المبيعات','net_sales'=>'صافي المبيعات','gross_profit'=>'مجمل الربح','invoice_count'=>'عدد الفواتير'] as $value=>$label)
                        <option value="{{ $value }}" @selected(old('target_basis') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div>
            <label>قيمة الهدف<input name="target_amount" type="number" min="0" step="0.0001" value="{{ old('target_amount') }}" inputmode="decimal"></label>
            <label>أسباب مرتجع لا تؤثر على العمولة<textarea name="ignored_return_reasons" maxlength="3000" placeholder="سبب في كل سطر، مثل: عيب مصنعي">{{ old('ignored_return_reasons') }}</textarea></label>
        </div>
        <div>
            <label>تاريخ بداية الخطة<input name="starts_on" type="date" value="{{ old('starts_on') }}"></label>
            <label>تاريخ نهاية الخطة<input name="ends_on" type="date" value="{{ old('ends_on') }}"></label>
        </div>

        <x-admin.section-title kicker="02" title="نطاق الموظفين"/>
        <div>
            <label>نوع الربط
                <select name="assignment_scope" required>
                    <option value="all_staff" @selected(old('assignment_scope', 'all_staff') === 'all_staff')>كل الموظفين</option>
                    <option value="employee" @selected(old('assignment_scope') === 'employee')>موظف محدد</option>
                    <option value="role" @selected(old('assignment_scope') === 'role')>دور وظيفي</option>
                </select>
            </label>
            <label>أولوية الربط<input name="assignment_priority" type="number" min="1" max="10000" value="{{ old('assignment_priority', 100) }}" required></label>
        </div>
        <div>
            <label>الموظف
                <select name="employee_id">
                    <option value="">يستخدم عند اختيار موظف محدد</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((string) old('employee_id') === (string) $employee->id)>{{ $employee->name }} — {{ $employee->getRoleNames()->join('، ') }}</option>
                    @endforeach
                </select>
            </label>
            <label>الدور الوظيفي
                <select name="role_name">
                    <option value="">يستخدم عند اختيار دور وظيفي</option>
                    @foreach(['Owner','Manager','Cashier','Technician','Inventory Clerk','Accountant'] as $role)
                        <option value="{{ $role }}" @selected(old('role_name') === $role)>{{ $role }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <x-admin.section-title kicker="03" title="قاعدة العمولة الأساسية"/>
        <div>
            <label>اسم القاعدة<input name="rule_name" value="{{ old('rule_name') }}" required maxlength="160" placeholder="مثال: نسبة صافي المبيعات"></label>
            <label>أولوية القاعدة<input name="rule_priority" type="number" min="1" max="10000" value="{{ old('rule_priority', 100) }}" required></label>
        </div>
        <label>وصف القاعدة<textarea name="rule_description" maxlength="3000">{{ old('rule_description') }}</textarea></label>
        <div>
            <label>أساس الحساب
                <select name="calculation_basis" required>
                    @foreach(['gross_sales'=>'إجمالي المبيعات','net_sales'=>'صافي المبيعات','gross_profit'=>'مجمل الربح','net_profit'=>'صافي الربح','invoice_count'=>'عدد الفواتير','item_quantity'=>'عدد الوحدات','collected_amount'=>'المبلغ المحصل','category_sales'=>'مبيعات فئة','product_sales'=>'مبيعات منتج'] as $value=>$label)
                        <option value="{{ $value }}" @selected(old('calculation_basis', 'net_sales') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>طريقة الحساب
                <select name="calculation_method" required>
                    <option value="percentage" @selected(old('calculation_method', 'percentage') === 'percentage')>نسبة مئوية</option>
                    <option value="fixed_per_invoice" @selected(old('calculation_method') === 'fixed_per_invoice')>مبلغ لكل فاتورة</option>
                    <option value="fixed_per_item" @selected(old('calculation_method') === 'fixed_per_item')>مبلغ لكل صنف</option>
                    <option value="fixed_per_unit" @selected(old('calculation_method') === 'fixed_per_unit')>مبلغ لكل وحدة</option>
                </select>
            </label>
        </div>
        <div>
            <label>النسبة المئوية<input name="rate" type="number" min="0" max="100" step="0.000001" value="{{ old('rate') }}" inputmode="decimal" placeholder="تُملأ مع طريقة النسبة"></label>
            <label>المبلغ الثابت<input name="fixed_amount" type="number" min="0" step="0.0001" value="{{ old('fixed_amount') }}" inputmode="decimal" placeholder="يُملأ مع الطرق الثابتة"></label>
        </div>
        <div>
            <label>الحد الأدنى لأساس الحساب<input name="minimum_basis" type="number" min="0" step="0.0001" value="{{ old('minimum_basis') }}"></label>
            <label>أقصى أساس مؤهل<input name="maximum_eligible_basis" type="number" min="0" step="0.0001" value="{{ old('maximum_eligible_basis') }}"></label>
        </div>
        <div>
            <label>الحد الأدنى للعمولة<input name="minimum_commission" type="number" min="0" step="0.0001" value="{{ old('minimum_commission') }}"></label>
            <label>الحد الأقصى للعمولة<input name="maximum_commission" type="number" min="0" step="0.0001" value="{{ old('maximum_commission') }}"></label>
        </div>
        <div>
            <label>تأثير المرتجع
                <select name="return_impact_mode" required>
                    <option value="reverse_full" @selected(old('return_impact_mode', 'reverse_full') === 'reverse_full')>عكس العمولة بنسبة المرتجع</option>
                    <option value="reverse_percentage" @selected(old('return_impact_mode') === 'reverse_percentage')>عكس نسبة مخصصة</option>
                    <option value="ignore" @selected(old('return_impact_mode') === 'ignore')>لا يؤثر</option>
                </select>
            </label>
            <label>نسبة تأثير المرتجع<input name="return_impact_rate" type="number" min="0" max="100" step="0.000001" value="{{ old('return_impact_rate') }}" placeholder="عند اختيار نسبة مخصصة"></label>
        </div>
        <div>
            <label><input type="hidden" name="is_stackable" value="0"><input name="is_stackable" type="checkbox" value="1" @checked((bool) old('is_stackable', true))> يمكن جمعها مع قواعد أخرى</label>
            <label><input type="hidden" name="stops_processing" value="0"><input name="stops_processing" type="checkbox" value="1" @checked((bool) old('stops_processing', false))> توقف معالجة القواعد التالية</label>
        </div>
        <button class="btn btn-primary" type="submit">حفظ الخطة والربط والقاعدة</button>
    </form>
</section>
@endcan
@endcan

@can('incentives.calculate')
<section class="admin-panel glass-panel module-panel">
    <div class="panel-head"><div><span class="eyebrow">فترة قابلة لإعادة الحساب</span><h2>إنشاء فترة احتساب</h2><p>أنشئ الفترة أولًا، ثم شغّل الحساب من جدول الفترات.</p></div></div>
    <form class="admin-form" method="POST" action="{{ route('admin.incentives.periods.store') }}">
        @csrf
        <div>
            <label>اسم الفترة<input name="period_name" value="{{ old('period_name') }}" required maxlength="160" placeholder="مثال: يوليو 2026"></label>
            <label>نوع الفترة
                <select name="calculation_period_type" required>
                    @foreach(['daily'=>'يومي','weekly'=>'أسبوعي','monthly'=>'شهري','quarterly'=>'ربع سنوي','yearly'=>'سنوي','custom'=>'مخصص'] as $value=>$label)
                        <option value="{{ $value }}" @selected(old('calculation_period_type', 'monthly') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div>
            <label>من<input name="period_starts_on" type="date" value="{{ old('period_starts_on') }}" required></label>
            <label>إلى<input name="period_ends_on" type="date" value="{{ old('period_ends_on') }}" required></label>
        </div>
        <button class="btn btn-primary" type="submit">إنشاء فترة الاحتساب</button>
    </form>
</section>
@endcan

<section class="admin-panel glass-panel module-panel">
    <div class="panel-head"><div><h2>خطط الحوافز</h2><p>الخطط المحفوظة وعدد الروابط والقواعد داخل كل خطة.</p></div></div>
    @if($plans->count())
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>الخطة</th><th>الكود</th><th>الحالة</th><th>الدورية</th><th>الروابط</th><th>القواعد</th><th>الفترة</th></tr></thead>
                <tbody>
                    @foreach($plans as $plan)
                    <tr>
                        <td><strong>{{ $plan->name }}</strong><small>{{ $plan->description }}</small></td>
                        <td dir="ltr">{{ $plan->code }}</td>
                        <td><x-admin.status-badge :tone="$plan->status === 'active' ? 'success' : ($plan->status === 'draft' ? 'warning' : 'danger')">{{ $plan->status }}</x-admin.status-badge></td>
                        <td>{{ $plan->period_type }}</td>
                        <td>{{ number_format($plan->assignments_count) }}</td>
                        <td>{{ number_format($plan->rules_count) }}</td>
                        <td>{{ $plan->starts_on?->format('Y-m-d') ?? 'مفتوحة' }} — {{ $plan->ends_on?->format('Y-m-d') ?? 'مفتوحة' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $plans->links() }}
    @else
        <div class="empty-admin"><span>＋</span><h3>لا توجد خطة حوافز بعد</h3><p>استخدم النموذج أعلاه لإنشاء أول خطة حقيقية.</p></div>
    @endif
</section>

<section class="admin-panel glass-panel module-panel">
    <div class="panel-head"><div><h2>فترات الاحتساب</h2><p>حالة كل فترة وعدد الموظفين والحركات المحتسبة.</p></div></div>
    @if($periods->count())
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>الفترة</th><th>النطاق</th><th>الحالة</th><th>الموظفون</th><th>الحركات</th><th>آخر حساب</th><th>الإجراء</th></tr></thead>
                <tbody>
                    @foreach($periods as $period)
                    <tr>
                        <td><strong>{{ $period->name }}</strong><small>{{ $period->period_type }}</small></td>
                        <td>{{ $period->starts_on->format('Y-m-d') }} — {{ $period->ends_on->format('Y-m-d') }}</td>
                        <td><x-admin.status-badge :tone="$period->status === 'calculated' ? 'success' : 'warning'">{{ $period->status }}</x-admin.status-badge></td>
                        <td>{{ number_format($period->summaries_count) }}</td>
                        <td>{{ number_format($period->transactions_count) }}</td>
                        <td>{{ $period->calculated_at?->format('Y-m-d H:i') ?? 'لم يُحسب' }}</td>
                        <td>
                            @can('incentives.calculate')
                            <form method="POST" action="{{ route('admin.incentives.periods.calculate', $period) }}">@csrf<button class="btn btn-glass" type="submit">تشغيل الحساب</button></form>
                            @else — @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $periods->links() }}
    @else
        <div class="empty-admin"><span>＋</span><h3>لا توجد فترة احتساب</h3><p>أنشئ فترة ثم شغّل الحساب بعد تفعيل خطة واحدة على الأقل.</p></div>
    @endif
</section>

@can('incentives.view_all_employees')
<section class="admin-panel glass-panel module-panel">
    <div class="panel-head"><div><h2>حوافز الموظفين</h2><p>اختيار موظف يفتح بياناته المحسوبة فقط، وفق صلاحية عرض الجميع.</p></div><x-admin.search-bar class="module-search" placeholder="ابحث عن موظف..." /></div>
    <div class="quick-actions">
        @foreach($employees as $employee)
            <x-admin.quick-action :href="route('admin.incentives.employees.show', $employee)" icon="commission" :title="$employee->name" :description="$employee->getRoleNames()->join('، ') ?: 'موظف'" />
        @endforeach
    </div>
</section>
@endcan
@endsection
