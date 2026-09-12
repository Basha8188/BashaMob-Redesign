@props(['title' => 'الصفحة الرئيسية'])
<header class="admin-topbar glass-panel">
    <div class="topbar-page"><button class="sidebar-toggle" type="button" aria-label="فتح أو تصغير القائمة الجانبية" aria-expanded="true"><span></span><span></span><span></span></button><span><small><span class="luminous-brand compact">Basha<span>Mob</span></span> / الإدارة</small><b>{{ $title }}</b></span></div>
    <x-admin.search-bar placeholder="ابحث في النظام..." shortcut="⌘ K" />
    <div class="topbar-actions">
        <button class="language-toggle" id="admin-language-toggle" type="button" aria-label="تغيير اللغة"><span class="language-label">EN</span><i></i></button>
        <x-admin.icon-button class="notification-button" label="الإشعارات"><svg viewBox="0 0 24 24"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><b class="notification-count">3</b></x-admin.icon-button>
        <section class="notification-popover" id="admin-notifications" aria-hidden="true">
            <header><div><strong>الإشعارات</strong><small>3 إشعارات جديدة</small></div><button type="button" data-mark-notifications>تحديد الكل كمقروء</button></header>
            <div class="notification-items">
                <a href="{{ route('admin.module','inventory') }}"><i class="info"></i><span><strong>تنبيه مخزون منخفض</strong><small>4 منتجات وصلت إلى حد إعادة الطلب.</small><time>منذ 5 دقائق</time></span></a>
                <a href="{{ route('admin.module','repairs') }}"><i class="success"></i><span><strong>جهاز جاهز للاستلام</strong><small>تم الانتهاء من صيانة جهاز iPhone 13.</small><time>منذ 18 دقيقة</time></span></a>
                <a href="{{ route('admin.module','sales') }}"><i class="warning"></i><span><strong>فاتورة غير مسددة</strong><small>توجد فاتورتان تحتاجان إلى متابعة.</small><time>منذ ساعة</time></span></a>
            </div>
        </section>
        <span class="topbar-clock"><b>10:24</b><small>14 يوليو 2026</small></span>
        <div class="admin-user"><span><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->getRoleNames()->first() ?? 'Staff' }}</small></span><x-user-avatar class="topbar-user-avatar"/></div>
        <form action="{{ route('staff.logout') }}" method="POST">@csrf<button class="logout-button" type="submit" title="تسجيل الخروج"><svg viewBox="0 0 24 24"><path d="M10 5H5v14h5m4-3 4-4-4-4m4 4H9"/></svg><span>خروج</span></button></form>
    </div>
</header>
