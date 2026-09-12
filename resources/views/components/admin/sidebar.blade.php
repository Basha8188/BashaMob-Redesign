<aside class="admin-sidebar glass-panel glass-panel--strong">
    <div class="sidebar-profile"><x-user-avatar class="sidebar-avatar"/><span><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->getRoleNames()->first() ?? 'موظف' }}</small></span><button class="sidebar-pin" type="button" data-sidebar-pin aria-label="تثبيت أو إلغاء تثبيت القائمة الجانبية" aria-pressed="false"><i></i><i></i><i></i></button></div>
    <a class="admin-logo" href="{{ route('admin.dashboard') }}"><x-brand-symbol class="brand-mark"/><span class="admin-brand-copy"><strong class="luminous-brand">Basha<span>Mob</span></strong><small>أمان وثقة وخبرة سنين</small></span></a>
    <x-admin.search-bar compact placeholder="بحث سريع..." />
    <nav data-liquid-nav="vertical">
        <span class="nav-liquid-indicator" data-liquid-indicator aria-hidden="true"></span>
        <small class="sidebar-section-label">القائمة الرئيسية</small>
        <a data-liquid-nav-item class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" aria-current="{{ request()->routeIs('admin.dashboard') ? 'page' : 'false' }}" href="{{ route('admin.dashboard') }}" title="الصفحة الرئيسية"><x-admin-icon name="dashboard"/><span class="sidebar-link-label">الصفحة الرئيسية</span></a>
        @can('device_passports.view')<a data-liquid-nav-item class="{{ request()->routeIs('admin.device-passports.*') ? 'active' : '' }}" aria-current="{{ request()->routeIs('admin.device-passports.*') ? 'page' : 'false' }}" href="{{ route('admin.device-passports.index') }}" title="جواز سفر الجهاز"><x-admin-icon name="repairs"/><span class="sidebar-link-label">جواز سفر الجهاز</span></a>@endcan
        @foreach(['store'=>'بيانات المحل','products'=>'المنتجات والبضاعة','sales'=>'المبيعات','purchases'=>'المشتريات','inventory'=>'المخزن','repairs'=>'الصيانة','customers'=>'العملاء','suppliers'=>'الموردون','expenses'=>'المصروفات والخزنة','reports'=>'التقارير','team'=>'الموظفون والصلاحيات','settings'=>'الإعدادات'] as $key=>$label)
            <a data-liquid-nav-item class="{{ request()->route('module') === $key ? 'active' : '' }}" aria-current="{{ request()->route('module') === $key ? 'page' : 'false' }}" href="{{ route('admin.module', $key) }}" title="{{ $label }}"><x-admin-icon :name="$key"/><span class="sidebar-link-label">{{ $label }}</span>@if($key === 'customers')@php($pendingRequests = \App\Models\MaintenanceRequest::where('status', 'pending_review')->count() + (\Illuminate\Support\Facades\Schema::hasTable('customer_orders') ? \App\Models\CustomerOrder::where('status', 'pending_review')->count() : 0))<b class="nav-count" data-customer-request-badge data-server-count="{{ $pendingRequests }}" @if($pendingRequests < 1) hidden @endif>{{ $pendingRequests }}</b>@endif</a>
            @if($key === 'reports')
                @can('incentives.view')
                    <a data-liquid-nav-item class="{{ request()->routeIs('admin.incentives.index', 'admin.incentives.plans.*', 'admin.incentives.periods.*', 'admin.incentives.employees.*') ? 'active' : '' }}" aria-current="{{ request()->routeIs('admin.incentives.index', 'admin.incentives.plans.*', 'admin.incentives.periods.*', 'admin.incentives.employees.*') ? 'page' : 'false' }}" href="{{ route('admin.incentives.index') }}" title="الحوافز والعمولات"><x-admin-icon name="incentives"/><span class="sidebar-link-label">الحوافز والعمولات</span></a>
                @endcan
                @canany(['incentives.view_own', 'incentives.view_all_employees'])
                    <a data-liquid-nav-item class="{{ request()->routeIs('admin.incentives.mine') ? 'active' : '' }}" aria-current="{{ request()->routeIs('admin.incentives.mine') ? 'page' : 'false' }}" href="{{ route('admin.incentives.mine') }}" title="حوافزي"><x-admin-icon name="commission"/><span class="sidebar-link-label">حوافزي</span></a>
                @endcanany
            @endif
        @endforeach
    </nav>
    <div class="sidebar-help"><span>؟</span><div><strong>تحتاج مساعدة؟</strong><small>فريق الدعم متاح الآن</small></div></div>
    <a class="back-store" href="{{ route('home') }}">← العودة لواجهة العملاء</a>
</aside>
