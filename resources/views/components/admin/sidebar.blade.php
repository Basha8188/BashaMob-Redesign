<aside class="admin-sidebar glass-panel glass-panel--strong">
    <div class="sidebar-profile"><x-user-avatar class="sidebar-avatar"/><span><strong>محمد الباشا</strong><small>مدير عام</small></span><b>•••</b></div>
    <a class="admin-logo" href="{{ route('admin.dashboard') }}"><x-brand-symbol class="brand-mark"/><span class="admin-brand-copy"><strong class="luminous-brand">Basha<span>Mob</span></strong><small>أمان وثقة وخبرة سنين</small></span></a>
    <x-admin.search-bar compact placeholder="بحث سريع..." />
    <nav>
        <small class="sidebar-section-label">القائمة الرئيسية</small>
        <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><x-admin-icon name="dashboard"/><span>الصفحة الرئيسية</span></a>
        @foreach(['store'=>'بيانات المحل','products'=>'المنتجات والبضاعة','sales'=>'المبيعات','purchases'=>'المشتريات','inventory'=>'المخزن','repairs'=>'الصيانة','customers'=>'العملاء','suppliers'=>'الموردون','expenses'=>'المصروفات والخزنة','reports'=>'التقارير','team'=>'الموظفون والصلاحيات','settings'=>'الإعدادات'] as $key=>$label)
            <a class="{{ request()->route('module') === $key ? 'active' : '' }}" href="{{ route('admin.module', $key) }}"><x-admin-icon :name="$key"/><span>{{ $label }}</span>@if($key === 'customers')<b class="nav-count" data-customer-request-badge>3</b>@endif</a>
        @endforeach
    </nav>
    <div class="sidebar-help"><span>؟</span><div><strong>تحتاج مساعدة؟</strong><small>فريق الدعم متاح الآن</small></div></div>
    <a class="back-store" href="{{ route('home') }}">← العودة لواجهة العملاء</a>
</aside>
