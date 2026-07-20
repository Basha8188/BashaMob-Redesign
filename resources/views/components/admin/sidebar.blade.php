<aside class="admin-sidebar glass-panel glass-panel--strong">
    <div class="sidebar-profile"><x-user-avatar class="sidebar-avatar"/><span><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->getRoleNames()->first() ?? 'موظف' }}</small></span><b>•••</b></div>
    <a class="admin-logo" href="{{ route('admin.dashboard') }}"><x-brand-symbol class="brand-mark"/><span class="admin-brand-copy"><strong class="luminous-brand">Basha<span>Mob</span></strong><small>أمان وثقة وخبرة سنين</small></span></a>
    <x-admin.search-bar compact placeholder="بحث سريع..." />
    <nav>
        <small class="sidebar-section-label">القائمة الرئيسية</small>
        <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}" title="الصفحة الرئيسية"><x-admin-icon name="dashboard"/><span class="sidebar-link-label">الصفحة الرئيسية</span></a>
        @foreach(['store'=>'بيانات المحل','products'=>'المنتجات والبضاعة','sales'=>'المبيعات','purchases'=>'المشتريات','inventory'=>'المخزن','repairs'=>'الصيانة','customers'=>'العملاء','suppliers'=>'الموردون','expenses'=>'المصروفات والخزنة','reports'=>'التقارير','team'=>'الموظفون والصلاحيات','settings'=>'الإعدادات'] as $key=>$label)
            <a class="{{ request()->route('module') === $key ? 'active' : '' }}" href="{{ route('admin.module', $key) }}" title="{{ $label }}"><x-admin-icon :name="$key"/><span class="sidebar-link-label">{{ $label }}</span>@if($key === 'customers')@php($pendingRequests = \App\Models\MaintenanceRequest::where('status', 'pending_review')->count() + (\Illuminate\Support\Facades\Schema::hasTable('customer_orders') ? \App\Models\CustomerOrder::where('status', 'pending_review')->count() : 0))<b class="nav-count" data-customer-request-badge data-server-count="{{ $pendingRequests }}" @if($pendingRequests < 1) hidden @endif>{{ $pendingRequests }}</b>@endif</a>
        @endforeach
    </nav>
    <div class="sidebar-help"><span>؟</span><div><strong>تحتاج مساعدة؟</strong><small>فريق الدعم متاح الآن</small></div></div>
    <a class="back-store" href="{{ route('home') }}">← العودة لواجهة العملاء</a>
</aside>
